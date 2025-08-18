<?php

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php'; // Now includes createSlug()

requireAuth('admin');

$pageTitle = "Category Management";
$errors = [];
$successMessage = '';

// --- Handle Add Category ---
if (isset($_POST['add_category'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token. Please try again.';
    } else {
        // Get raw name and slug inputs from POST
        $nameInput = trim($_POST['name'] ?? '');
        $slugInput = trim($_POST['slug'] ?? '');

        // Generate or clean the slug using createSlug on the RAW input
        $slug = !empty($slugInput) ? createSlug($slugInput) : createSlug($nameInput);
        
        $parentId = filter_var($_POST['parent_id'] ?? null, FILTER_VALIDATE_INT);
        $parentId = ($parentId === false || $parentId === 0) ? null : $parentId; // Convert 0 or false to null for parent_id

        if (empty($nameInput)) { // Use nameInput for validation
            $errors[] = 'Category name is required.';
        }
        if (empty($slug)) {
            $errors[] = 'Generated slug is empty. Please ensure name or slug input is valid.';
        } else {
            // Check if slug is unique
            $checkSlugStmt = $pdo->prepare("SELECT id FROM categories WHERE slug = :slug");
            $checkSlugStmt->bindParam(':slug', $slug);
            $checkSlugStmt->execute();
            if ($checkSlugStmt->fetch()) {
                $errors[] = 'Category slug already exists. Please choose a different one or adjust the name.';
            }
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, parent_id) VALUES (:name, :slug, :parent_id)");
                $stmt->bindParam(':name', $nameInput); // Store the (possibly) unescaped name
                $stmt->bindParam(':slug', $slug); // Store the clean slug
                $stmt->bindParam(':parent_id', $parentId, PDO::PARAM_INT);
                $stmt->execute();
                $successMessage = "Category added successfully!";
                // Clear form fields after successful submission
                $_POST = []; 
            } catch (PDOException $e) {
                $errors[] = "Failed to add category: " . $e->getMessage();
            }
        }
    }
    header('Location: ' . BASE_URL . '/admin/categories.php');
}

// --- Handle Edit Category ---
if (isset($_POST['edit_category'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token. Please try again.';
    } else {
        $categoryId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
        // Get raw name and slug inputs from POST
        $nameInput = trim($_POST['name'] ?? '');
        $slugInput = trim($_POST['slug'] ?? '');

        // Generate or clean the slug for editing using createSlug on the RAW input
        $slug = !empty($slugInput) ? createSlug($slugInput) : createSlug($nameInput);

        $parentId = filter_var($_POST['parent_id'] ?? null, FILTER_VALIDATE_INT);
        $parentId = ($parentId === false || $parentId === 0) ? null : $parentId;

        if (!$categoryId) {
            $errors[] = 'Category ID for editing is missing.';
        }
        if (empty($nameInput)) { // Use nameInput for validation
            $errors[] = 'Category name is required.';
        }
        if (empty($slug)) {
            $errors[] = 'Generated slug is empty. Please ensure name or slug input is valid.';
        } else {
            // Check if slug is unique (excluding current category)
            $checkSlugStmt = $pdo->prepare("SELECT id FROM categories WHERE slug = :slug AND id != :category_id");
            $checkSlugStmt->bindParam(':slug', $slug);
            $checkSlugStmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
            $checkSlugStmt->execute();
            if ($checkSlugStmt->fetch()) {
                $errors[] = 'Category slug already exists for another category.';
            }
        }

        // Prevent a category from being its own parent or a descendant of itself (simple check for direct parent)
        if ($parentId == $categoryId) {
            $errors[] = 'A category cannot be its own parent.';
        }
        // More complex recursive check for preventing circular dependencies would be needed for deeper hierarchies.

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE categories SET name = :name, slug = :slug, parent_id = :parent_id WHERE id = :category_id");
                $stmt->bindParam(':name', $nameInput); // Store the (possibly) unescaped name
                $stmt->bindParam(':slug', $slug); // Store the clean slug
                $stmt->bindParam(':parent_id', $parentId, PDO::PARAM_INT);
                $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
                $stmt->execute();
                $successMessage = "Category updated successfully!";
                // Unset edit mode
                unset($_GET['edit_id']);
                // Clear POST to prevent re-submission of old data in form
                $_POST = [];
            } catch (PDOException $e) {
                $errors[] = "Failed to update category: " . $e->getMessage();
            }
        }
    }
    header('Location: ' . BASE_URL . '/admin/categories.php');
}

// --- Handle Delete Category ---
if (isset($_GET['delete_id'])) {
    $categoryId = filter_var($_GET['delete_id'] ?? null, FILTER_VALIDATE_INT);
    $csrfToken = $_GET['csrf_token'] ?? '';

    if (!$categoryId) {
        $_SESSION['error'] = "Category ID for deletion is missing.";
    } elseif (!verifyCsrfToken($csrfToken)) {
        $_SESSION['error'] = 'Invalid CSRF token. Please try again.';
    } else {
        try {
            // Check if any books are associated with this category via book_categories
            $checkBooksStmt = $pdo->prepare("SELECT COUNT(*) FROM book_categories WHERE category_id = :category_id");
            $checkBooksStmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
            $checkBooksStmt->execute();
            if ($checkBooksStmt->fetchColumn() > 0) {
                $_SESSION['error'] = "Cannot delete category: Books are currently assigned to it. Please reassign or delete books first.";
            } else {
                // Set children categories' parent_id to NULL to prevent breaking foreign key constraints
                $updateChildrenStmt = $pdo->prepare("UPDATE categories SET parent_id = NULL WHERE parent_id = :category_id");
                $updateChildrenStmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
                $updateChildrenStmt->execute();

                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :category_id");
                $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
                $stmt->execute();
                $_SESSION['success'] = "Category deleted successfully.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to delete category: " . $e->getMessage();
        }
    }
    header('Location: ' . BASE_URL . '/admin/categories.php');
    exit;
}

// --- Fetch all categories for display and parent dropdown ---
$allCategories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// --- Fetch category to edit if edit_id is present ---
$categoryToEdit = null;
if (isset($_GET['edit_id'])) {
    $editId = filter_var($_GET['edit_id'] ?? null, FILTER_VALIDATE_INT);
    if ($editId) {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id");
        $stmt->bindParam(':id', $editId, PDO::PARAM_INT);
        $stmt->execute();
        $categoryToEdit = $stmt->fetch();
        if (!$categoryToEdit) {
            $errors[] = "Category not found for editing.";
        }
    }
}

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-2xl">
    <h1 class="text-3xl font-bold text-sky-800 mb-6">Category Management</h1>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($successMessage); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4"><?php echo $categoryToEdit ? 'Edit Category' : 'Add New Category'; ?></h2>
        <form method="post" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <?php if ($categoryToEdit): ?>
                <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($categoryToEdit['id']); ?>">
            <?php endif; ?>

            <div>
                <label for="name" class="block text-gray-700 font-bold mb-1">Category Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($nameInput ?? $categoryToEdit['name'] ?? ''); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
            </div>
            
            <div>
                <label for="slug" class="block text-gray-700 font-bold mb-1">Category Slug <span class="text-red-500">*</span></label>
                <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($slugInput ?? $categoryToEdit['slug'] ?? ''); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md" 
                       placeholder="e.g., fiction-books, science-fantasy">
                <p class="text-sm text-gray-500 mt-1">Unique, URL-friendly version of the name. Will be auto-generated if left empty.</p>
            </div>

            <div>
                <label for="parent_id" class="block text-gray-700 font-bold mb-1">Parent Category (Optional)</label>
                <select id="parent_id" name="parent_id" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">-- No Parent --</option>
                    <?php foreach ($allCategories as $cat): ?>
                        <?php if (!$categoryToEdit || $cat['id'] !== $categoryToEdit['id']): // Prevent category from being its own parent ?>
                            <option value="<?php echo htmlspecialchars($cat['id']); ?>"
                                <?php echo ((isset($_POST['parent_id']) && $_POST['parent_id'] == $cat['id']) || ($categoryToEdit && $categoryToEdit['parent_id'] == $cat['id'])) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" name="<?php echo $categoryToEdit ? 'edit_category' : 'add_category'; ?>" 
                    class="w-full bg-sky-600 text-white py-2 px-4 rounded-md hover:bg-sky-700 transition duration-300">
                <?php echo $categoryToEdit ? 'Update Category' : 'Add Category'; ?>
            </button>
            <?php if ($categoryToEdit): ?>
                <a href="<?= BASE_URL ?>/admin/categories.php" class="block text-center mt-2 w-full py-2 px-4 rounded-md transition duration-300 bg-gray-600 text-white hover:bg-gray-300 hover:text-gray-700">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </div>

</div>
<div class="container mx-auto px-4 pb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Existing Categories</h2>
        <?php if (empty($allCategories)): ?>
            <p class="text-gray-600">No categories added yet.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="data-table min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parent</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($allCategories as $cat): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($cat['id']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($cat['name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($cat['slug']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                        if ($cat['parent_id']) {
                                            $parentName = array_filter($allCategories, fn($parent) => $parent['id'] == $cat['parent_id']);
                                            echo htmlspecialchars($parentName ? reset($parentName)['name'] : 'N/A');
                                        } else {
                                            echo 'None';
                                        }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                    <a href="<?= BASE_URL ?>/admin/categories.php?edit_id=<?php echo $cat['id']; ?>" class="text-sky-600 hover:text-sky-800 mr-2">Edit</a>
                                    <a href="<?= BASE_URL ?>/admin/categories.php?delete_id=<?php echo $cat['id']; ?>&csrf_token=<?php echo generateCsrfToken(); ?>" 
                                    onclick="return confirm('Are you sure you want to delete this category? (Books linked to this category must be reassigned first)')"
                                    class="text-red-600 hover:text-red-800">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

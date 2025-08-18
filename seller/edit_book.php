<?php
    require_once '../config/database.php';
    require_once '../includes/auth.php';
    require_once '../includes/helpers.php';

    requireAuth('seller');

    $user = currentUser();
    $pageTitle = "Edit Book";
    $errors = [];
    $bookId = $_GET['id'] ?? null;

    if (!$bookId) {
        header('Location:  ' . BASE_URL . '/seller/books.php');
        exit;
    }

    // Fetch book details
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ? AND seller_id = ?");
    $stmt->execute([$bookId, $user['id']]);
    $book = $stmt->fetch();

    if (!$book) {
        $_SESSION['error'] = "Book not found or you don't have permission to edit it.";
        header('Location:  ' . BASE_URL . '/seller/books.php');
        exit;
    }

    // Fetch current categories for this book
    $currentCategoriesStmt = $pdo->prepare("SELECT category_id FROM book_categories WHERE book_id = ?");
    $currentCategoriesStmt->execute([$bookId]);
    $currentCategories = $currentCategoriesStmt->fetchAll(PDO::FETCH_COLUMN);
    $currentCategories = $currentCategories ?? [];

    // ==============================
    // Fetch categories
    // ==============================
    $stmt = $pdo->query("SELECT id, name, parent_id FROM categories ORDER BY parent_id, name");
    $allCategories = $stmt->fetchAll();

    // Convert flat array to tree
    function buildTree(array $elements, $parentId = null) {
        $branch = [];
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    $categoryTree = buildTree($allCategories);

    // ==============================
    // Render tree recursively
    // ==============================
    function renderTree($tree, $currentCategories) {
        echo '<ul class="category-tree">';
        foreach ($tree as $node) {
            $hasChildren = !empty($node['children']);
            echo '<li>';
                        // Add toggle button if node has children
            if ($hasChildren) {
                echo '<span class="toggle"><i class="fa fa-plus-square toggleicon"></i></span> ';
            } else {
                echo '<span class="toggle-placeholder"><i class="fa fa-square"></i></span> ';
            }
            echo '<input type="checkbox" class="category" name="categories[]" value="'.$node['id'].'" data-id="'.$node['id'].'" id="cat_'.$node['id'].'" '.(in_array($node['id'], $currentCategories) ? 'checked' : '').' >';
            echo '<label for="cat_'.$node['id'].'"> '.$node['name'].'</label>';
            if ($hasChildren) {
                echo '<div class="children" style="display:none;">';
                    renderTree($node['children'], $currentCategories); // recursive call
                echo '</div>';
            }
            echo '</li>';
        }
        echo '</ul>';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSRF Token validation
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Invalid CSRF token. Please try again.';
            header('Location:  ' . BASE_URL . '/seller/edit_book.php?id=' . $bookId);
            exit;
        }

        $title = sanitizeInput($_POST['title'] ?? '');
        $author = sanitizeInput($_POST['author'] ?? '');
        $isbn = sanitizeInput($_POST['isbn'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
        $stock = filter_var($_POST['stock'] ?? 0, FILTER_VALIDATE_INT);
        $isPhysical = isset($_POST['is_physical']) ? 1 : 0;
        $isDigital = isset($_POST['is_digital']) ? 1 : 0;
        $selectedCategories = $_POST['categories'] ?? [];

        // Validation
        if (empty($title)) $errors['title'] = 'Title is required.';
        if (empty($author)) $errors['author'] = 'Author is required.';
        if ($price === false || $price <= 0) $errors['price'] = 'Valid price is required.';
        if ($isPhysical && ($stock === false || $stock < 0)) $errors['stock'] = 'Valid stock quantity is required for physical books.';
        if (!$isPhysical && !$isDigital) $errors['format'] = 'At least one format (physical or digital) must be selected.';

        $coverImage = $book['cover_image'];
        $digitalFile = $book['digital_file'];
        $previewPages = $book['preview_pages'];

        // Handle Cover Image Upload
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $imgDir = __DIR__ . '/../assets/images/books/';
            $imgExtension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
            $newCoverImage = uniqid('cover_') . '.' . $imgExtension;
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $imgDir . $newCoverImage)) {
                // Delete old cover image if it exists and is different
                if ($coverImage && file_exists($imgDir . $coverImage) && $coverImage !== $newCoverImage) {
                    unlink($imgDir . $coverImage);
                }
                $coverImage = $newCoverImage;
            } else {
                $errors['cover_image'] = 'Failed to upload new cover image.';
            }
        } elseif (isset($_POST['remove_cover_image']) && $_POST['remove_cover_image'] == '1') {
            // Remove existing cover image
            if ($coverImage && file_exists(__DIR__ . '/../assets/images/books/' . $coverImage)) {
                unlink(__DIR__ . '/../assets/images/books/' . $coverImage);
            }
            $coverImage = null;
        }


        // Handle Digital File Upload
        if ($isDigital && isset($_FILES['digital_file']) && $_FILES['digital_file']['error'] === UPLOAD_ERR_OK) {
            $digitalDir = __DIR__ . '/../assets/digital_books/';
            $digitalExtension = pathinfo($_FILES['digital_file']['name'], PATHINFO_EXTENSION);
            $newDigitalFile = uniqid('digital_') . '.' . $digitalExtension;
            if (move_uploaded_file($_FILES['digital_file']['tmp_name'], $digitalDir . $newDigitalFile)) {
                if ($digitalFile && file_exists($digitalDir . $digitalFile) && $digitalFile !== $newDigitalFile) {
                    unlink($digitalDir . $digitalFile);
                }
                $digitalFile = $newDigitalFile;
            } else {
                $errors['digital_file'] = 'Failed to upload new digital file.';
            }
        } elseif (!$isDigital && $digitalFile) { // If format changed from digital or digital option unchecked
            if (file_exists(__DIR__ . '/../assets/digital_books/' . $digitalFile)) {
                unlink(__DIR__ . '/../assets/digital_books/' . $digitalFile);
            }
            $digitalFile = null;
        }


        // Handle Preview Pages Upload
        if (isset($_FILES['preview_pages']) && $_FILES['preview_pages']['error'] === UPLOAD_ERR_OK) {
            $previewDir = __DIR__ . '/../assets/previews/';
            $previewExtension = pathinfo($_FILES['preview_pages']['name'], PATHINFO_EXTENSION);
            $newPreviewPages = uniqid('preview_') . '.' . $previewExtension;
            if (move_uploaded_file($_FILES['preview_pages']['tmp_name'], $previewDir . $newPreviewPages)) {
                if ($previewPages && file_exists($previewDir . $previewPages) && $previewPages !== $newPreviewPages) {
                    unlink($previewDir . $previewPages);
                }
                $previewPages = $newPreviewPages;
            } else {
                $errors['preview_pages'] = 'Failed to upload new preview pages.';
            }
        } elseif (isset($_POST['remove_preview_pages']) && $_POST['remove_preview_pages'] == '1') {
            if ($previewPages && file_exists(__DIR__ . '/../assets/previews/' . $previewPages)) {
                unlink(__DIR__ . '/../assets/previews/' . $previewPages);
            }
            $previewPages = null;
        }


        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("UPDATE books SET 
                    title = ?, author = ?, isbn = ?, description = ?, price = ?, stock = ?, 
                    cover_image = ?, preview_pages = ?, is_physical = ?, is_digital = ?, digital_file = ?
                    WHERE id = ? AND seller_id = ?");
                
                $stmt->execute([
                    $title, $author, $isbn, $description, $price, $stock, 
                    $coverImage, $previewPages, $isPhysical, $isDigital, $digitalFile, 
                    $bookId, $user['id']
                ]);

                // Update categories: remove old, add new
                $pdo->prepare("DELETE FROM book_categories WHERE book_id = ?")->execute([$bookId]);
                if (!empty($selectedCategories)) {
                    $categoryStmt = $pdo->prepare("INSERT INTO book_categories (book_id, category_id) VALUES (?, ?)");
                    foreach ($selectedCategories as $catId) {
                        $categoryStmt->execute([$bookId, $catId]);
                    }
                }

                $pdo->commit();
                $_SESSION['success'] = "Book updated successfully!";
                header('Location:  ' . BASE_URL . '/seller/books.php');
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors['general'] = 'Failed to update book: ' . $e->getMessage();
                // Re-fetch book to revert changes in case of error for consistent display
                $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ? AND seller_id = ?");
                $stmt->execute([$bookId, $user['id']]);
                $book = $stmt->fetch();
            }
        } else {
            // If there are validation errors, ensure the form retains submitted values
            // This is implicit if we don't redirect on validation errors and re-render the form
            // We'll also need to re-fetch the categories for current book if validation fails to populate them
            $book = array_merge($book, $_POST); // Merge POST data to keep form values
            $book['cover_image'] = $coverImage; // Keep updated image name if upload succeeded before other errors
            $book['digital_file'] = $digitalFile;
            $book['preview_pages'] = $previewPages;
        }
    }

    include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-3xl">
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-sky-800 mb-6 text-center">Edit Book: <?php echo htmlspecialchars($book['title']); ?></h1>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $errors['general']; ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

            <div>
                <label for="title" class="block text-gray-700 font-bold mb-1">Title <span class="text-red-500">*</span></label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($book['title'] ?? ''); ?>"
                       class="w-full px-3 py-2 border <?php echo isset($errors['title']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md" required>
                <?php if (isset($errors['title'])): ?><p class="text-red-500 text-sm mt-1"><?php echo $errors['title']; ?></p><?php endif; ?>
            </div>

            <div>
                <label for="author" class="block text-gray-700 font-bold mb-1">Author <span class="text-red-500">*</span></label>
                <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($book['author'] ?? ''); ?>"
                       class="w-full px-3 py-2 border <?php echo isset($errors['author']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md" required>
                <?php if (isset($errors['author'])): ?><p class="text-red-500 text-sm mt-1"><?php echo $errors['author']; ?></p><?php endif; ?>
            </div>

            <div>
                <label for="isbn" class="block text-gray-700 font-bold mb-1">ISBN (Optional)</label>
                <input type="text" id="isbn" name="isbn" value="<?php echo htmlspecialchars($book['isbn'] ?? ''); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>

            <div>
                <label for="description" class="block text-gray-700 font-bold mb-1">Description</label>
                <textarea id="description" name="description" rows="6"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md"><?php echo htmlspecialchars($book['description'] ?? ''); ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="price" class="block text-gray-700 font-bold mb-1">Price ($) <span class="text-red-500">*</span></label>
                    <input type="number" id="price" name="price" step="0.01" min="0.01" value="<?php echo htmlspecialchars($book['price'] ?? ''); ?>"
                           class="w-full px-3 py-2 border <?php echo isset($errors['price']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md" required>
                    <?php if (isset($errors['price'])): ?><p class="text-red-500 text-sm mt-1"><?php echo $errors['price']; ?></p><?php endif; ?>
                </div>
                <div>
                    <label for="stock" class="block text-gray-700 font-bold mb-1">Stock (for Physical Books)</label>
                    <input type="number" id="stock" name="stock" min="0" value="<?php echo htmlspecialchars($book['stock'] ?? '0'); ?>"
                           class="w-full px-3 py-2 border <?php echo isset($errors['stock']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md" <?php echo ($book['is_physical'] == 0) ? 'disabled' : ''; ?>>
                    <?php if (isset($errors['stock'])): ?><p class="text-red-500 text-sm mt-1"><?php echo $errors['stock']; ?></p><?php endif; ?>
                </div>
            </div>

            <div>
                <label class="block text-gray-700 font-bold mb-1">Book Format <span class="text-red-500">*</span></label>
                <div class="flex items-center space-x-4">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_physical" value="1" id="is_physical" <?php echo $book['is_physical'] ? 'checked' : ''; ?> onchange="toggleStockField()" class="form-checkbox">
                        <span class="ml-2">Physical Book</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="is_digital" value="1" id="is_digital" <?php echo $book['is_digital'] ? 'checked' : ''; ?> onchange="toggleDigitalFields()" class="form-checkbox">
                        <span class="ml-2">Digital Book (eBook)</span>
                    </label>
                </div>
                <?php if (isset($errors['format'])): ?><p class="text-red-500 text-sm mt-1"><?php echo $errors['format']; ?></p><?php endif; ?>
            </div>

            <div id="digital-upload-fields" class="space-y-4 <?php echo $book['is_digital'] ? '' : 'hidden'; ?>">
                <div>
                    <label for="digital_file" class="block text-gray-700 font-bold mb-1">Digital File (PDF, EPUB, etc.)</label>
                    <?php if ($book['digital_file']): ?>
                        <p class="text-gray-600 mb-2">Current File: <a href="<?= BASE_URL ?>/assets/digital_books/<?php echo htmlspecialchars($book['digital_file']); ?>" target="_blank" class="text-sky-600 hover:underline"><?php echo htmlspecialchars($book['digital_file']); ?></a></p>
                    <?php endif; ?>
                    <input type="file" id="digital_file" name="digital_file" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <p class="text-sm text-gray-500 mt-1">Upload a new digital file to replace the current one.</p>
                    <?php if (isset($errors['digital_file'])): ?><p class="text-red-500 text-sm mt-1"><?php echo $errors['digital_file']; ?></p><?php endif; ?>
                </div>
                <div>
                    <label for="preview_pages" class="block text-gray-700 font-bold mb-1">Preview Pages (Optional)</label>
                    <?php if ($book['preview_pages']): ?>
                        <p class="text-gray-600 mb-2">Current Preview: <a href="<?= BASE_URL ?>/assets/previews/<?php echo htmlspecialchars($book['preview_pages']); ?>" target="_blank" class="text-sky-600 hover:underline"><?php echo htmlspecialchars($book['preview_pages']); ?></a></p>
                        <label class="flex items-center mb-2">
                            <input type="checkbox" name="remove_preview_pages" value="1" class="form-checkbox">
                            <span class="ml-2 text-sm text-gray-600">Remove current preview</span>
                        </label>
                    <?php endif; ?>
                    <input type="file" id="preview_pages" name="preview_pages" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <p class="text-sm text-gray-500 mt-1">Upload a new sample PDF/image for preview.</p>
                    <?php if (isset($errors['preview_pages'])): ?><p class="text-red-500 text-sm mt-1"><?php echo $errors['preview_pages']; ?></p><?php endif; ?>
                </div>
            </div>

            <div>
                <label for="cover_image" class="block text-gray-700 font-bold mb-1">Cover Image (Optional)</label>
                <?php if ($book['cover_image']): ?>
                    <div class="mb-4">
                        <img src="<?= BASE_URL ?>/assets/images/books/<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Current Cover" class="w-32 h-auto rounded-md shadow-md mb-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="remove_cover_image" value="1" class="form-checkbox">
                            <span class="ml-2 text-sm text-gray-600">Remove current cover image</span>
                        </label>
                    </div>
                <?php endif; ?>
                <input type="file" id="cover_image" name="cover_image" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md">
                <p class="text-sm text-gray-500 mt-1">Upload a new image for the book cover (JPG, PNG, GIF).</p>
                <?php if (isset($errors['cover_image'])): ?><p class="text-red-500 text-sm mt-1"><?php echo $errors['cover_image']; ?></p><?php endif; ?>
            </div>

            <div>
                <label for="categories" class="block text-gray-700 font-bold mb-1">Categories (Select one or more)</label>
                <div class="overflow-auto max-h-[150px]"><?php renderTree($categoryTree, $currentCategories); ?></div>
            </div>

            <button type="submit" class="bg-sky-600 text-white px-6 py-2 rounded-md hover:bg-sky-700 transition duration-300">
                Update Book
            </button>
            <a href="<?= BASE_URL ?>/seller/books.php" class="inline-block px-4 py-2 rounded-md bg-gray-600 text-white hover:bg-gray-300 hover:text-gray-700 mb-2">Cancel</a>
        </form>
    </div>
</div>

<script>
    function toggleDigitalFields() {
        const isDigitalCheckbox = document.getElementById('is_digital');
        const digitalUploadFields = document.getElementById('digital-upload-fields');
        const digitalFileInput = document.getElementById('digital_file');

        if (isDigitalCheckbox.checked) {
            digitalUploadFields.classList.remove('hidden');
            // Make digital file required ONLY if it's a new upload AND there's no existing file
            if (!digitalFileInput.value && !'<?php echo $book['digital_file']; ?>') {
                digitalFileInput.setAttribute('required', 'required');
            }
        } else {
            digitalUploadFields.classList.add('hidden');
            digitalFileInput.removeAttribute('required');
            digitalFileInput.value = ''; // Clear file input
        }
    }
</script>

<?php include '../includes/footer.php'; ?>

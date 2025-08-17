<?php
// Ensure necessary files are included and BASE_PATH/BASE_URL are defined
require_once '../config/database.php';
require_once '../includes/auth.php'; // Provides requireAuth, currentUser, isLoggedIn, hasRole
require_once '../includes/helpers.php'; // Provides sanitizeInput, generateCsrfToken, verifyCsrfToken

requireAuth('admin');

$pageTitle = "Edit User";
$errors = [];
$userId = $_GET['id'] ?? null;

if (!$userId) {
    $_SESSION['error'] = "User ID not provided.";
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

// Fetch user data
$stmt = $pdo->prepare("SELECT id, username, email, role, is_active FROM users WHERE id = :user_id");
$stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();
$userToEdit = $stmt->fetch();

if (!$userToEdit) {
    $_SESSION['error'] = "User not found.";
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

if ($userToEdit && $_SESSION['user_id'] == $userToEdit['id']) {
    $_SESSION['error'] = "Operation not allowed.";
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token validation
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid CSRF token. Please try again.';
        header('Location: ' . BASE_URL . '/admin/edit_user.php?id=' . $userId);
        exit;
    }

    $newUsername = sanitizeInput($_POST['username'] ?? '');
    $newEmail = sanitizeInput($_POST['email'] ?? '');
    $newRole = sanitizeInput($_POST['role'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Basic validation
    if (empty($newUsername)) {
        $errors['username'] = "Username cannot be empty.";
    }
    if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Valid email is required.";
    }
    $allowedRoles = ['admin', 'seller', 'buyer'];
    if (!in_array($newRole, $allowedRoles)) {
        $errors['role'] = "Invalid role selected.";
    }

    // Check for duplicate username/email (excluding current user)
    $checkUsernameStmt = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :user_id");
    $checkUsernameStmt->execute([':username' => $newUsername, ':user_id' => $userId]);
    if ($checkUsernameStmt->fetch()) {
        $errors['username'] = "Username already taken.";
    }

    $checkEmailStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :user_id");
    $checkEmailStmt->execute([':email' => $newEmail, ':user_id' => $userId]);
    if ($checkEmailStmt->fetch()) {
        $errors['email'] = "Email already taken.";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET username = :username, email = :email, role = :role, is_active = :is_active WHERE id = :user_id");
            $stmt->execute([
                ':username' => $newUsername,
                ':email' => $newEmail,
                ':role' => $newRole,
                ':is_active' => $isActive,
                ':user_id' => $userId
            ]);

            $_SESSION['success'] = "User updated successfully!";
            header('Location: ' . BASE_URL . '/admin/users.php');
            exit;
        } catch (PDOException $e) {
            $errors['general'] = "Database error: " . $e->getMessage();
        }
    }
    // If there are errors, ensure the form retains submitted values
    $userToEdit['username'] = $_POST['username'];
    $userToEdit['email'] = $_POST['email'];
    $userToEdit['role'] = $_POST['role'];
    $userToEdit['is_active'] = $isActive;
}

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-sky-800 mb-6 text-center">Edit User: <?php echo htmlspecialchars($userToEdit['username']); ?></h1>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $errors['general']; ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

            <div>
                <label for="username" class="block text-gray-700 font-bold mb-1">Username <span class="text-red-500">*</span></label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($userToEdit['username']); ?>"
                       class="w-full px-3 py-2 border <?php echo isset($errors['username']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md" required>
                <?php if (isset($errors['username'])): ?><p class="text-red-500 text-sm mt-1"><?php echo $errors['username']; ?></p><?php endif; ?>
            </div>

            <div>
                <label for="email" class="block text-gray-700 font-bold mb-1">Email <span class="text-red-500">*</span></label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userToEdit['email']); ?>"
                       class="w-full px-3 py-2 border <?php echo isset($errors['email']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md" required>
                <?php if (isset($errors['email'])): ?><p class="text-red-500 text-sm mt-1"><?php echo $errors['email']; ?></p><?php endif; ?>
            </div>

            <div>
                <label for="role" class="block text-gray-700 font-bold mb-1">Role <span class="text-red-500">*</span></label>
                <select id="role" name="role" class="w-full px-3 py-2 border <?php echo isset($errors['role']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md" required>
                    <option value="admin" <?php echo ($userToEdit['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="seller" <?php echo ($userToEdit['role'] === 'seller') ? 'selected' : ''; ?>>Seller</option>
                    <option value="buyer" <?php echo ($userToEdit['role'] === 'buyer') ? 'selected' : ''; ?>>Buyer</option>
                </select>
                <?php if (isset($errors['role'])): ?><p class="text-red-500 text-sm mt-1"><?php echo $errors['role']; ?></p><?php endif; ?>
            </div>

            <div class="flex items-center">
                <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo $userToEdit['is_active'] ? 'checked' : ''; ?>
                       class="form-checkbox h-5 w-5 text-sky-600">
                <label for="is_active" class="ml-2 block text-gray-700">Is Active</label>
            </div>
            
            <button type="submit" class="bg-sky-600 text-white py-2 px-4 rounded-md hover:bg-sky-700 transition duration-300">
                Update User
            </button>
            <a href="<?= BASE_URL ?>/admin/users.php" class="inline-block px-4 py-2 rounded-md bg-gray-600 text-white hover:bg-gray-300 hover:text-gray-700 mb-2">Cancel</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php'; // Needed for generateCsrfToken

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

$pageTitle = "Register";
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'buyer'; // Default to 'buyer' if not set
    $storeName = ($role === 'seller') ? sanitizeInput($_POST['store_name'] ?? '') : '';

    // CSRF Token validation
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'Invalid CSRF token. Please try again.';
    }

    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (strlen($username) < 4) {
        $errors['username'] = 'Username must be at least 4 characters';
    }

    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }

    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    if ($role === 'seller' && empty($storeName)) {
        $errors['store_name'] = 'Store name is required for sellers';
    }

    // Check if username or email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors['general'] = 'Username or email already exists. Please choose another.';
        }
    }

    // If no errors, create user
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $isActive = ($role === 'buyer') ? 1 : 0; // Buyers are active immediately, sellers await approval
            $isApproved = ($role === 'buyer') ? 1 : 0; // Buyers are approved immediately, sellers await approval

            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, is_active, is_approved) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hashedPassword, $role, $isActive, $isApproved]);
            $userId = $pdo->lastInsertId();

            if ($role === 'seller') {
                $stmt = $pdo->prepare("INSERT INTO seller_profiles (user_id, store_name) VALUES (?, ?)");
                $stmt->execute([$userId, $storeName]);
            }

            $pdo->commit();

            if ($role === 'buyer') {
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                $_SESSION['user_role'] = $role;
                session_regenerate_id(true); // Regenerate session ID for security
                $_SESSION['success'] = "Account created successfully! Welcome.";
                header('Location: ' . BASE_URL . '/buyer/'); // Redirect buyers directly to their dashboard
            } else { // Seller
                $_SESSION['success'] = "Seller registration submitted for approval. You'll be notified once your account is activated.";
                header('Location: ' . BASE_URL . '/auth/login.php'); // Sellers redirected to login, awaiting approval
            }
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['general'] = 'Registration failed: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-md">
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-sky-800 mb-6 text-center">Create Account</h1>

        <?php if (!empty($errors['general'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $errors['general']; ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

            <div>
                <label class="block text-gray-700 mb-1">Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                       class="w-full px-3 py-2 border <?php echo isset($errors['username']) ? 'border-red-500' : 'border-gray-300'; ?> rounded" required>
                <?php if (isset($errors['username'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $errors['username']; ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       class="w-full px-3 py-2 border <?php echo isset($errors['email']) ? 'border-red-500' : 'border-gray-300'; ?> rounded" required>
                <?php if (isset($errors['email'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $errors['email']; ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-gray-700 mb-1">Password</label>
                <input type="password" name="password"
                       class="w-full px-3 py-2 border <?php echo isset($errors['password']) ? 'border-red-500' : 'border-gray-300'; ?> rounded" required>
                <?php if (isset($errors['password'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $errors['password']; ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-gray-700 mb-1">Confirm Password</label>
                <input type="password" name="confirm_password"
                       class="w-full px-3 py-2 border <?php echo isset($errors['confirm_password']) ? 'border-red-500' : 'border-gray-300'; ?> rounded" required>
                <?php if (isset($errors['confirm_password'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $errors['confirm_password']; ?></p>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 mb-1">Register as:</label>
                <div class="flex items-center space-x-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="role" value="buyer" class="form-radio" checked onchange="toggleStoreName()">
                        <span class="ml-2">Buyer</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="role" value="seller" class="form-radio" onchange="toggleStoreName()" <?php if (isset($_GET['role']) && $_GET['role'] == 'seller'){echo 'checked';} ?> >
                        <span class="ml-2">Seller</span>
                    </label>
                </div>
            </div>

            <div id="store-name-group" class="hidden">
                <label class="block text-gray-700 mb-1">Store Name</label>
                <input type="text" name="store_name" value="<?php echo htmlspecialchars($_POST['store_name'] ?? ''); ?>"
                       class="w-full px-3 py-2 border <?php echo isset($errors['store_name']) ? 'border-red-500' : 'border-gray-300'; ?> rounded">
                <?php if (isset($errors['store_name'])): ?>
                    <p class="text-red-500 text-sm mt-1"><?php echo $errors['store_name']; ?></p>
                <?php endif; ?>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full bg-sky-600 text-white py-2 px-4 rounded hover:bg-sky-700">
                    Register
                </button>
            </div>

            <div class="text-center text-sm text-gray-600">
                Already have an account? <a href="login.php" class="text-sky-600 hover:text-sky-800">Login here</a>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleStoreName() {
        const role = document.querySelector('input[name="role"]:checked').value;
        const storeNameGroup = document.getElementById('store-name-group');
        const storeNameInput = storeNameGroup.querySelector('input[name="store_name"]');

        if (role === 'seller') {
            storeNameGroup.classList.remove('hidden');
            storeNameInput.setAttribute('required', 'required');
        } else {
            storeNameGroup.classList.add('hidden');
            storeNameInput.removeAttribute('required');
        }
    }

    // Call on load to set initial state based on default radio button
    document.addEventListener('DOMContentLoaded', toggleStoreName);
</script>

<?php include '../includes/footer.php'; ?>
<?php
// Ensure database config and authentication functions are available
require_once '../config/database.php';
require_once '../includes/auth.php'; // This provides isLoggedIn(), currentUser(), hasRole(), etc.
require_once '../includes/helpers.php'; // This provides sanitizeInput(), generateCsrfToken(), etc.

// Redirect if user is already logged in
if (isLoggedIn()) {
    // Access BASE_URL from the database.php configuration
    header('Location: ' . BASE_URL . '/');
    exit;
}

$error = "";
$info = "";

$pageTitle = "Login";
if(isset($_SESSION['success']) && $_SESSION['success'] == "Seller registration submitted for approval. You'll be notified once your account is activated."){
    $info = $_SESSION['success'];
    unset($_SESSION['success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        // Attempt to log in the user
        // Using the email field in the database for login as per register.php admin setup
        // It's more common to log in with username OR email. Assuming username as the primary login field for now.
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Redirect based on role, using BASE_URL for correct pathing
            $redirect = match($user['role']) {
                'admin' => BASE_URL . '/admin/',
                'seller' => BASE_URL . '/seller/',
                default => BASE_URL . '/buyer/' // Default for 'buyer' or any other role
            };
            header("Location: $redirect");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}

include '../includes/header.php'; // This includes the header, which relies on BASE_URL and session variables
?>

<div class="container mx-auto px-4 py-8 max-w-md">
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-sky-800 mb-6 text-center">Login</h1>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($info): ?>
            <div class="bg-cyan-100 border border-cyan-400 text-cyan-600 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($info); ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div>
                <label class="block text-gray-700 mb-1">Username</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded">
            </div>
            
            <div>
                <label class="block text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded">
            </div>
            
            <div class="pt-2">
                <button type="submit" class="w-full bg-sky-600 text-white py-2 px-4 rounded hover:bg-sky-700">
                    Login
                </button>
            </div>
            
            <div class="text-center text-sm text-gray-600">
                <a href="<?= BASE_URL ?>/auth/reset_password.php" class="text-sky-600 hover:text-sky-800">Forgot password?</a>
                <span class="mx-2">•</span>
                <a href="<?= BASE_URL ?>/auth/register.php" class="text-sky-600 hover:text-sky-800">Create account</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

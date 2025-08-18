<?php

require_once '../config/database.php';

$pageTitle = "Reset Password";
$step = isset($_GET['step']) ? $_GET['step'] : 'request';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 'request') {
        $email = trim($_POST['email'] ?? '');
        
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate token (in a real app, send this via email)
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token
            $stmt = $pdo->prepare("REPLACE INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$email, $token, $expires]);
            
            // In a real app, send email here with link like:
            // /auth/reset_password.php?step=verify&token=...&email=...
            $_SESSION['reset_token'] = $token;
            $_SESSION['reset_email'] = $email;
            
            header('Location: ' . BASE_URL . '/auth/reset_password.php?step=verify');
            exit;
        } else {
            $error = "No account found with that email";
        }
    } elseif ($step === 'verify') {
        $token = $_POST['token'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        // Validate
        if (strlen($password) < 6) {
            $error = "Password must be at least 6 characters";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match";
        } else {
            // Check token (in a real app, verify from database)
            if ($token === ($_SESSION['reset_token'] ?? '')) {
                // Update password
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                $stmt->execute([$hashed, $email]);
                
                // Clean up
                $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
                unset($_SESSION['reset_token'], $_SESSION['reset_email']);
                
                $_SESSION['success'] = "Password updated successfully";

                if (isset($_GET['account']) && $_GET['account'] == true) {
                    // header('Location: ' . BASE_URL . '/auth/account.php');
                } else {
                    header('Location: ' . BASE_URL . '/auth/login.php');
                    exit;
                }
            } else {
                $error = "Invalid or expired token";
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-md">
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-sky-800 mb-6 text-center">Reset Password</h1>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($step === 'request'): ?>
            <form method="post" class="space-y-4">
                <div>
                    <label class="block text-gray-700 mb-1">Email Address</label>
                    <input type="email" name="email" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded">
                </div>
                <button type="submit" class="w-full bg-sky-600 text-white py-2 px-4 rounded hover:bg-sky-700">
                    Send Reset Link
                </button>
            </form>
        <?php elseif ($step === 'verify'): ?>
            <form method="post" class="space-y-4">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['reset_token'] ?? ''); ?>">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['reset_email'] ?? ''); ?>">
                
                <div>
                    <label class="block text-gray-700 mb-1">New Password</label>
                    <input type="password" name="password" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded">
                </div>
                <div>
                    <label class="block text-gray-700 mb-1">Confirm Password</label>
                    <input type="password" name="confirm_password" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded">
                </div>
                <button type="submit" class="w-full bg-sky-600 text-white py-2 px-4 rounded hover:bg-sky-700">
                    Update Password
                </button>
            </form>
        <?php endif; ?>
        
        <div class="mt-4 text-center text-sm text-gray-600">
            <?php if(!isset($_GET['account'])): ?>
                <a href="<?= BASE_URL ?>/auth/login.php" class="text-sky-600 hover:text-sky-800">Back to Login</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
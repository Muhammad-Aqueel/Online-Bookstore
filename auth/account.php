<?php
require_once __DIR__ . '/../' . 'config/database.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/helpers.php';

// Redirect if user is not logged in
redirectIfNotLoggedIn();

$user = currentUser(); // Get current user's data
$pageTitle = "My Account";

// Handle form submission for profile updates (basic example)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic sanitization
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');

    try {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->execute([$firstName, $lastName, $phone, $address, $user['id']]);
        $_SESSION['success'] = "Profile updated successfully!";
        // Refresh user data after update
        $user = currentUser();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to update profile: " . $e->getMessage();
    }
    header('Location: account.php'); // Redirect to prevent form resubmission
    exit;
}

require_once BASE_PATH . '/includes/header.php'; // Include the header
?>

<div class="container mx-auto px-4 py-8 max-w-2xl">
    <h1 class="text-3xl font-bold text-sky-800 mb-6">My Account</h1>
    <div class="bg-white rounded-lg shadow p-6">
        <?php if ($user['role'] === 'seller'): ?>
                <div class="mb-4 border-b border-gray-200">
                    <div class="mb-2 flex justify-between items-center flex-wrap">
                        <h2 class="text-xl font-semibold text-gray-800 mb-2">Seller Profile</h2>
                        <a href="<?= BASE_URL ?>/seller/profile.php" class="px-4 py-2 rounded-md bg-sky-600 text-white hover:bg-gray-300 hover:text-gray-700 mb-2" target="_blank"><i class="fas fa-id-card-alt mr-2"></i>My Seller Profile</a>
                    </div>
                    <?php
                        $sellerStmt = $pdo->prepare("SELECT store_name, store_description FROM seller_profiles WHERE user_id = ?");
                        $sellerStmt->execute([$user['id']]);
                        $sellerProfile = $sellerStmt->fetch();
                    ?>
                    <p><strong>Store Name:</strong> <?php echo htmlspecialchars($sellerProfile['store_name'] ?? 'N/A'); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($sellerProfile['store_description'] ?? 'N/A'); ?></p>
                    <!-- Add form to edit seller profile details if needed -->
                </div>
        <?php endif; ?>
        <div class="mb-2 flex justify-between items-center flex-wrap">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Profile Information</h2>
            <a href="<?= BASE_URL ?>/auth/reset_password.php?step=verify&account=true" class="px-4 py-2 rounded-md bg-sky-600 text-white hover:bg-gray-300 hover:text-gray-700 mb-2" target="_blank"><i class="fas fa-key  mr-2"></i>Change Password</a>
        </div>
        <form method="post" class="space-y-4">
            <div>
                <label for="username" class="block text-gray-700 text-sm font-bold mb-1">Username:</label>
                <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 cursor-not-allowed" readonly>
            </div>
            <div>
                <label for="email" class="block text-gray-700 text-sm font-bold mb-1">Email:</label>
                <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 cursor-not-allowed" readonly>
            </div>
            <div>
                <label for="role" class="block text-gray-700 text-sm font-bold mb-1">Role:</label>
                <input type="text" id="role" value="<?php echo ucfirst(htmlspecialchars($user['role'])); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 cursor-not-allowed" readonly>
            </div>
            <div>
                <label for="first_name" class="block text-gray-700 text-sm font-bold mb-1">First Name:</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label for="last_name" class="block text-gray-700 text-sm font-bold mb-1">Last Name:</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label for="phone" class="block text-gray-700 text-sm font-bold mb-1">Phone:</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            <div>
                <label for="address" class="block text-gray-700 text-sm font-bold mb-1">Address:</label>
                <textarea id="address" name="address" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="bg-sky-600 text-white px-6 py-2 rounded-md hover:bg-sky-700 transition duration-300">
                Update Profile
            </button>
        </form>
    </div>
</div>

<?php
require_once BASE_PATH . '/includes/footer.php'; // Include the footer
?>
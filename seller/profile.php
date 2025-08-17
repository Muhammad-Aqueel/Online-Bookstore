<?php
require_once '../config/database.php';
require_once '../includes/auth.php'; // Provides requireAuth, currentUser
require_once '../includes/helpers.php'; // Provides sanitizeInput, generateCsrfToken, verifyCsrfToken

requireAuth('seller'); // Only sellers can access this page

$user = currentUser(); // Get the currently logged-in user (seller)
$pageTitle = "My Seller Profile";
$errors = [];
$successMessage = '';

// Fetch the seller's profile details
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.email, u.is_active, u.is_verified, 
           sp.store_name, sp.store_description, sp.logo, sp.payment_details, sp.kyc_verified
    FROM users u
    JOIN seller_profiles sp ON u.id = sp.user_id
    WHERE u.id = :user_id AND u.role = 'seller'
");
$stmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
$stmt->execute();
$sellerProfile = $stmt->fetch();

if (!$sellerProfile) {
    // This should ideally not happen if a seller is logged in and has a profile
    // But as a fallback, if no profile exists (e.g., database inconsistency)
    $_SESSION['error'] = "Your seller profile could not be loaded. Please contact support.";
    header('Location: ' . BASE_URL . '/seller/'); // Redirect to seller dashboard
    exit;
}

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token. Please try again.';
    } else {
        $storeName = sanitizeInput($_POST['store_name'] ?? '');
        $storeDescription = sanitizeInput($_POST['store_description'] ?? '');
        $paymentDetails = sanitizeInput($_POST['payment_details'] ?? '');

        // Basic validation
        if (empty($storeName)) {
            $errors[] = 'Store name is required.';
        }

        // Handle logo upload
        $logo = $sellerProfile['logo']; // Start with current logo
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $logoDir = __DIR__ . '/../assets/images/logos/';
            // Ensure the directory exists
            if (!is_dir($logoDir)) {
                mkdir($logoDir, 0777, true);
            }
            $logoExtension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $newLogoName = uniqid('logo_') . '.' . $logoExtension;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $logoDir . $newLogoName)) {
                // Delete old logo if different and not default/empty
                if ($logo && $logo !== $newLogoName && file_exists($logoDir . $logo)) {
                    unlink($logoDir . $logo);
                }
                $logo = $newLogoName;
            } else {
                $errors[] = 'Failed to upload new logo.';
            }
        } elseif (isset($_POST['remove_logo']) && $_POST['remove_logo'] == '1') {
            // If user checked 'remove logo' and there was a logo
            if ($logo && file_exists(__DIR__ . '/../assets/images/logos/' . $logo)) {
                unlink(__DIR__ . '/../assets/images/logos/' . $logo);
            }
            $logo = null; // Set logo to null in DB
        }

        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE seller_profiles SET store_name = :store_name, store_description = :store_description, logo = :logo, payment_details = :payment_details WHERE user_id = :user_id");
                
                $stmt->bindParam(':store_name', $storeName);
                $stmt->bindParam(':store_description', $storeDescription);
                $stmt->bindParam(':logo', $logo);
                $stmt->bindParam(':payment_details', $paymentDetails);
                $stmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
                $stmt->execute();

                $successMessage = "Your profile has been updated successfully!";
                // Re-fetch updated profile to show current data immediately
                $stmt = $pdo->prepare("
                    SELECT u.id, u.username, u.email, u.is_active, u.is_verified, 
                           sp.store_name, sp.store_description, sp.logo, sp.payment_details, sp.kyc_verified
                    FROM users u
                    JOIN seller_profiles sp ON u.id = sp.user_id
                    WHERE u.id = :user_id AND u.role = 'seller'
                ");
                $stmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
                $stmt->execute();
                $sellerProfile = $stmt->fetch();

            } catch (PDOException $e) {
                $errors[] = "Failed to update profile: " . $e->getMessage();
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-sky-800 mb-6 text-center">My Seller Profile</h1>
        
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

        <form method="post" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div>
                <label for="store_name" class="block text-gray-700 font-bold mb-1">Store Name <span class="text-red-500">*</span></label>
                <input type="text" id="store_name" name="store_name" value="<?php echo htmlspecialchars($_POST['store_name'] ?? $sellerProfile['store_name'] ?? ''); ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
            </div>
            <div>
                <label for="store_description" class="block text-gray-700 font-bold mb-1">Store Description</label>
                <textarea id="store_description" name="store_description" rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md"><?php echo htmlspecialchars($_POST['store_description'] ?? $sellerProfile['store_description'] ?? ''); ?></textarea>
            </div>
            <div>
                <label for="logo" class="block text-gray-700 font-bold mb-1">Store Logo</label>
                <?php if ($sellerProfile['logo']): ?>
                    <div class="mb-2">
                        <img src="<?= BASE_URL ?>/assets/images/logos/<?php echo htmlspecialchars($sellerProfile['logo']); ?>" alt="Current Logo" class="w-24 h-24 object-contain rounded-md">
                    </div>
                    <label class="flex items-center mb-2">
                        <input type="checkbox" name="remove_logo" value="1" class="form-checkbox h-4 w-4 text-red-600">
                        <span class="ml-2 text-sm text-gray-600">Remove current logo</span>
                    </label>
                <?php endif; ?>
                <input type="file" id="logo" name="logo" accept="image/*"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md">
                <p class="text-sm text-gray-500 mt-1">Upload a new logo image (JPG, PNG, GIF). Max 2MB.</p>
            </div>
            <div>
                <label for="payment_details" class="block text-gray-700 font-bold mb-1">Payment Details</label>
                <textarea id="payment_details" name="payment_details" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md"><?php echo htmlspecialchars($_POST['payment_details'] ?? $sellerProfile['payment_details'] ?? ''); ?></textarea>
                <p class="text-sm text-gray-500 mt-1">Provide details for receiving payouts (e.g., Bank Name, Account Number, PayPal Email).</p>
            </div>

            <!-- Display read-only status (controlled by admin) -->
            <div class="border-t border-gray-200 pt-4 mt-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Account Status</h3>
                <div class="flex items-center mb-2">
                    <span class="px-3 py-1 text-sm rounded-full 
                        <?php echo $sellerProfile['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        Account: <?php echo $sellerProfile['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                    <span class="ml-4 px-3 py-1 text-sm rounded-full 
                        <?php echo $sellerProfile['kyc_verified'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                        KYC Verification: <?php echo $sellerProfile['kyc_verified'] ? 'Verified' : 'Pending'; ?>
                    </span>
                </div>
                <p class="text-sm text-gray-500">
                    Your account activity and KYC status are managed by the administration.
                </p>
            </div>

            <button type="submit" name="update_profile" 
                    class="w-full bg-sky-600 text-white py-2 px-4 rounded-md hover:bg-sky-700 transition duration-300">
                Update Profile
            </button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
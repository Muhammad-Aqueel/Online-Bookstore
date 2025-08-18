<?php

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireAuth('admin');

$pageTitle = "Seller Profile Management";
$errors = [];
$sellerProfileToEdit = null;

// --- Handle Edit Seller Profile ---
if (isset($_POST['edit_seller_profile'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token. Please try again.';
    } else {
        $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
        $storeName = sanitizeInput($_POST['store_name'] ?? '');
        $storeDescription = sanitizeInput($_POST['store_description'] ?? '');
        $paymentDetails = sanitizeInput($_POST['payment_details'] ?? '');
        $kycVerified = isset($_POST['kyc_verified']) ? 1 : 0;

        if (!$userId) {
            $errors[] = 'Seller user ID is missing.';
        }
        if (empty($storeName)) {
            $errors[] = 'Store name is required.';
        }

        // Handle logo upload
        $logo = $_POST['current_logo'] ?? null; // Keep current logo by default
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $logoDir = __DIR__ . '/../assets/images/logos/';
            $logoExtension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $newLogoName = uniqid('logo_') . '.' . $logoExtension;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $logoDir . $newLogoName)) {
                // Delete old logo if different
                if ($logo && file_exists($logoDir . $logo) && $logo !== $newLogoName) {
                    unlink($logoDir . $logo);
                }
                $logo = $newLogoName;
            } else {
                $errors[] = 'Failed to upload new logo.';
            }
        } elseif (isset($_POST['remove_logo']) && $_POST['remove_logo'] == '1') {
            if ($logo && file_exists(__DIR__ . '/../assets/images/logos/' . $logo)) {
                unlink(__DIR__ . '/../assets/images/logos/' . $logo);
            }
            $logo = null;
        }

        if (empty($errors)) {
            try {
                // Check if seller_profile already exists, if not, insert (should exist from registration)
                $checkProfileStmt = $pdo->prepare("SELECT COUNT(*) FROM seller_profiles WHERE user_id = :user_id");
                $checkProfileStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $checkProfileStmt->execute();
                if ($checkProfileStmt->fetchColumn() > 0) {
                    $stmt = $pdo->prepare("UPDATE seller_profiles SET store_name = :store_name, store_description = :store_description, logo = :logo, payment_details = :payment_details, kyc_verified = :kyc_verified WHERE user_id = :user_id");
                } else {
                    $stmt = $pdo->prepare("INSERT INTO seller_profiles (user_id, store_name, store_description, logo, payment_details, kyc_verified) VALUES (:user_id, :store_name, :store_description, :logo, :payment_details, :kyc_verified)");
                }
                
                $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                $stmt->bindParam(':store_name', $storeName);
                $stmt->bindParam(':store_description', $storeDescription);
                $stmt->bindParam(':logo', $logo);
                $stmt->bindParam(':payment_details', $paymentDetails);
                $stmt->bindParam(':kyc_verified', $kycVerified, PDO::PARAM_BOOL);
                $stmt->execute();

                $_SESSION['success'] = "Seller profile updated successfully!";
                // Unset edit mode
                unset($_GET['edit_id']);
            } catch (PDOException $e) {
                $errors[] = "Failed to update seller profile: " . $e->getMessage();
            }
        }
    }
}


// --- Fetch all sellers for display ---
$sellers = $pdo->query("
    SELECT u.id, u.username, u.email, u.is_active, u.is_verified, sp.store_name, sp.store_description, sp.logo, sp.payment_details, sp.kyc_verified
    FROM users u
    JOIN seller_profiles sp ON u.id = sp.user_id
    WHERE u.role = 'seller'
    ORDER BY u.username
")->fetchAll();

// --- Fetch seller profile to edit if edit_id is present ---
if (isset($_GET['edit_id'])) {
    $editId = filter_var($_GET['edit_id'] ?? null, FILTER_VALIDATE_INT);
    if ($editId) {
        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.email, u.is_active, u.is_verified, sp.store_name, sp.store_description, sp.logo, sp.payment_details, sp.kyc_verified
            FROM users u
            JOIN seller_profiles sp ON u.id = sp.user_id
            WHERE u.id = :user_id AND u.role = 'seller'
        ");
        $stmt->bindParam(':user_id', $editId, PDO::PARAM_INT);
        $stmt->execute();
        $sellerProfileToEdit = $stmt->fetch();
        if (!$sellerProfileToEdit) {
            $errors[] = "Seller profile not found for editing.";
        }
    }
}

$finalerror = implode("<br>", $errors);
if ($errors) {
    $_SESSION['error'] = $finalerror;
}

include '../includes/header.php';
?>

<?php if ($sellerProfileToEdit): ?>
    <div class="container mx-auto px-4 pt-8 pb-6 max-w-2xl">
        <div class="bg-white rounded-lg shadow p-6">
            <h1 class="text-2xl text-center font-bold text-sky-800 mb-6">Seller Profile Management</h1>
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Name: <?php echo htmlspecialchars($sellerProfileToEdit['username']); ?></h2>
            <form method="post" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($sellerProfileToEdit['id']); ?>">
                <input type="hidden" name="current_logo" value="<?php echo htmlspecialchars($sellerProfileToEdit['logo'] ?? ''); ?>">

                <div>
                    <label for="store_name" class="block text-gray-700 font-bold mb-1">Store Name <span class="text-red-500">*</span></label>
                    <input type="text" id="store_name" name="store_name" value="<?php echo htmlspecialchars($_POST['store_name'] ?? $sellerProfileToEdit['store_name'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                <div>
                    <label for="store_description" class="block text-gray-700 font-bold mb-1">Store Description</label>
                    <textarea id="store_description" name="store_description" rows="4"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md"><?php echo htmlspecialchars($_POST['store_description'] ?? $sellerProfileToEdit['store_description'] ?? ''); ?></textarea>
                </div>
                <div>
                    <label for="logo" class="block text-gray-700 font-bold mb-1">Store Logo</label>
                    <?php if ($sellerProfileToEdit['logo']): ?>
                        <div class="mb-2">
                            <img src="<?= BASE_URL ?>/assets/images/logos/<?php echo htmlspecialchars($sellerProfileToEdit['logo']); ?>" alt="Current Logo" class="w-24 h-24 object-contain rounded-md">
                        </div>
                        <label class="flex items-center mb-2">
                            <input type="checkbox" name="remove_logo" value="1" class="form-checkbox">
                            <span class="ml-2 text-sm text-gray-600">Remove current logo</span>
                        </label>
                    <?php endif; ?>
                    <input type="file" id="logo" name="logo" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <p class="text-sm text-gray-500 mt-1">Upload a new logo image (JPG, PNG, GIF).</p>
                </div>
                <div>
                    <label for="payment_details" class="block text-gray-700 font-bold mb-1">Payment Details</label>
                    <textarea id="payment_details" name="payment_details" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md"><?php echo htmlspecialchars($_POST['payment_details'] ?? $sellerProfileToEdit['payment_details'] ?? ''); ?></textarea>
                    <p class="text-sm text-gray-500 mt-1">Bank account, PayPal, etc. (for payouts)</p>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="kyc_verified" name="kyc_verified" value="1" <?php echo $sellerProfileToEdit['kyc_verified'] ? 'checked' : ''; ?>
                           class="form-checkbox h-5 w-5 text-sky-600">
                    <label for="kyc_verified" class="ml-2 block text-gray-700">KYC Verified</label>
                </div>

                <button type="submit" name="edit_seller_profile" 
                        class="bg-sky-600 text-white py-2 px-4 rounded-md hover:bg-sky-700 transition duration-300">
                    Update Seller Profile
                </button>
                <a href="<?= BASE_URL ?>/admin/seller_profiles.php" class="inline-block px-4 py-2 rounded-md bg-gray-600 text-white hover:bg-gray-300 hover:text-gray-700 mb-2">Cancel Edit</a>
            </form>
        </div>
    </div>
<?php endif; ?>
<div class="container mx-auto px-4 pb-8 mt-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">All Seller Profiles</h2>
        <?php if (empty($sellers)): ?>
            <p class="text-gray-600">No seller profiles found.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="data-table min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Seller (Username)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Store Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">KYC Verified</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($sellers as $seller): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($seller['username']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($seller['store_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($seller['email']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        <?php echo $seller['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $seller['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                    <a href="<?= BASE_URL ?>/admin/users.php?toggle_status=<?php echo $seller['id']; ?>" class="text-xs bg-sky-600 text-white px-2 py-1 rounded hover:bg-sky-700" onclick="return confirm('Are you sure you want to toggle this user\'s status?')">Toggle</a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        <?php echo $seller['kyc_verified'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo $seller['kyc_verified'] ? 'Yes' : 'No'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                    <a href="<?= BASE_URL ?>/admin/seller_profiles.php?edit_id=<?php echo $seller['id']; ?>" class="text-sky-600 hover:text-sky-800 mr-2">Edit</a>
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

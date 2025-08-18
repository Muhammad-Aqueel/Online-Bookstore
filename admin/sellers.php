<?php
    
    require_once '../config/database.php';
    require_once '../includes/auth.php'; // Ensure auth functions are available
    require_once '../includes/helpers.php';

    requireAuth('admin');

    $pageTitle = "Seller Management";

    // Handle approval/rejection
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['approve'])) {
            $userId = (int)$_GET['approve'];
            // Set both is_approved and is_active to 1
            $pdo->prepare("UPDATE users SET is_approved = 1, is_active = 1 WHERE id = ?")->execute([$userId]);
            $_SESSION['success'] = "Seller approved and activated successfully";
            header('Location: ' . BASE_URL . '/admin/sellers.php');
            exit;
        }

        if (isset($_GET['reject'])) {
            $userId = (int)$_GET['reject'];
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            $_SESSION['success'] = "Seller rejected and removed";
            header('Location: ' . BASE_URL . '/admin/sellers.php');
            exit;
        }
    }

    // Get pending sellers
    $sellers = $pdo->query("
        SELECT u.*, s.store_name 
        FROM users u
        JOIN seller_profiles s ON u.id = s.user_id
        WHERE u.role = 'seller' AND u.is_approved = 0
        ORDER BY u.created_at DESC
    ")->fetchAll();

    // Handle user status changes
    if (isset($_GET['toggle_status'])) {
        $userId = $_GET['toggle_status'];
        $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$userId]);
        
        $_SESSION['success'] = "User status updated successfully";

        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (strpos($referer, 'seller_profiles.php') !== false) {
            header('Location: ' . BASE_URL . '/admin/seller_profiles.php');
        } else {
            header('Location: ' . BASE_URL . '/admin/sellers.php');
        }
        exit;
    }

    // Get all users with pagination
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $users = $pdo->query("
        SELECT * FROM users
        WHERE role = 'seller'
        ORDER BY created_at DESC
        LIMIT $limit OFFSET $offset
    ")->fetchAll();

    // Get total count for pagination
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalPages = ceil($totalUsers / $limit);

    include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-sky-800 mb-6">Seller Approvals</h1>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($sellers)): ?>
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <p class="text-gray-600">No pending seller approvals</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Seller</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Store</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Registered</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($sellers as $seller): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo htmlspecialchars($seller['username']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo htmlspecialchars($seller['store_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo htmlspecialchars($seller['email']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo date('M d, Y', strtotime($seller['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                    <a href="<?= BASE_URL ?>/admin/sellers.php?approve=<?php echo $seller['id']; ?>" 
                                    class="text-green-600 hover:text-green-800 mr-2">Approve</a>
                                    <a href="<?= BASE_URL ?>/admin/sellers.php?reject=<?php echo $seller['id']; ?>" 
                                    class="text-red-600 hover:text-red-800"
                                    onclick="return confirm('Reject this seller? This cannot be undone.')">Reject</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-sky-800 mb-6">Sellers Management</h1>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="data-table min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo $user['id']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['username']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-center"><?php echo htmlspecialchars($user['role']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="px-2 py-1 text-xs rounded-full <?php echo $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                            <a href="<?= BASE_URL ?>/admin/sellers.php?toggle_status=<?php echo $user['id']; ?>" class="text-xs bg-sky-600 text-white px-2 py-1 rounded hover:bg-sky-700" onclick="return confirm('Are you sure you want to toggle this user\'s status?')">Toggle</a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                            <a href="<?= BASE_URL ?>/admin/edit_user.php?id=<?php echo $user['id']; ?>" class="text-sky-600 hover:text-sky-800 mr-2" target="_blank">Edit</a>
                            <a href="<?= BASE_URL ?>/admin/delete_user.php?id=<?php echo $user['id']; ?>&csrf_token=<?php echo generateCsrfToken(); ?>"
                            onclick="return confirm('Are you sure you want to permanently delete this user and all their associated data? This cannot be undone.')"
                            class="text-red-600 hover:text-red-800 mr-2">Delete</a>
                            <a href="<?= BASE_URL ?>/admin/seller_profiles.php?edit_id=<?php echo $user['id']; ?>" class="text-sky-600 hover:text-sky-800" target="_blank">Profile</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-200">
            <div class="flex-1 flex justify-between sm:hidden">
                <a href="<?= BASE_URL ?>/admin/users.php?page=<?php echo max(1, $page - 1); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Previous
                </a>
                <a href="<?= BASE_URL ?>/admin/users.php?page=<?php echo min($totalPages, $page + 1); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Next
                </a>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $limit, $totalUsers); ?></span> of <span class="font-medium"><?php echo $totalUsers; ?></span> results
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <a href="<?= BASE_URL ?>/admin/users.php?page=<?php echo max(1, $page - 1); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Previous</span>
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="<?= BASE_URL ?>/admin/users.php?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'bg-sky-50 border-sky-500 text-sky-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium z-10">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <a href="<?= BASE_URL ?>/admin/users.php?page=<?php echo min($totalPages, $page + 1); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Next</span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
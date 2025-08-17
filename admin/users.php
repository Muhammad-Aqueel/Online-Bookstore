<?php
    require_once '../config/database.php';
    require_once '../includes/auth.php';
    require_once '../includes/helpers.php'; // Provides sanitizeInput, generateCsrfToken, verifyCsrfToken

    requireAuth('admin');

    $pageTitle = "User Management";

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
            header('Location: ' . BASE_URL . '/admin/users.php');
        }
        exit;
    }

    // Handle role changes
    if (isset($_POST['update_role'])) {
        $userId = $_POST['user_id'];
        $newRole = $_POST['role'];
        
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$newRole, $userId]);
        
        $_SESSION['success'] = "User role updated successfully";
        header('Location: ' . BASE_URL . '/admin/users.php');
        exit;
    }

    // Get all users with pagination
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $users = $pdo->query("
        SELECT * FROM users
        ORDER BY created_at DESC
        LIMIT $limit OFFSET $offset
    ")->fetchAll();

    // Get total count for pagination
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalPages = ceil($totalUsers / $limit);

    include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-sky-800 mb-6">User Management</h1>

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
                        <td class="px-6 py-4 whitespace-nowrap">
                            <form method="post" class="flex items-center">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <?php if (currentUser()['id'] != $user['id']): // Prevent admin from change themselves ?>
                                    <select name="role" class="mr-2 border rounded px-2 py-1 text-sm">
                                        <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        <option value="seller" <?php echo $user['role'] == 'seller' ? 'selected' : ''; ?>>Seller</option>
                                        <option value="buyer" <?php echo $user['role'] == 'buyer' ? 'selected' : ''; ?>>Buyer</option>
                                    </select>
                                    <button type="submit" name="update_role" class="text-xs bg-sky-600 text-white px-2 py-1 rounded hover:bg-sky-700">Update</button>
                                <?php else: ?>
                                    <p class="text-xs text-black ml-5">Admin</p>
                                <?php endif; ?>
                            </form>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="px-2 py-1 text-xs rounded-full <?php echo $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                            <?php if (currentUser()['id'] != $user['id']): // Prevent admin from deactivate themselves ?>
                                <a href="<?= BASE_URL ?>/admin/users.php?toggle_status=<?php echo $user['id']; ?>" class="text-xs bg-sky-600 text-white px-2 py-1 rounded hover:bg-sky-700" onclick="return confirm('Are you sure you want to toggle this user\'s active status?')">Toggle</a>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <?php if (currentUser()['id'] != $user['id']): // Prevent admin from edit and delete themselves ?>
                                <a href="<?= BASE_URL ?>/admin/edit_user.php?id=<?php echo $user['id']; ?>" class="text-sky-600 hover:text-sky-800 mr-2" target="_blank">Edit</a>
                                <a href="<?= BASE_URL ?>/admin/delete_user.php?id=<?php echo $user['id']; ?>&csrf_token=<?php echo generateCsrfToken(); ?>"
                                onclick="return confirm('Are you sure you want to permanently delete this user and all their associated data? This cannot be undone.')"
                                class="text-red-600 hover:text-red-800 mr-2">Delete</a>
                                <?php if ($user['role'] == 'seller'): // Seller Profile ?>
                                    <a href="<?= BASE_URL ?>/admin/seller_profiles.php?edit_id=<?php echo $user['id']; ?>" class="text-sky-600 hover:text-sky-800" target="_blank">Profile</a>
                            <?php endif; endif; ?>
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
<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireAuth('admin');

$pageTitle = "Order Management";

// Handle status updates
if (isset($_POST['update_status'])) {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $orderId]);
    
    $_SESSION['success'] = "Order status updated successfully";
    header('Location: ' . BASE_URL . '/admin/orders.php');
    exit;
}

// Get all orders with pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$orders = $pdo->query("
    SELECT o.*, u.username as buyer_name 
    FROM orders o
    JOIN users u ON o.buyer_id = u.id
    ORDER BY o.order_date DESC
    LIMIT $limit OFFSET $offset
")->fetchAll();

// Get total count for pagination
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalPages = ceil($totalOrders / $limit);

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-sky-800 mb-6">Order Management</h1>

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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Buyer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">#<?php echo $order['id']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">$<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    <?php echo $order['payment_status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                        ($order['payment_status'] === 'failed' ? 'bg-red-100 text-red-800' : 
                                        ($order['payment_status'] === 'refunded' ? 'bg-gray-100 text-gray-800' : 
                                        'bg-yellow-100 text-yellow-800')); ?>">
                                    <?php echo ucfirst(htmlspecialchars($order['payment_status'])); ?>
                                </span>
                            </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <form method="post" class="flex items-center">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <select name="status" class="<?php 
                                    if ($order['status'] === 'pending') {
                                        echo 'bg-yellow-100 text-yellow-800 border border-yellow-300';
                                    } else if ($order['status'] === 'processing') {
                                        echo 'bg-purple-100 text-purple-800 border border-purple-300';
                                    } else if ($order['status'] === 'shipped') {
                                        echo 'bg-blue-100 text-blue-800 border border-blue-300';
                                    } else if ($order['status'] === 'delivered') {
                                        echo 'bg-green-100 text-green-800 border border-green-300';
                                    } else if ($order['status'] === 'cancelled') {
                                        echo 'bg-red-100 text-red-800 border border-red-300';
                                    }                      
                                ?> mr-2 border rounded px-2 py-1 text-sm">
                                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?> class="bg-yellow-100 text-yellow-800">Pending</option>
                                    <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?> class="bg-purple-100 text-purple-800">Processing</option>
                                    <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?> class="bg-blue-100 text-blue-800">Shipped</option>
                                    <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?> class="bg-green-100 text-green-800">Delivered</option>
                                    <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?> class="bg-red-100 text-red-800">Cancelled</option>
                                </select>
                                <button type="submit" name="update_status" class="text-xs bg-sky-600 text-white px-2 py-1 rounded hover:bg-sky-700">Update</button>
                            </form>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <a href="<?= BASE_URL ?>/admin/order_details.php?id=<?php echo $order['id']; ?>" class="text-sky-600 hover:text-sky-800">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-200">
            <div class="flex-1 flex justify-between sm:hidden">
                <a href="<?= BASE_URL ?>/admin/orders.php?page=<?php echo max(1, $page - 1); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Previous
                </a>
                <a href="<?= BASE_URL ?>/admin/orders.php?page=<?php echo min($totalPages, $page + 1); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Next
                </a>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $limit, $totalOrders); ?></span> of <span class="font-medium"><?php echo $totalOrders; ?></span> results
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <a href="<?= BASE_URL ?>/admin/orders.php?page=<?php echo max(1, $page - 1); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <span class="sr-only">Previous</span>
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="<?= BASE_URL ?>/admin/orders.php?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'bg-sky-50 border-sky-500 text-sky-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium z-10">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <a href="<?= BASE_URL ?>/admin/orders.php?page=<?php echo min($totalPages, $page + 1); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
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
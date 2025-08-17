<?php
// Start session at the very beginning of the script
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../includes/auth.php'; // Provides requireAuth, currentUser, isLoggedIn, hasRole
require_once '../includes/helpers.php'; // Provides sanitizeInput, generateCsrfToken, verifyCsrfToken

requireAuth('buyer');

$user = currentUser();
$pageTitle = "My Orders";

// Pagination setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get orders with pagination
$stmt = $pdo->prepare("
    SELECT o.*, COUNT(oi.id) as item_count
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.buyer_id = :buyer_id -- Changed to named parameter
    GROUP BY o.id
    ORDER BY o.order_date DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindParam(':buyer_id', $user['id'], PDO::PARAM_INT); // Bind buyer_id
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute(); // No array needed here since all parameters are bound via bindParam

$orders = $stmt->fetchAll();

// Get total count for pagination
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE buyer_id = :buyer_id"); // Changed to named parameter
$totalStmt->bindParam(':buyer_id', $user['id'], PDO::PARAM_INT); // Bind buyer_id
$totalStmt->execute(); // No array needed here
$totalOrders = $totalStmt->fetchColumn();
$totalPages = ceil($totalOrders / $limit);

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-sky-800 mb-6">My Orders</h1>

    <?php if (empty($orders)): ?>
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <p class="text-gray-600 mb-4">You haven't placed any orders yet.</p>
            <a href="<?= BASE_URL ?>/buyer/" class="bg-sky-600 text-white px-4 py-2 rounded hover:bg-sky-700">Browse Books</a>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">#<?php echo htmlspecialchars($order['id']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['item_count']); ?></td>
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
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-2 py-1 text-xs rounded-full
                                    <?php echo $order['status'] === 'delivered' ? 'bg-green-100 text-green-800' :
                                        ($order['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : (($order['status'] === 'pending') ? 'bg-yellow-100 text-yellow-800' : ($order['status'] === 'processing' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'))) ?>">
                                    <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                <a href="<?= BASE_URL ?>/buyer/order_details.php?id=<?php echo $order['id']; ?>" class="text-sky-600 hover:text-sky-800 mr-2">View</a>
                                <?php if ($order['status'] === 'pending' || $order['status'] === 'processing'): ?>
                                    <a href="<?= BASE_URL ?>/buyer/cancel_order.php?id=<?php echo $order['id']; ?>"
                                    onclick="return confirm('Are you sure you want to cancel this order?')"
                                    class="text-red-600 hover:text-red-800">Cancel</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-200">
                <div class="flex-1 flex justify-between sm:hidden">
                    <a href="<?= BASE_URL ?>/buyer/orders.php?page=<?php echo max(1, $page - 1); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Previous
                    </a>
                    <a href="<?= BASE_URL ?>/buyer/orders.php?page=<?php echo min($totalPages, $page + 1); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
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
                            <a href="<?= BASE_URL ?>/buyer/orders.php?page=<?php echo max(1, $page - 1); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Previous</span>
                                <i class="fas fa-chevron-left"></i>
                            </a>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="<?= BASE_URL ?>/buyer/orders.php?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'bg-sky-50 border-sky-500 text-sky-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium z-10">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <a href="<?= BASE_URL ?>/buyer/orders.php?page=<?php echo min($totalPages, $page + 1); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Next</span>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

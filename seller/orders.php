<?php
// Start session at the very beginning of the script
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../includes/auth.php'; // Provides requireAuth, currentUser, isLoggedIn, hasRole
require_once '../includes/helpers.php'; // Provides sanitizeInput, generateCsrfToken, verifyCsrfToken

requireAuth('seller');

$user = currentUser();
$pageTitle = "My Sales Orders";

// Pagination setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Fetch orders that contain items from this seller
// We need to join through order_items and books tables
$stmt = $pdo->prepare("
    SELECT DISTINCT o.id, o.order_date, o.total_amount, o.payment_status, o.status, u.username as buyer_name
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN books b ON oi.book_id = b.id
    JOIN users u ON o.buyer_id = u.id
    WHERE b.seller_id = :seller_id -- Changed to named parameter
    ORDER BY o.order_date DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindParam(':seller_id', $user['id'], PDO::PARAM_INT); // Bind seller_id
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT); // Bind limit as INT
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT); // Bind offset as INT
$stmt->execute(); // Execute without an array as all parameters are bound via bindParam
$orders = $stmt->fetchAll();

// Get total count of relevant orders for pagination
$totalStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT o.id)
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN books b ON oi.book_id = b.id
    WHERE b.seller_id = :seller_id_total -- Changed to named parameter
");
$totalStmt->bindParam(':seller_id_total', $user['id'], PDO::PARAM_INT); // Bind seller_id as INT
$totalStmt->execute(); // Execute without an array
$totalOrders = $totalStmt->fetchColumn();
$totalPages = ceil($totalOrders / $limit);

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-sky-800 mb-6">My Sales Orders</h1>

    <?php if (empty($orders)): ?>
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <p class="text-gray-600 mb-4">You have no sales orders yet.</p>
            <a href="<?= BASE_URL ?>/seller/add_book.php" class="bg-sky-600 text-white px-4 py-2 rounded hover:bg-sky-700">Add New Book</a>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Buyer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order Date</th>
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
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    <?php echo $order['status'] === 'delivered' ? 'bg-green-100 text-green-800' : 
                                        ($order['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : (($order['status'] === 'pending') ? 'bg-yellow-100 text-yellow-800' : ($order['status'] === 'processing' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'))) ?>">
                                    <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                <a href="<?= BASE_URL ?>/seller/order_details.php?id=<?php echo $order['id']; ?>" class="text-sky-600 hover:text-sky-800">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-200">
                <div class="flex-1 flex justify-between sm:hidden">
                    <a href="<?= BASE_URL ?>/seller/orders.php?page=<?php echo max(1, $page - 1); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Previous
                    </a>
                    <a href="<?= BASE_URL ?>/seller/orders.php?page=<?php echo min($totalPages, $page + 1); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
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
                            <a href="<?= BASE_URL ?>/seller/orders.php?page=<?php echo max(1, $page - 1); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Previous</span>
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="<?= BASE_URL ?>/seller/orders.php?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'bg-sky-50 border-sky-500 text-sky-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium z-10">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <a href="<?= BASE_URL ?>/seller/orders.php?page=<?php echo min($totalPages, $page + 1); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
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

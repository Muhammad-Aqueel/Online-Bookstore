<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireAuth('seller');

$user = currentUser();
$pageTitle = "Seller Dashboard";

// Get seller stats
$booksCount = $pdo->prepare("SELECT COUNT(*) FROM books WHERE seller_id = ?");
$booksCount->execute([$user['id']]);
$booksCount = $booksCount->fetchColumn();

$ordersCount = $pdo->prepare("SELECT COUNT(DISTINCT o.id) 
                            FROM orders o
                            JOIN order_items oi ON o.id = oi.order_id
                            JOIN books b ON oi.book_id = b.id
                            WHERE b.seller_id = ?");
$ordersCount->execute([$user['id']]);
$ordersCount = $ordersCount->fetchColumn();

$revenue = $pdo->prepare("SELECT SUM(oi.price * oi.quantity) 
                         FROM order_items oi
                         JOIN books b ON oi.book_id = b.id
                         JOIN orders o ON oi.order_id = o.id
                         WHERE b.seller_id = ? AND o.payment_status = 'completed' AND (status = 'shipped' OR status = 'delivered')");
$revenue->execute([$user['id']]);
$revenue = $revenue->fetchColumn();

// Recent orders
$recentOrders = $pdo->prepare("SELECT o.*, u.username as buyer_name 
                              FROM orders o
                              JOIN order_items oi ON o.id = oi.order_id
                              JOIN books b ON oi.book_id = b.id
                              JOIN users u ON o.buyer_id = u.id
                              WHERE b.seller_id = ?
                              GROUP BY o.id
                              ORDER BY o.order_date DESC
                              LIMIT 5");
$recentOrders->execute([$user['id']]);
$recentOrders = $recentOrders->fetchAll();

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-sky-800 mb-6">Seller Dashboard</h1>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white p-4 rounded-lg shadow">
            <h3 class="text-gray-500">Your Books</h3>
            <p class="text-2xl font-bold text-sky-600"><?php echo $booksCount; ?></p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <h3 class="text-gray-500">Total Orders</h3>
            <p class="text-2xl font-bold text-sky-600"><?php echo $ordersCount; ?></p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <h3 class="text-gray-500">Total Revenue</h3>
            <p class="text-2xl font-bold text-sky-600">$<?php echo number_format($revenue, 2); ?></p>
        </div>
    </div>
    
    <!-- Recent Orders -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-sky-800">Recent Orders</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Buyer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="<?= BASE_URL ?>/seller/order_details.php?id=<?php echo $order['id']; ?>" class="text-sky-600 hover:text-sky-800">
                                #<?php echo $order['id']; ?>
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">$<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full 
                                <?php echo $order['status'] === 'delivered' ? 'bg-green-100 text-green-800' : 
                                       ($order['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800') ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
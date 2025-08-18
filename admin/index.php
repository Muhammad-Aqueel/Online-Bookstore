<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireAuth('admin');

$pageTitle = "Admin Dashboard";

// Get stats for dashboard
$usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$sellersCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'seller'")->fetchColumn();
$buyersCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'buyer'")->fetchColumn();
$booksCount = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$ordersCount = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

// Recent pending approvals
$pendingBooks = $pdo->query("SELECT b.*, u.username as seller_name 
                            FROM books b 
                            JOIN users u ON b.seller_id = u.id 
                            WHERE b.approved = 0 
                            ORDER BY b.created_at DESC 
                            LIMIT 5")->fetchAll();

// Recent orders
$recentOrders = $pdo->query("SELECT o.*, u.username as buyer_name 
                            FROM orders o 
                            JOIN users u ON o.buyer_id = u.id 
                            ORDER BY o.order_date DESC 
                            LIMIT 5")->fetchAll();

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-sky-800 mb-6">Admin Dashboard</h1>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="bg-white p-4 rounded-lg shadow">
            <h3 class="text-gray-500">Total Users</h3>
            <p class="text-2xl font-bold text-sky-600"><?php echo $usersCount; ?></p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <h3 class="text-gray-500">Sellers</h3>
            <p class="text-2xl font-bold text-sky-600"><?php echo $sellersCount; ?></p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <h3 class="text-gray-500">Buyers</h3>
            <p class="text-2xl font-bold text-sky-600"><?php echo $buyersCount; ?></p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <h3 class="text-gray-500">Books</h3>
            <p class="text-2xl font-bold text-sky-600"><?php echo $booksCount; ?></p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <h3 class="text-gray-500">Orders</h3>
            <p class="text-2xl font-bold text-sky-600"><?php echo $ordersCount; ?></p>
        </div>
    </div>
    
    <!-- Pending Approvals -->
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-sky-800">Pending Book Approvals</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="data-table min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Author</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Seller</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($pendingBooks as $book): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($book['title']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($book['author']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($book['seller_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">$<?php echo number_format($book['price'], 2); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <a href="<?= BASE_URL ?>/admin/approve_book.php?id=<?php echo $book['id']; ?>" class="text-green-600 hover:text-green-800 mr-2">Approve</a>
                            <a href="<?= BASE_URL ?>/admin/reject_book.php?id=<?php echo $book['id']; ?>" class="text-red-600 hover:text-red-800">Reject</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
                            <a href="<?= BASE_URL ?>/admin/order_details.php?id=<?php echo $order['id']; ?>" class="text-sky-600 hover:text-sky-800"><?php echo $order['id']; ?></a>
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
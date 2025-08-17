<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php'; // For sanitizeInput if needed elsewhere

requireAuth('buyer');

$user = currentUser();
$pageTitle = "Order Details";
$orderId = $_GET['id'] ?? null;

if (!$orderId) {
    header('Location: ' . BASE_URL . '/buyer/orders.php');
    exit;
}

// Fetch order details, ensuring it belongs to the current user
$orderStmt = $pdo->prepare("
    SELECT o.*, u.username as buyer_username
    FROM orders o
    JOIN users u ON o.buyer_id = u.id
    WHERE o.id = ? AND o.buyer_id = ?
");
$orderStmt->execute([$orderId, $user['id']]);
$order = $orderStmt->fetch();

if (!$order) {
    $_SESSION['error'] = "Order not found or you don't have permission to view its details.";
    header('Location: ' . BASE_URL . '/buyer/orders.php');
    exit;
}

// Fetch all items for this order
$itemsStmt = $pdo->prepare("
    SELECT oi.*, b.title, b.author, b.cover_image, b.isbn, b.is_physical, b.is_digital
    FROM order_items oi
    JOIN books b ON oi.book_id = b.id
    WHERE oi.order_id = ?
");
$itemsStmt->execute([$orderId]);
$orderItems = $itemsStmt->fetchAll();

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-sky-800 mb-6">Order #<?php echo htmlspecialchars($order['id']); ?></h1>

    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Order Summary</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">
            <div>
                <p><strong>Order Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></p>
                <p><strong>Total Amount:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
                <p><strong>Payment Method:</strong> <?php echo htmlspecialchars(ucfirst($order['payment_method'])); ?></p>
                <p><strong>Payment Status:</strong> 
                    <span class="px-2 py-1 text-xs rounded-full 
                        <?php 
                            if ($order['payment_status'] === 'completed') {
                                echo 'bg-green-100 text-green-800';
                            } else if ($order['payment_status'] === 'failed') {
                                echo 'bg-red-100 text-red-800';
                            } else if ($order['payment_status'] === 'refunded') {
                                echo 'bg-gray-300 text-gray-800';
                            } else { // pending
                                echo 'bg-yellow-100 text-yellow-800';
                            }
                        ?>">
                        <?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?>
                    </span>
                </p>
            </div>
            <div>
                <p><strong>Current Status:</strong> 
                    <span class="px-2 py-1 text-xs rounded-full 
                        <?php 
                            if ($order['status'] === 'delivered') echo 'bg-green-100 text-green-800';
                            else if ($order['status'] === 'cancelled') echo 'bg-red-100 text-red-800';
                            else if ($order['status'] === 'pending') echo 'bg-yellow-100 text-yellow-800';
                            else if ($order['status'] === 'processing') echo 'bg-purple-100 text-purple-800';
                            else echo 'bg-blue-100 text-blue-800'; // processing, shipped
                        ?>">
                        <?php echo htmlspecialchars(ucfirst($order['status'])); ?>
                    </span>
                </p>
                <?php if ($order['status'] === 'pending' || $order['status'] === 'processing'): ?>
                    <div class="mt-4">
                        <a href="<?= BASE_URL ?>/buyer/cancel_order.php?id=<?php echo $order['id']; ?>" 
                           onclick="return confirm('Are you sure you want to cancel this order? This action cannot be undone for shipped items.');"
                           class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600 transition duration-300">
                            Cancel Order
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="mt-4">
            <h3 class="font-semibold mb-2">Shipping Address:</h3>
            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Items in This Order</h2>
        <?php if (empty($orderItems)): ?>
            <p class="text-gray-600">No items found for this order.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="data-table min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price/Item</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Format</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Download</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orderItems as $item): ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-16 w-16 bg-gray-200 rounded-md overflow-hidden">
                                            <?php if ($item['cover_image']): ?>
                                                <img src="<?= BASE_URL ?>/assets/images/books/<?php echo htmlspecialchars($item['cover_image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="h-full w-full object-cover">
                                            <?php else: ?>
                                                <div class="h-full w-full flex items-center justify-center text-gray-400">
                                                    <i class="fas fa-book text-xl"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <a href="<?= BASE_URL ?>/buyer/book.php?id=<?php echo htmlspecialchars($item['book_id']); ?>" class="text-sm font-medium text-sky-600 hover:text-sky-800">
                                                <?php echo htmlspecialchars($item['title']); ?>
                                            </a>
                                            <p class="text-sm text-gray-500">by <?php echo htmlspecialchars($item['author']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$<?php echo number_format($item['price'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php 
                                        $formats = [];
                                        if ($item['is_physical']) $formats[] = 'Physical';
                                        if ($item['is_digital']) $formats[] = 'Digital';
                                        echo implode(' + ', $formats);
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <?php if ($item['is_digital'] && $order['payment_status'] === 'completed'): ?>
                                        <a href="<?= BASE_URL ?>/buyer/download.php?id=<?php echo htmlspecialchars($item['id']); ?>&orderid=<?= $orderId ?>" class="text-green-600 hover:text-green-800 flex items-center">
                                            <i class="fas fa-download mr-1"></i> Download
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="mt-8 text-center">
        <a href="<?= BASE_URL ?>/buyer/orders.php" class="bg-gray-200 text-gray-700 px-6 py-3 rounded-md hover:bg-gray-300 transition duration-300">
            Back to My Orders
        </a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

requireAuth('buyer');

$user = currentUser();
$pageTitle = "Order Confirmation";
$orderId = $_GET['id'] ?? null;

if (!$orderId) {
    // If no order ID is provided, redirect to the buyer's order history
    header('Location: ' . BASE_URL . '/buyer/orders.php');
    exit;
}

// Fetch order details for the confirmation
$orderStmt = $pdo->prepare("
    SELECT o.*, u.username as buyer_username
    FROM orders o
    JOIN users u ON o.buyer_id = u.id
    WHERE o.id = ? AND o.buyer_id = ?
");
$orderStmt->execute([$orderId, $user['id']]);
$order = $orderStmt->fetch();

if (!$order) {
    // If order not found or doesn't belong to the user, redirect
    $_SESSION['error'] = "Order not found or you don't have permission to view this order.";
    header('Location: ' . BASE_URL . '/buyer/orders.php');
    exit;
}

// Fetch all items for this order
$itemsStmt = $pdo->prepare("
    SELECT oi.*, b.title, b.author, b.cover_image, b.is_physical, b.is_digital
    FROM order_items oi
    JOIN books b ON oi.book_id = b.id
    WHERE oi.order_id = ?
");
$itemsStmt->execute([$orderId]);
$orderItems = $itemsStmt->fetchAll();

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-8 text-center">
        <div class="flex flex-col items-center justify-center">
            <i class="fas fa-check-circle text-green-500 text-6xl mb-4"></i>
            <h1 class="text-3xl font-bold text-green-700 mb-2">Order Confirmed!</h1>
            <p class="text-gray-700 text-lg mb-6">Thank you for your purchase, <?php echo htmlspecialchars($user['username']); ?>!</p>
        </div>

        <div class="border-t border-b border-gray-200 py-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Order Details</h2>
            <p class="text-gray-700"><strong>Order #<?php echo htmlspecialchars($order['id']); ?></strong></p>
            <p class="text-gray-700"><strong>Order Date:</strong> <?php echo date('M d, Y', strtotime($order['order_date'])); ?></p>
            <p class="text-gray-700"><strong>Total Amount:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
            <p class="text-gray-700"><strong>Payment Method:</strong> <?php echo htmlspecialchars(ucfirst($order['payment_method'])); ?></p>
            <p class="text-gray-700 mt-4"><strong>Shipping Address:</strong><br><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
        </div>

        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Items Ordered</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($orderItems as $item): ?>
                    <div class="flex items-center bg-gray-50 p-3 rounded-lg shadow-sm">
                        <div class="flex-shrink-0 h-20 w-20 bg-gray-200 rounded-md overflow-hidden">
                            <?php if ($item['cover_image']): ?>
                                <img src="<?= BASE_URL ?>/assets/images/books/<?php echo htmlspecialchars($item['cover_image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="h-full w-full object-cover">
                            <?php else: ?>
                                <div class="h-full w-full flex items-center justify-center text-gray-400">
                                    <i class="fas fa-book text-xl"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="ml-4 text-left">
                            <h3 class="text-md font-medium text-gray-900"><?php echo htmlspecialchars($item['title']); ?></h3>
                            <p class="text-sm text-gray-600">by <?php echo htmlspecialchars($item['author']); ?></p>
                            <p class="text-sm text-gray-600">Qty: <?php echo htmlspecialchars($item['quantity']); ?></p>
                            <p class="text-md font-bold text-sky-600">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                            <span class="text-xs text-gray-500">
                                <?php echo ($item['is_digital'] && !$item['is_physical']) ? 'eBook' : ($item['is_physical'] && !$item['is_digital'] ? 'Physical Book' : 'Physical & eBook'); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-4">
            <a href="<?= BASE_URL ?>/buyer/orders.php" class="bg-sky-600 text-white px-6 py-3 rounded-md hover:bg-sky-700 transition duration-300 flex items-center justify-center">
                <i class="fas fa-receipt mr-2"></i> View All Orders
            </a>
            <a href="<?= BASE_URL ?>/buyer/" class="bg-gray-200 text-gray-700 px-6 py-3 rounded-md hover:bg-gray-300 transition duration-300 flex items-center justify-center">
                <i class="fas fa-shopping-bag mr-2"></i> Continue Shopping
            </a>
            <?php 
            // Check if there are any digital items in the order
            $hasDigitalItems = false;
            foreach ($orderItems as $item) {
                if ($item['is_digital'] == 1) {
                    $hasDigitalItems = true;
                    break;
                }
            }
            if ($hasDigitalItems): 
            ?>
            <a href="<?= BASE_URL ?>/buyer/library.php" class="bg-green-600 text-white px-6 py-3 rounded-md hover:bg-green-700 transition duration-300 flex items-center justify-center">
                <i class="fas fa-book-reader mr-2"></i> Go to My Digital Library
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

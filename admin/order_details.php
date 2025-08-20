<?php

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireAuth('admin');

$pageTitle = "Order Details (Admin)";
$orderId = $_GET['id'] ?? null;

if (!$orderId) {
    header('Location: ' . BASE_URL . '/admin/orders.php');
    exit;
}

// --- Fetch order details ---
$orderStmt = $pdo->prepare("
    SELECT o.*, u.username as buyer_username, u.email as buyer_email, u.phone as buyer_phone
    FROM orders o
    JOIN users u ON o.buyer_id = u.id
    WHERE o.id = ?
");
$orderStmt->execute([$orderId]);
$order = $orderStmt->fetch();

if (!$order) {
    $_SESSION['error'] = "Order not found.";
    header('Location: ' . BASE_URL . '/admin/orders.php');
    exit;
}

// --- Fetch all items in this order ---
$itemsStmt = $pdo->prepare("
    SELECT oi.*, b.id as bookid, b.title, b.author, b.cover_image, b.isbn, b.is_physical, b.is_digital, sp.store_name
    FROM order_items oi
    JOIN books b ON oi.book_id = b.id
    JOIN seller_profiles sp ON b.seller_id = sp.user_id
    WHERE oi.order_id = ?
");
$itemsStmt->execute([$orderId]);
$orderItems = $itemsStmt->fetchAll();

// --- Handle order status update ---
if (isset($_POST['update_status'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid CSRF token. Please try again.';
        header('Location: ' . BASE_URL . '/admin/order_details.php?id=' . $orderId);
        exit;
    }

    $newStatus = sanitizeInput($_POST['status']);
    $allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

    if (!in_array($newStatus, $allowedStatuses)) {
        $_SESSION['error'] = "Invalid status value provided.";
    } else {
        try {
            $updateStmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $updateStmt->execute([$newStatus, $orderId]);
            $_SESSION['success'] = "Order status updated successfully.";
            $order['status'] = $newStatus;
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to update order status: " . $e->getMessage();
        }
    }
    header('Location: ' . BASE_URL . '/admin/order_details.php?id=' . $orderId);
    exit;
}

// Handle order payment_status update
if (isset($_POST['update_payment_status'])) {
    // CSRF Token validation
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid CSRF token. Please try again.';
        header('Location: ' . BASE_URL . '/admin/order_details.php?id=' . $orderId);
        exit;
    }

    $newStatus = sanitizeInput($_POST['payment_status']);

    // Validate payment status value (optional, but good practice)
    $allowedStatuses = ['pending', 'completed', 'failed', 'refunded'];
    if (!in_array($newStatus, $allowedStatuses)) {
        $_SESSION['error'] = "Invalid payment status value provided.";
    } else {
        try {
            $updateStmt = $pdo->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
            $updateStmt->execute([$newStatus, $orderId]);
            $_SESSION['success'] = "Order payment status updated to '" . htmlspecialchars($newStatus) . "' successfully.";
            // Refresh order data
            $order['payment_status'] = $newStatus;
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to update order payment status: " . $e->getMessage();
        }
    }
    header('Location: ' . BASE_URL . '/admin/order_details.php?id=' . $orderId);
    exit;
}

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-sky-800 mb-6">Order #<?php echo htmlspecialchars($order['id']); ?></h1>

    <!-- Order Summary -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Order Summary</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">
            <div>
                <p><strong>Order Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></p>
                <p><strong>Total Amount:</strong> $<?php echo number_format($order['total_amount'] + $order['discount_amount'], 2); ?></p>
                <p><strong>Discount:</strong> $<?php echo number_format($order['discount_amount'], 2); ?></p>
                <p><strong>Final Amount:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
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
                <h3 class="font-semibold mt-2">Update Payment Status:</h3>
                <form method="post" class="flex items-center mt-2">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <select name="payment_status" class="border rounded px-3 py-2 text-sm mr-2">
                        <option value="pending" <?php echo $order['payment_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $order['payment_status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="failed" <?php echo $order['payment_status'] == 'failed' ? 'selected' : ''; ?>>Failed</option>
                        <option value="refunded" <?php echo $order['payment_status'] == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                    </select>
                    <button type="submit" name="update_payment_status" class="bg-sky-600 text-white px-4 py-2 rounded-md hover:bg-sky-700">Update</button>
                </form>
            </div>
            <div>
                <p><strong>Current Status:</strong> 
                    <span class="px-2 py-1 text-xs rounded-full 
                        <?php 
                            if ($order['status'] === 'delivered') echo 'bg-green-100 text-green-800';
                            else if ($order['status'] === 'cancelled') echo 'bg-red-100 text-red-800';
                            else if ($order['status'] === 'pending') echo 'bg-yellow-100 text-yellow-800';
                            else if ($order['status'] === 'processing') echo 'bg-purple-100 text-purple-800';
                            else echo 'bg-blue-100 text-blue-800';
                        ?>">
                        <?php echo htmlspecialchars(ucfirst($order['status'])); ?>
                    </span>
                </p>
                <h3 class="font-semibold mt-2">Update Order Status:</h3>
                <form method="post" class="flex items-center mt-2">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <select name="status" class="border rounded px-3 py-2 text-sm mr-2">
                        <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <button type="submit" name="update_status" class="bg-sky-600 text-white px-4 py-2 rounded-md hover:bg-sky-700">Update</button>
                </form>
            </div>
        </div>
        <div class="mt-4">
            <h3 class="font-semibold mb-2">Shipping Address:</h3>
            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
        </div>
    </div>

    <!-- Buyer Info -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Buyer Information</h2>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($order['buyer_username']); ?></p>
        <p><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($order['buyer_email']); ?>" class="text-sky-600 hover:underline"><?php echo htmlspecialchars($order['buyer_email']); ?></a></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['buyer_phone'] ?? 'N/A'); ?></p>
    </div>

    <!-- Items -->
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Seller</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price/Item</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Format</th>
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
                                            <span class="text-sm font-medium text-sky-600 hover:text-sky-800">
                                                <?php echo htmlspecialchars($item['title']); ?>
                                            </span>
                                            <p class="text-sm text-gray-500">by <?php echo htmlspecialchars($item['author']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($item['store_name']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-500">$<?php echo number_format($item['price'], 2); ?></td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php 
                                        $formats = [];
                                        if ($item['is_physical']) $formats[] = 'Physical';
                                        if ($item['is_digital']) $formats[] = 'Digital';
                                        echo implode(' + ', $formats);
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <div class="mt-8 text-center">
        <a href="<?= BASE_URL ?>/admin/orders.php" class="bg-gray-200 text-gray-700 px-6 py-3 rounded-md hover:bg-gray-300 transition duration-300">
            Back to Orders
        </a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
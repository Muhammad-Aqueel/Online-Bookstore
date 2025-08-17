<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireAuth('buyer');

if (!isset($_GET['id'])) {
    header('Location: ' . BASE_URL . '/buyer/orders.php');
    exit;
}

$orderId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    // Verify the order belongs to the current user and is in a cancellable status
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ? AND buyer_id = ?");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch();

    if (!$order) {
        $_SESSION['error'] = "Order not found or you don't have permission to cancel it.";
        header('Location: ' . BASE_URL . '/buyer/orders.php');
        exit;
    }

    // Only allow cancellation for 'pending' or 'processing' orders
    if ($order['status'] === 'pending' || $order['status'] === 'processing') {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$orderId]);

        // Optionally, refund payment here in a real application (currently manually by admin/sellers)
        // Restock items (only if physical) on order cancellation
        $getItems = $pdo->prepare("SELECT 
            oi.id AS order_item_id,
            oi.order_id,
            oi.book_id,
            oi.quantity,
            oi.is_digital,
            b.title,
            b.stock,
            b.is_physical,
            b.is_digital AS book_is_digital
        FROM order_items oi
        INNER JOIN books b ON oi.book_id = b.id
        WHERE oi.order_id = ?");
        $getItems->execute([$orderId]);
        $items = $getItems->fetchAll();

        foreach ($items as $item) {
            if ($item['is_physical']) { // Only restock physical books
                $restock = $pdo->prepare("UPDATE books b
                INNER JOIN order_items oi ON b.id = oi.book_id
                SET b.stock = b.stock + oi.quantity
                WHERE oi.order_id = ? AND b.is_physical = TRUE");
                $restock->execute([$orderId]);
            }
        }

        $_SESSION['success'] = "Order #$orderId has been cancelled successfully.";
    } else {
        $_SESSION['error'] = "Order #$orderId cannot be cancelled as it is already " . $order['status'] . ".";
    }

    $pdo->commit();

} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Error cancelling order: " . $e->getMessage();
}

header('Location: ' . BASE_URL . '/buyer/orders.php');
exit;
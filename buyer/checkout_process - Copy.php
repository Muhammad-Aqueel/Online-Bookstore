<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

requireAuth('buyer');

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'Invalid CSRF token';
    header('Location: ' . BASE_URL . '/buyer/checkout.php');
    exit;
}

// Process fake payment
$paymentMethod = $_POST['payment_method'] ?? '';
$cardNumber = $_POST['card_number'] ?? '';
$expiry = $_POST['expiry'] ?? '';
$cvv = $_POST['cvv'] ?? '';

// In a real system, you would validate and process payment here
// For our fake system, we'll just simulate a successful payment

try {
    $pdo->beginTransaction();
    
    // Create order
    $stmt = $pdo->prepare("INSERT INTO orders (buyer_id, total_amount, payment_method, payment_status, shipping_address) 
                          VALUES (?, ?, ?, 'completed', ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        $_SESSION['cart_total'],
        $paymentMethod,
        $_POST['shipping_address']
    ]);
    $orderId = $pdo->lastInsertId();

    // Add order items
    foreach ($_SESSION['cart'] as $bookId => $item) {
        $cbook = $pdo->prepare("SELECT `id`, `is_physical`, `is_digital` FROM `books` WHERE `id` = ?");
        $cbook->execute([$bookId]);
        $current_book = $cbook->fetchAll();
        
        $digital_ = $current_book['is_physical'] ? '1' : '1';

        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, book_id, quantity, price, is_digital) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $orderId,
            $bookId,
            $item['quantity'],
            $item['price'],
            $digital_
            // $item['is_digital']
        ]);
        
        // Update book stock if physical
        if (!$item['is_digital']) {
            $pdo->prepare("UPDATE books SET stock = stock - ? WHERE id = ?")
                ->execute([$item['quantity'], $bookId]);
        }
    }
    
    $pdo->commit();
    
    // Clear cart and cart count
    unset($_SESSION['cart']);
    unset($_SESSION['cart_total']);
    unset($_SESSION['cart_count']); // Clear cart count too
    
    $_SESSION['success'] = 'Order placed successfully!';
    header('Location: ' . BASE_URL . '/buyer/order_confirmation.php?id=' . $orderId);
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Error processing your order: ' . $e->getMessage();
    header('Location: ' . BASE_URL . '/buyer/checkout.php');
    exit;
}
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

    // Calculate final total including coupon
    $discountAmount = 0;
    if (!empty($_SESSION['applied_coupon'])) {
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id=? AND active=1 AND (expires_at IS NULL OR expires_at > NOW())");
        $stmt->execute([$_SESSION['applied_coupon']]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($coupon && $_SESSION['cart_total'] >= $coupon['min_order_amount'] && ($coupon['times_used'] !== $coupon['usage_limit'] || $coupon['times_used'] < $coupon['usage_limit'])) {
            $discountAmount = ($coupon['type'] === 'percent')
                ? ($_SESSION['cart_total'] * ($coupon['amount'] / 100))
                : $coupon['amount'];

            // coupon usage
            $stmt = $pdo->prepare("UPDATE `coupons` SET `times_used`= ? WHERE `id` = ?");
            $stmt->execute([$coupon['times_used'] + 1, $_SESSION['applied_coupon']]);
    
            $stmt = $pdo->prepare("INSERT INTO `coupon_usages`(`coupon_id`, `buyer_id`) VALUES (?,?)");
            $stmt->execute([$_SESSION['applied_coupon'], $_SESSION['user_id']]);
        }
    }

    $finalTotal = max($_SESSION['cart_total'] - $discountAmount, 0);

    // Process fake payment
    $paymentMethod = $_POST['payment_method'] ?? '';
    $cardNumber = $_POST['card_number'] ?? '';
    $expiry = $_POST['expiry'] ?? '';
    $cvv = $_POST['cvv'] ?? '';

    try {
        $pdo->beginTransaction();

        // Create order
        $stmt = $pdo->prepare("INSERT INTO orders (buyer_id, total_amount, payment_method, payment_status, shipping_address, coupon_id, discount_amount) 
                            VALUES (?, ?, ?, 'completed', ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $finalTotal,
            $paymentMethod,
            $_POST['shipping_address'],
            $_SESSION['applied_coupon'] ?? null,
            $discountAmount
        ]);
        $orderId = $pdo->lastInsertId();

        // Add order items
        foreach ($_SESSION['cart'] as $bookId => $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, book_id, quantity, price, is_digital) 
                                VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $orderId,
                $bookId,
                $item['quantity'],
                $item['price'],
                $item['is_digital'] ? 1 : 0
            ]);

            // Update book stock if physical
            if (!$item['is_digital']) {
                $pdo->prepare("UPDATE books SET stock = stock - ? WHERE id = ?")
                    ->execute([$item['quantity'], $bookId]);
            }
        }

        $pdo->commit();

        // Clear cart
        unset($_SESSION['cart'], $_SESSION['cart_total'], $_SESSION['cart_count'], $_SESSION['applied_coupon']);

        $_SESSION['success'] = 'Order placed successfully!';
        header('Location: ' . BASE_URL . '/buyer/order_confirmation.php?id=' . $orderId);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Error processing your order: ' . $e->getMessage();
        header('Location: ' . BASE_URL . '/buyer/checkout.php');
        exit;
    }

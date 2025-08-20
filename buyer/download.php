<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

requireAuth('buyer');

if (!isset($_GET['id']) && !isset($_GET['orderid'])) {
    header('Location: ' . BASE_URL . '/buyer/library.php');
    exit;
}

$orderItemId = $_GET['id'];
$userId = $_SESSION['user_id'];

// Verify the user has permission to download this file
$stmt = $pdo->prepare("
    SELECT b.digital_file, oi.digital_downloads
    FROM order_items oi
    JOIN books b ON oi.book_id = b.id
    JOIN orders o ON oi.order_id = o.id
    WHERE oi.id = ? AND o.buyer_id = ? AND b.is_digital = 1 AND o.payment_status = 'completed' AND (o.status = 'shipped' OR o.status = 'delivered')");
$stmt->execute([$orderItemId, $userId]);
$book = $stmt->fetch();

if (!$book || !$book['digital_file']) {
    $_SESSION['error'] = "File not found or you don't have permission to download it";
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (strpos($referer, 'order_details.php') !== false) {
        header('Location: ' . BASE_URL . '/buyer/order_details.php?id=' . $_GET['orderid']);
    } else {
        header('Location: ' . BASE_URL . '/buyer/library.php');
    }
    exit;
}

// Update download count
$pdo->prepare("UPDATE order_items SET digital_downloads = digital_downloads + 1 WHERE id = ?")
    ->execute([$orderItemId]);

// Send the file to the browser
$filePath = '../assets/digital_books/' . $book['digital_file'];
if (file_exists($filePath)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
} else {
    $_SESSION['error'] = "File not found on server";
    header('Location: ' . BASE_URL . '/buyer/library.php');
    exit;
}
?>
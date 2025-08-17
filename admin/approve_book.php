<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

requireAuth('admin');

if (!isset($_GET['id'])) {
    header('Location: ' . BASE_URL . '/admin/');
    exit;
}

$bookId = $_GET['id'];

// Approve the book
$stmt = $pdo->prepare("UPDATE books SET approved = 1 WHERE id = ?");
$stmt->execute([$bookId]);

$_SESSION['success'] = "Book approved successfully";
header('Location: ' . BASE_URL . '/admin/');
exit;
?>
<?php
// Start session at the very beginning of the script
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure necessary files are included and BASE_PATH/BASE_URL are defined
require_once '../config/database.php';
require_once '../includes/auth.php'; // Provides requireAuth
require_once '../includes/helpers.php'; // Provides verifyCsrfToken

requireAuth('admin'); // Only admins can delete users

$userIdToDelete = $_GET['id'] ?? null;
$csrfToken = $_GET['csrf_token'] ?? '';

if (!$userIdToDelete) {
    $_SESSION['error'] = "User ID not provided for deletion.";
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

if (!verifyCsrfToken($csrfToken)) {
    $_SESSION['error'] = 'Invalid CSRF token. Please try again.';
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

// Prevent admin from deleting themselves
if (currentUser()['id'] == $userIdToDelete) {
    $_SESSION['error'] = "You cannot delete your own admin account.";
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Optional: If you have a profile picture for users, delete it here
    // $profilePicStmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = :user_id");
    // $profilePicStmt->bindParam(':user_id', $userIdToDelete, PDO::PARAM_INT);
    // $profilePicStmt->execute();
    // $profilePic = $profilePicStmt->fetchColumn();
    // if ($profilePic && $profilePic !== 'default.jpg' && file_exists(__DIR__ . '/../assets/images/users/' . $profilePic)) {
    //     unlink(__DIR__ . '/../assets/images/users/' . $profilePic);
    // }

    // Deleting a user will automatically cascade delete related records due to FOREIGN KEY ON DELETE CASCADE
    // defined in install.sql (e.g., seller_profiles, books, orders, order_items, reviews, wishlists)
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $userIdToDelete, PDO::PARAM_INT);
    $stmt->execute();

    $pdo->commit();
    $_SESSION['success'] = "User and all associated data deleted successfully.";
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Failed to delete user: " . $e->getMessage();
}

header('Location: ' . BASE_URL . '/admin/users.php');
exit;
?>

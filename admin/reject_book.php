<?php
// Ensure necessary files are included and BASE_PATH/BASE_URL are defined
require_once '../config/database.php';
require_once '../includes/auth.php'; // Provides requireAuth
require_once '../includes/helpers.php'; // Provides verifyCsrfToken, generateCsrfToken

requireAuth('admin'); // Only admins can reject books

$bookIdToReject = $_GET['id'] ?? null;
$csrfToken = $_GET['csrf_token'] ?? '';

if (!$bookIdToReject) {
    $_SESSION['error'] = "Book ID not provided for rejection.";
    header('Location: ' . BASE_URL . '/admin/books.php');
    exit;
}

// Validate CSRF token for security
if (!verifyCsrfToken($csrfToken)) {
    $_SESSION['error'] = 'Invalid CSRF token. Please try again.';
    header('Location: ' . BASE_URL . '/admin/books.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // First, fetch the file paths associated with the book
    $fileStmt = $pdo->prepare("SELECT cover_image, digital_file, preview_pages FROM books WHERE id = :book_id");
    $fileStmt->bindParam(':book_id', $bookIdToReject, PDO::PARAM_INT);
    $fileStmt->execute();
    $bookFiles = $fileStmt->fetch();

    // If the book exists and has associated files, delete them from the server
    if ($bookFiles) {
        $imgDir = __DIR__ . '/../assets/images/books/';
        $digitalDir = __DIR__ . '/../assets/digital_books/';
        $previewDir = __DIR__ . '/../assets/previews/';

        if ($bookFiles['cover_image'] && file_exists($imgDir . $bookFiles['cover_image'])) {
            unlink($imgDir . $bookFiles['cover_image']);
        }
        if ($bookFiles['digital_file'] && file_exists($digitalDir . $bookFiles['digital_file'])) {
            unlink($digitalDir . $bookFiles['digital_file']);
        }
        if ($bookFiles['preview_pages'] && file_exists($previewDir . $bookFiles['preview_pages'])) {
            unlink($previewDir . $bookFiles['preview_pages']);
        }
    }

    // Now, delete the book record from the database.
    // Due to ON DELETE CASCADE settings in install.sql, related entries (like book_categories, order_items, reviews, wishlists)
    // that depend on this book_id will also be automatically deleted.
    $deleteStmt = $pdo->prepare("DELETE FROM books WHERE id = :book_id");
    $deleteStmt->bindParam(':book_id', $bookIdToReject, PDO::PARAM_INT);
    $deleteStmt->execute();

    $pdo->commit();
    $_SESSION['success'] = "Book rejected and removed successfully.";

} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Failed to reject book: " . $e->getMessage();
}

// Redirect back to the book management page
header('Location: ' . BASE_URL . '/admin/books.php');
exit;
?>

<?php
// Start session at the very beginning of the script
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireAuth('seller');

$user = currentUser();
$pageTitle = "My Books";

// Handle book deletion
if (isset($_GET['delete']) && isset($_GET['csrf_token'])) {
    $bookId = (int)$_GET['delete'];
    $csrfToken = $_GET['csrf_token'];

    if (!verifyCsrfToken($csrfToken)) {
        $_SESSION['error'] = 'Invalid CSRF token. Please try again.';
    } else {
        try {
            // Before deleting book record, delete associated files (cover, digital, preview)
            $fileStmt = $pdo->prepare("SELECT cover_image, digital_file, preview_pages FROM books WHERE id = :book_id AND seller_id = :seller_id");
            $fileStmt->bindParam(':book_id', $bookId, PDO::PARAM_INT);
            $fileStmt->bindParam(':seller_id', $user['id'], PDO::PARAM_INT);
            $fileStmt->execute();
            $bookFiles = $fileStmt->fetch();

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

            // Delete book record
            $stmt = $pdo->prepare("DELETE FROM books WHERE id = :book_id AND seller_id = :seller_id");
            $stmt->bindParam(':book_id', $bookId, PDO::PARAM_INT);
            $stmt->bindParam(':seller_id', $user['id'], PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['success'] = "Book deleted successfully.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to delete book: " . $e->getMessage();
        }
    }
    header('Location: ' . BASE_URL . '/seller/books.php');
    exit;
}

// Get seller's books with pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare("SELECT * FROM books WHERE seller_id = :seller_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindParam(':seller_id', $user['id'], PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute(); // Execute without an array
$books = $stmt->fetchAll();

// Get total count for pagination
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE seller_id = :seller_id");
$totalStmt->bindParam(':seller_id', $user['id'], PDO::PARAM_INT);
$totalStmt->execute(); // Execute without an array
$totalBooks = $totalStmt->fetchColumn();
$totalPages = ceil($totalBooks / $limit);

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-sky-800 mb-6">My Books</h1>

    <div class="mb-6">
        <a href="<?= BASE_URL ?>/seller/add_book.php" class="bg-sky-600 text-white px-4 py-2 rounded-md hover:bg-sky-700 transition duration-300">
            <i class="fas fa-plus mr-2"></i> Add New Book
        </a>
    </div>

    <?php if (empty($books)): ?>
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <p class="text-gray-600 mb-4">You haven't added any books yet.</p>
            <a href="<?= BASE_URL ?>/seller/add_book.php" class="bg-sky-600 text-white px-4 py-2 rounded hover:bg-sky-700">Add Your First Book</a>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Author</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php 
                        // Generate CSRF token for delete links
                        $csrfToken = generateCsrfToken(); 
                        foreach ($books as $book): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-md overflow-hidden mr-3 text-center">
                                            <?php if ($book['cover_image']): ?>
                                                <img src="<?= BASE_URL; ?>/assets/images/books/<?php echo htmlspecialchars($book['cover_image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="h-full w-full object-cover">
                                            <?php else: ?>
                                                <i class="fas fa-book text-xl text-gray-400 mt-2"></i>
                                            <?php endif; ?>
                                        </div>
                                        <a href="<?= BASE_URL ?>/buyer/book.php?id=<?php echo htmlspecialchars($book['id']); ?>" class="text-sm font-medium text-sky-600 hover:text-sky-800">
                                            <?php echo htmlspecialchars($book['title']); ?>
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($book['author']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">$<?php echo number_format($book['price'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($book['stock']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        <?php echo $book['approved'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo $book['approved'] ? 'Approved' : 'Pending'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                    <a href="<?= BASE_URL ?>/seller/edit_book.php?id=<?php echo $book['id']; ?>" class="text-sky-600 hover:text-sky-800 mr-2">Edit</a>
                                    <a href="<?= BASE_URL ?>/seller/books.php?delete=<?php echo $book['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" 
                                    onclick="return confirm('Are you sure you want to permanently delete this book? This cannot be undone.')"
                                    class="text-red-600 hover:text-red-800">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-200">
                <div class="flex-1 flex justify-between sm:hidden">
                    <a href="<?= BASE_URL ?>/seller/books.php?page=<?php echo max(1, $page - 1); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Previous
                    </a>
                    <a href="<?= BASE_URL ?>/seller/books.php?page=<?php echo min($totalPages, $page + 1); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Next
                    </a>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $limit, $totalBooks); ?></span> of <span class="font-medium"><?php echo $totalBooks; ?></span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <a href="<?= BASE_URL ?>/seller/books.php?page=<?php echo max(1, $page - 1); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Previous</span>
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="<?= BASE_URL ?>/seller/books.php?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'bg-sky-50 border-sky-500 text-sky-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium z-10">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <a href="<?= BASE_URL ?>/seller/books.php?page=<?php echo min($totalPages, $page + 1); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Next</span>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

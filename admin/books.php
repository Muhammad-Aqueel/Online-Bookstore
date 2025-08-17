<?php
// Start session at the very beginning of the script
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure necessary files are included and BASE_PATH/BASE_URL are defined
require_once '../config/database.php';
require_once '../includes/auth.php'; // Provides requireAuth, currentUser, isLoggedIn, hasRole
require_once '../includes/helpers.php'; // Provides sanitizeInput, generateCsrfToken, verifyCsrfToken

requireAuth('admin');

$pageTitle = "Book Management";

// Handle book approval/rejection/deletion
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['approve'])) {
        $bookId = (int)$_GET['approve'];
        $stmt = $pdo->prepare("UPDATE books SET approved = 1 WHERE id = :book_id");
        $stmt->bindParam(':book_id', $bookId, PDO::PARAM_INT);
        $stmt->execute();
        $_SESSION['success'] = "Book approved successfully.";
        header('Location: ' . BASE_URL . '/admin/books.php');
        exit;
    }

    if (isset($_GET['reject'])) {
        $bookId = (int)$_GET['reject'];
        // Before deleting book record, delete associated files (cover, digital, preview)
        $fileStmt = $pdo->prepare("SELECT cover_image, digital_file, preview_pages FROM books WHERE id = :book_id");
        $fileStmt->bindParam(':book_id', $bookId, PDO::PARAM_INT);
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

        $stmt = $pdo->prepare("DELETE FROM books WHERE id = :book_id");
        $stmt->bindParam(':book_id', $bookId, PDO::PARAM_INT);
        $stmt->execute();
        $_SESSION['success'] = "Book rejected and removed successfully.";
        header('Location: ' . BASE_URL . '/admin/books.php');
        exit;
    }

    if (isset($_GET['delete'])) {
        $bookId = (int)$_GET['delete'];
        // Before deleting book record, delete associated files (cover, digital, preview)
        $fileStmt = $pdo->prepare("SELECT cover_image, digital_file, preview_pages FROM books WHERE id = :book_id");
        $fileStmt->bindParam(':book_id', $bookId, PDO::PARAM_INT);
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

        $stmt = $pdo->prepare("DELETE FROM books WHERE id = :book_id");
        $stmt->bindParam(':book_id', $bookId, PDO::PARAM_INT);
        $stmt->execute();
        $_SESSION['success'] = "Book deleted successfully.";
        header('Location: ' . BASE_URL . '/admin/books.php');
        exit;
    }
}

// Get filter status
$filterStatus = $_GET['status'] ?? 'all'; // 'all', 'approved', 'pending'

// Pagination setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$sql = "SELECT b.*, u.username as seller_name FROM books b JOIN users u ON b.seller_id = u.id";
$countSql = "SELECT COUNT(*) FROM books b JOIN users u ON b.seller_id = u.id";
$params = []; // For filtering conditions, not limit/offset

if ($filterStatus === 'approved') {
    $sql .= " WHERE b.approved = 1";
    $countSql .= " WHERE b.approved = 1";
} elseif ($filterStatus === 'pending') {
    $sql .= " WHERE b.approved = 0";
    $countSql .= " WHERE b.approved = 0";
}

$sql .= " ORDER BY b.created_at DESC LIMIT :limit OFFSET :offset";

// Fetch books
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
// If you had other WHERE clauses with parameters, you'd bind them here too
$stmt->execute();
$books = $stmt->fetchAll();

// Get total count for pagination
$countStmt = $pdo->prepare($countSql);
$countStmt->execute(); // No parameters for count query in this setup
$totalBooks = $countStmt->fetchColumn();
$totalPages = ceil($totalBooks / $limit);

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-sky-800 mb-6">Book Management</h1>

    <div class="mb-6 flex justify-between items-center flex-wrap">
        <div class="flex space-x-2 mb-2">
            <a href="<?= BASE_URL ?>/admin/books.php?status=all" class="px-4 py-2 rounded-md <?php echo $filterStatus === 'all' ? 'bg-sky-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">All Books</a>
            <a href="<?= BASE_URL ?>/admin/books.php?status=approved" class="px-4 py-2 rounded-md <?php echo $filterStatus === 'approved' ? 'bg-sky-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">Approved Books</a>
            <a href="<?= BASE_URL ?>/admin/books.php?status=pending" class="px-4 py-2 rounded-md <?php echo $filterStatus === 'pending' ? 'bg-sky-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">Pending Approval</a>
        </div>
        <a href="<?= BASE_URL ?>/admin/categories.php" class="px-4 py-2 rounded-md bg-sky-600 text-white hover:bg-gray-300 hover:text-gray-700 mb-2" target="_blank">Add Category</a>
    </div>

    <?php if (empty($books)): ?>
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <p class="text-gray-600">No books found for this filter.</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Author</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Seller</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-md overflow-hidden mr-3 text-center">
                                            <?php if ($book['cover_image']): ?>
                                                <img src="<?= BASE_URL ?>/assets/images/books/<?php echo htmlspecialchars($book['cover_image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>" class="h-full w-full object-cover">
                                            <?php else: ?>
                                                <i class="fas fa-book text-xl text-gray-400 mt-2"></i>
                                            <?php endif; ?>
                                        </div>
                                        <?php echo htmlspecialchars($book['title']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($book['author']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($book['seller_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">$<?php echo number_format($book['price'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><?php echo htmlspecialchars($book['stock']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        <?php echo $book['approved'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo $book['approved'] ? 'Approved' : 'Pending'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                    <?php if (!$book['approved']): ?>
                                        <a href="<?= BASE_URL ?>/admin/books.php?approve=<?php echo $book['id']; ?>" class="text-green-600 hover:text-green-800 mr-2">Approve</a>
                                        <a href="<?= BASE_URL ?>/admin/reject_book.php?id=<?php echo $book['id']; ?>&csrf_token=<?php echo generateCsrfToken(); ?>"
                                        onclick="return confirm('Are you sure you want to reject and permanently delete this book? This cannot be undone.')"
                                        class="text-red-600 hover:text-red-800 mr-2">Reject</a>
                                    <?php endif; ?>
                                    <a href="<?= BASE_URL ?>/buyer/book.php?id=<?php echo $book['id']; ?>" class="text-sky-600 hover:text-sky-800 mr-2" target="_blank">View</a>
                                    <a href="<?= BASE_URL ?>/admin/books.php?delete=<?php echo $book['id']; ?>" 
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
                    <a href="<?= BASE_URL ?>/admin/books.php?status=<?php echo htmlspecialchars($filterStatus); ?>&page=<?php echo max(1, $page - 1); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Previous
                    </a>
                    <a href="<?= BASE_URL ?>/admin/books.php?status=<?php echo htmlspecialchars($filterStatus); ?>&page=<?php echo min($totalPages, $page + 1); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
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
                            <a href="<?= BASE_URL ?>/admin/books.php?status=<?php echo htmlspecialchars($filterStatus); ?>&page=<?php echo max(1, $page - 1); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Previous</span>
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="<?= BASE_URL ?>/admin/books.php?status=<?php echo htmlspecialchars($filterStatus); ?>&page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'bg-sky-50 border-sky-500 text-sky-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium z-10">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <a href="<?= BASE_URL ?>/admin/books.php?status=<?php echo htmlspecialchars($filterStatus); ?>&page=<?php echo min($totalPages, $page + 1); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
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

<?php

require_once '../config/database.php';
require_once '../includes/auth.php'; // Provides isLoggedIn, currentUser, requireAuth
require_once '../includes/helpers.php'; // Provides sanitizeInput, generateCsrfToken, verifyCsrfToken

redirectIfNotLoggedIn();

$user = currentUser();
$pageTitle = "My Wishlist";

// Handle add to wishlist
if (isset($_GET['add']) && isset($_GET['csrf_token'])) {
    $bookId = (int)$_GET['add'];
    $csrfToken = $_GET['csrf_token'];

    if (!verifyCsrfToken($csrfToken)) {
        $_SESSION['error'] = 'Invalid CSRF token. Please try again.';
    } else {
        try {
            // Check if already in wishlist
            $checkStmt = $pdo->prepare("SELECT 1 FROM wishlists WHERE user_id = :user_id AND book_id = :book_id");
            $checkStmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
            $checkStmt->bindParam(':book_id', $bookId, PDO::PARAM_INT);
            $checkStmt->execute();

            if ($checkStmt->fetchColumn()) {
                $_SESSION['info'] = "Book is already in your wishlist.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO wishlists (user_id, book_id) VALUES (:user_id, :book_id)");
                $stmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
                $stmt->bindParam(':book_id', $bookId, PDO::PARAM_INT);
                $stmt->execute();
                $_SESSION['success'] = "Book added to your wishlist.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to add book to wishlist: " . $e->getMessage();
        }
    }
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (strpos($referer, 'book.php') !== false) {
        header('Location: ' . BASE_URL . '/buyer/book.php?id=' . $bookId);
    } elseif (strpos($referer, 'search.php') !== false) {
        header('Location: ' . BASE_URL . '/buyer/wishlist.php');
    } else {
        header('Location: ' . BASE_URL . '/buyer/');
    }
    exit;
}

// Handle remove from wishlist
if (isset($_GET['remove']) && isset($_GET['csrf_token'])) { // Ensure csrf_token is present
    $bookId = (int)$_GET['remove'];
    $csrfToken = $_GET['csrf_token'];

    if (!verifyCsrfToken($csrfToken)) {
        $_SESSION['error'] = 'Invalid CSRF token. Please try again.';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM wishlists WHERE user_id = :user_id AND book_id = :book_id");
            $stmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
            $stmt->bindParam(':book_id', $bookId, PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['success'] = "Book removed from wishlist.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to remove book from wishlist: " . $e->getMessage();
        }
    }
    header('Location: ' . BASE_URL . '/buyer/wishlist.php');
    exit;
}

// Get wishlist items with pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare("
    SELECT b.*,w.* FROM books b
    JOIN wishlists w ON b.id = w.book_id
    WHERE w.user_id = :user_id
    ORDER BY w.added_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute(); // Execute without an array as all parameters are bound via bindParam
$books = $stmt->fetchAll();

// Get total count for pagination
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE user_id = :user_id");
$totalStmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
$totalStmt->execute();
$totalBooks = $totalStmt->fetchColumn();
$totalPages = ceil($totalBooks / $limit);

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-sky-800 mb-6">My Wishlist</h1>

    <?php if (empty($books)): ?>
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <p class="text-gray-600 mb-4">Your wishlist is empty.</p>
            <a href="<?= BASE_URL ?>/buyer/" class="bg-sky-600 text-white px-4 py-2 rounded hover:bg-sky-700">Browse Books</a>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="data-table min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Book</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Author</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Added On</th>
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
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo date('M d, Y', strtotime($book['added_at'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                    <a href="<?= BASE_URL ?>/buyer/wishlist.php?remove=<?php echo $book['id']; ?>&csrf_token=<?php echo $csrfToken; ?>"
                                    onclick="return confirm('Are you sure you want to remove this book from your wishlist?')"
                                    class="text-red-600 hover:text-red-800">Remove</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-200">
                <div class="flex-1 flex justify-between sm:hidden">
                    <a href="<?= BASE_URL ?>/buyer/wishlist.php?page=<?php echo max(1, $page - 1); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Previous
                    </a>
                    <a href="<?= BASE_URL ?>/buyer/wishlist.php?page=<?php echo min($totalPages, $page + 1); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
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
                            <a href="<?= BASE_URL ?>/buyer/wishlist.php?page=<?php echo max(1, $page - 1); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Previous</span>
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="<?= BASE_URL ?>/buyer/wishlist.php?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'bg-sky-50 border-sky-500 text-sky-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium z-10">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <a href="<?= BASE_URL ?>/buyer/wishlist.php?page=<?php echo min($totalPages, $page + 1); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
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

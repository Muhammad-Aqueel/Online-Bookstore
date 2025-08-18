<?php
    
    require_once '../config/database.php';
    require_once '../includes/auth.php';
    require_once '../includes/helpers.php';

    requireAuth('buyer');

    $user = currentUser();
    $pageTitle = "Browse Books";

    $whereClauses = ["approved = 1"];
    $params = [];

    // Price filter
    if (!empty($_GET['min_price'])) {
        $whereClauses[] = "price >= ?";
        $params[] = (float) $_GET['min_price'];
    }
    if (!empty($_GET['max_price'])) {
        $whereClauses[] = "price <= ?";
        $params[] = (float) $_GET['max_price'];
    }

    // Format filter
    if (!empty($_GET['format'])) {
        $formats = [];
        if (in_array('physical', $_GET['format'])) {
            $formats[] = "is_physical = 1";
        }
        if (in_array('digital', $_GET['format'])) {
            $formats[] = "is_digital = 1";
        }
        if ($formats) {
            $whereClauses[] = "(" . implode(" OR ", $formats) . ")";
        }
    }

    // Build WHERE clause
    $whereSQL = implode(" AND ", $whereClauses);

    // Get categories for sidebar
    $categories = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL")->fetchAll();

    // Get featured books
    $featuredBooks = $pdo->query("SELECT * FROM books WHERE approved = 1 ORDER BY RAND() LIMIT 6")->fetchAll();

    // Get newest books
    $newestBooks = $pdo->query("SELECT * FROM books WHERE approved = 1 ORDER BY created_at DESC LIMIT 6")->fetchAll();

    // Fetch all book IDs in the user's wishlist
    try {
        $wishlistStmt = $pdo->prepare("SELECT book_id FROM wishlists WHERE user_id = :user_id");
        $wishlistStmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $wishlistStmt->execute();

        // This returns an array of book IDs
        $wishlistBooks = $wishlistStmt->fetchAll(PDO::FETCH_COLUMN); 
    } catch (PDOException $e) {
        $wishlistBooks = [];
        $_SESSION['error'] = "Failed to fetch wishlist: " . $e->getMessage();
    }

    include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row gap-8">
        <!-- Main Content -->
        <div class="container mx-auto px-4 py-8">
            <!-- Featured Books -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-sky-800 mb-4">Featured Books</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <?php foreach ($featuredBooks as $book): ?>
                    <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="h-48 bg-gray-200 flex items-center justify-center">
                            <?php if ($book['cover_image']): ?>
                            <img src="<?= BASE_URL ?>/assets/images/books/<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($book['title']); ?>" class="h-full object-cover">
                            <?php else: ?>
                                <!-- No Image -->
                                <i class="fas fa-book text-6xl text-gray-400"></i>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <div class="flex justify-between items-center">
                                <h3 class="font-semibold text-lg mb-1"><?php echo htmlspecialchars($book['title']); ?></h3>
                                <!-- Add to Wishlist Button -->
                                <?php $csrfToken = generateCsrfToken(); ?>
                                <a href="<?= BASE_URL ?>/buyer/wishlist.php?add=<?= $book['id']; ?>&csrf_token=<?= $csrfToken; ?>" class="text-gray-600 hover:text-sky-600 flex items-center" data-tooltip="Add to wishlist"><i class="<?php echo in_array($book['id'], $wishlistBooks) ? 'text-red-600 fas' : 'far'; ?> fa-heart mr-1"></i></a>
                            </div>
                            <p class="text-gray-600 mb-2"><?php echo htmlspecialchars($book['author']); ?></p>
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-sky-600">$<?php echo number_format($book['price'], 2); ?></span>
                                    <a href="<?= BASE_URL ?>/buyer/book.php?id=<?php echo $book['id']; ?>"
                                       class="bg-sky-600 text-white py-1 px-3 rounded hover:bg-sky-700">
                                        View
                                    </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Newest Books -->
            <div>
                <h2 class="text-2xl font-bold text-sky-800 mb-4">New Arrivals</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($newestBooks as $book): ?>
                    <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="h-48 bg-gray-200 flex items-center justify-center">
                            <?php if ($book['cover_image']): ?>
                            <img src="<?= BASE_URL ?>/assets/images/books/<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($book['title']); ?>" class="h-full object-cover">
                            <?php else: ?>
                                <!-- No Image -->
                                <i class="fas fa-book text-6xl text-gray-400"></i>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <div class="flex justify-between items-center">
                                <h3 class="font-semibold text-lg mb-1"><?php echo htmlspecialchars($book['title']); ?></h3>
                                <!-- Add to Wishlist Button -->
                                <?php $csrfToken = generateCsrfToken(); ?>
                                <a href="<?= BASE_URL ?>/buyer/wishlist.php?add=<?= $book['id']; ?>&csrf_token=<?= $csrfToken; ?>" class="text-gray-600 hover:text-sky-600 flex items-center" target="_blank" data-tooltip="Add to wishlist"><i class="<?php echo in_array($book['id'], $wishlistBooks) ? 'text-red-600 fas' : 'far'; ?> fa-heart mr-1"></i></a>
                            </div>
                            <p class="text-gray-600 mb-2"><?php echo htmlspecialchars($book['author']); ?></p>
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-sky-600">$<?php echo number_format($book['price'], 2); ?></span>
                                <a href="<?= BASE_URL ?>/buyer/book.php?id=<?php echo $book['id']; ?>" 
                                   class="bg-sky-600 text-white py-1 px-3 rounded hover:bg-sky-700">
                                    View
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
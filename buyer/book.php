<?php
    
    require_once '../config/database.php';
    require_once '../includes/auth.php';
    require_once '../includes/helpers.php';

    if (!isset($_GET['id'])) {
        header('Location: ' . BASE_URL . '/buyer/');
        exit;
    }

    $bookId = $_GET['id'];
    $pageTitle = "Book Details";

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

    // Get book details
    $stmt = $pdo->prepare("
        SELECT b.*, u.username as seller_name
        FROM books b
        JOIN users u ON b.seller_id = u.id
        WHERE b.id = ? AND b.approved = 1
    ");
    $stmt->execute([$bookId]);
    $book = $stmt->fetch();

    if (!$book) {
        header('Location: ' . BASE_URL . '/buyer/');
        exit;
    }

    // Get book categories
    $categories = $pdo->prepare("
        SELECT c.name
        FROM book_categories bc
        JOIN categories c ON bc.category_id = c.id
        WHERE bc.book_id = ?
    ");
    $categories->execute([$bookId]);
    $categories = $categories->fetchAll(PDO::FETCH_COLUMN);

    // Get book reviews
    $reviews = $pdo->prepare("
        SELECT r.*, u.username
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.book_id = ?
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $reviews->execute([$bookId]);
    $reviews = $reviews->fetchAll();

    // Calculate average rating
    $avgRatingStmt = $pdo->prepare("SELECT AVG(rating) FROM reviews WHERE book_id = ?");
    $avgRatingStmt->execute([$bookId]);
    $avgRating = $avgRatingStmt->fetchColumn();

    // Handle add to cart
    if (isset($_POST['add_to_cart']) && isLoggedIn() && hasRole('buyer')) {
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));
        // Determine if it's a digital purchase:
        // If book is only digital, or if it's both but 'is_digital' checkbox was checked
        $isDigitalPurchase = ($book['is_digital'] && !$book['is_physical']) || (isset($_POST['is_digital']) && $book['is_physical'] && $book['is_digital']);

        // Initialize cart if not exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
            $_SESSION['cart_total'] = 0;
        }
        $incart_qty = isset($_SESSION['cart'][$bookId]['quantity']) ? $_SESSION['cart'][$bookId]['quantity'] : 0;
        // Check stock for physical books
        if (!$isDigitalPurchase && $book['is_physical']) {
            if ($book['stock'] < $quantity || $book['stock'] < $incart_qty + $quantity) {
                if($book['stock'] < 1){
                    $_SESSION['error'] = htmlspecialchars($book['title']) . " is out of stock.";
                    header('Location: ' . BASE_URL . '/buyer/book.php?id=' . $bookId);
                    exit;
                } else {
                    if(isset($_SESSION['cart'][$bookId]['quantity'])){
                        $_SESSION['error'] = "Only " . (int)$book['stock'] . " " . ((int)$book['stock'] === 1 ? "copy" : "copies") . " of " . htmlspecialchars($book['title']) . ((int)$book['stock'] === 1 ? " is" : " are") . " available, and you already have " . (int)$_SESSION['cart'][$bookId]['quantity'] . " in your cart.";
                        header('Location: ' . BASE_URL . '/buyer/book.php?id=' . $bookId);
                        exit;
                    } else {
                        $_SESSION['error'] = "Only " . (int)$book['stock'] . " " . ((int)$book['stock'] === 1 ? "copy" : "copies") . " of " . htmlspecialchars($book['title']) . ((int)$book['stock'] === 1 ? " is" : " are") . " available.";
                        header('Location: ' . BASE_URL . '/buyer/book.php?id=' . $bookId);
                        exit;
                    }
                }
            }
        }

        // Add or update item in cart
        if (isset($_SESSION['cart'][$bookId])) {
            // If adding a digital book to a cart already containing its physical version, or vice-versa,
            // it's best to treat them as separate items or disallow. For simplicity, we'll allow
            // adding the same book multiple times but the 'is_digital' flag will be based on the last addition.
            // A more complex cart might have separate entries for digital/physical versions of the same book.
            $_SESSION['cart'][$bookId]['quantity'] += $quantity;
            $_SESSION['cart'][$bookId]['is_digital'] = $isDigitalPurchase; // Update to reflect current choice
        } else {
            $_SESSION['cart'][$bookId] = [
                'title' => $book['title'],
                'quantity' => $quantity,
                'price' => $book['price'],
                'is_digital' => $isDigitalPurchase
            ];
        }

        // Update cart total and count
        $_SESSION['cart_total'] = 0; // Recalculate total to be safe
        $_SESSION['cart_count'] = 0; // Recalculate count
        foreach ($_SESSION['cart'] as $itemInCart) {
            $_SESSION['cart_total'] += $itemInCart['price'] * $itemInCart['quantity'];
            $_SESSION['cart_count'] += $itemInCart['quantity'];
        }
        
        $_SESSION['success'] = "Book added to cart successfully";
        header('Location: ' . BASE_URL . '/buyer/cart.php');
        exit;
    }

    // Handle review submission
    if (isset($_POST['submit_review']) && isLoggedIn() && hasRole('buyer')) {
        $rating = max(1, min(5, (int)$_POST['rating']));
        $comment = trim($_POST['comment']);
        $userId = $_SESSION['user_id'];

        // Check if user has already reviewed this book
        $existingReviewStmt = $pdo->prepare("SELECT id FROM reviews WHERE book_id = ? AND user_id = ?");
        $existingReviewStmt->execute([$bookId, $userId]);
        if ($existingReviewStmt->fetch()) {
            $_SESSION['error'] = "You have already submitted a review for this book.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO reviews (book_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
            $stmt->execute([$bookId, $userId, $rating, $comment]);
            $_SESSION['success'] = "Review submitted successfully";
        }
        header('Location: ' . BASE_URL . '/buyer/book.php?id=' . $bookId);
        exit;
    }

    include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <!-- Book Details -->
        <div class="md:flex">
            <div class="md:w-1/3 p-6">
                <div class="bg-gray-200 rounded-lg overflow-hidden flex items-center justify-center" style="min-height: 300px;">
                    <?php if ($book['cover_image']): ?>
                        <img src="<?= BASE_URL; ?>/assets/images/books/<?php echo htmlspecialchars($book['cover_image']); ?>" 
                             alt="<?php echo htmlspecialchars($book['title']); ?>" class="h-full object-cover">
                    <?php else: ?>
                        <i class="fas fa-book-open text-6xl text-gray-400"></i>
                    <?php endif; ?>
                </div>
                
                <?php if ($book['preview_pages']): ?>
                    <div class="mt-4">
                        <a href="<?= BASE_URL ?>/assets/previews/<?php echo htmlspecialchars($book['preview_pages']); ?>" 
                           target="_blank" class="text-sky-600 hover:text-sky-800 flex items-center">
                            <i class="fas fa-eye mr-2"></i> Preview Sample
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="md:w-2/3 p-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($book['title']); ?></h1>
                <p class="text-xl text-gray-600 mb-4">by <?php echo htmlspecialchars($book['author']); ?></p>
                
                <?php if ($avgRating): ?>
                    <div class="flex items-center mb-4">
                        <div class="flex">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= round($avgRating) ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="ml-2 text-gray-600">(<?php echo number_format($avgRating, 1); ?> out of 5)</span>
                    </div>
                <?php endif; ?>
                
                <div class="mb-6">
                    <span class="text-2xl font-bold text-sky-600">$<?php echo number_format($book['price'], 2); ?></span>
                    <?php if ($book['is_physical']): ?>
                        <span class="ml-2 text-sm text-gray-500">+ shipping</span>
                    <?php endif; ?>
                </div>
                
                <div class="mb-6">
                    <h3 class="font-semibold text-gray-900 mb-2">Description</h3>
                    <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
                </div>
                
                <div class="mb-6">
                    <h3 class="font-semibold text-gray-900 mb-2">Details</h3>
                    <ul class="text-gray-600 space-y-1">
                        <li><strong>Seller:</strong> <?php echo htmlspecialchars($book['seller_name']); ?></li>
                        <li><strong>ISBN:</strong> <?php echo $book['isbn'] ? htmlspecialchars($book['isbn']) : 'N/A'; ?></li>
                        <li><strong>Categories:</strong> <?php echo $categories ? implode(', ', $categories) : 'Uncategorized'; ?></li>
                        <li><strong>Format:</strong> 
                            <?php 
                                $formats = [];
                                if ($book['is_physical']) $formats[] = 'Physical';
                                if ($book['is_digital']) $formats[] = 'Digital';
                                echo implode(' + ', $formats);
                            ?>
                        </li>
                        <li><strong>Stock:</strong> <?php if($book['is_physical']){
                        echo $book['stock'] > 0 ? $book['stock'] . ' available' : 'Out of stock';
                        } else {
                            echo 'Available';
                        }
                        ?></li>
                    </ul>
                </div>
                
                <?php if (isLoggedIn() && hasRole('buyer')): ?>
                    <form method="post" class="border-t border-gray-200 pt-6">
                        <?php if ($book['is_physical'] && $book['is_digital']): ?>
                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="is_digital" class="mr-2">
                                    <span>Digital version only</span>
                                </label>
                            </div>
                        <?php elseif ($book['is_digital']): // Only digital option, force it as digital ?>
                            <input type="hidden" name="is_digital" value="1">
                        <?php endif; ?>
                        
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center">
                                <label class="mr-2">Quantity:</label>
                                <input type="number" name="quantity" value="<?php echo ($book['stock']) === 0 ? 0 : 1; ?>" min="1" 
                                       max="<?php echo ($book['is_physical']) ? $book['stock'] : '99'; ?>" 
                                       class="w-16 px-2 py-1 border rounded">
                            </div>
                            <button type="submit" name="add_to_cart" class="bg-sky-600 text-white px-4 py-2 rounded hover:bg-sky-700">
                                Add to Cart
                            </button>
                            <!-- Add to Wishlist Button -->
                            <?php $csrfToken = generateCsrfToken(); ?>
                            <a href="<?= BASE_URL ?>/buyer/wishlist.php?add=<?= $book['id']; ?>&csrf_token=<?= $csrfToken; ?>" class="text-gray-600 hover:text-sky-600 flex items-center mt-2" data-tooltip="Add to wishlist"><i class="<?php echo in_array($book['id'], $wishlistBooks) ? 'text-red-600 fas' : 'far'; ?> fa-heart mr-1"></i>Add to Wishlist</a>
                        </div>
                    </form>
                <?php elseif (!isLoggedIn()): ?>
                    <div class="border-t border-gray-200 pt-6">
                        <p class="text-gray-600 mb-4">Please <a href="<?= BASE_URL ?>/auth/login.php" class="text-sky-600 hover:text-sky-800">login</a> to purchase this book.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Reviews Section -->
        <div class="border-t border-gray-200 p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Customer Reviews</h2>
            
            <?php if (!empty($reviews)): ?>
                <div class="space-y-6 mb-8">
                    <?php foreach ($reviews as $review): ?>
                        <div class="border-b border-gray-200 pb-6 last:border-0 last:pb-0">
                            <div class="flex justify-between mb-2">
                                <h3 class="font-medium"><?php echo htmlspecialchars($review['username']); ?></h3>
                                <div class="text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                </div>
                            </div>
                            <div class="flex mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-600 mb-8">No reviews yet. Be the first to review this book!</p>
            <?php endif; ?>
            
            <?php if (isLoggedIn() && hasRole('buyer')): ?>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-gray-900 mb-3">Write a Review</h3>
                    <form method="post">
                        <div class="mb-4">
                            <label class="block text-gray-700 mb-1">Rating</label>
                            <select name="rating" class="border rounded px-3 py-2">
                                <option value="5">5 Stars</option>
                                <option value="4">4 Stars</option>
                                <option value="3">3 Stars</option>
                                <option value="2">2 Stars</option>
                                <option value="1">1 Star</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 mb-1">Comment</label>
                            <textarea name="comment" rows="4" class="w-full px-3 py-2 border rounded" required></textarea>
                        </div>
                        <button type="submit" name="submit_review" class="bg-sky-600 text-white px-4 py-2 rounded hover:bg-sky-700">
                            Submit Review
                        </button>
                    </form>
                </div>
            <?php elseif (!isLoggedIn()): ?>
                <p class="text-gray-600">
                    Please <a href="<?= BASE_URL ?>/auth/login.php" class="text-sky-600 hover:text-sky-800">login</a> to write a review.
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
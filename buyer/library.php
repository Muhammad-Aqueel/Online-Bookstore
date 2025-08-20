<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

requireAuth('buyer');

$user = currentUser();
$pageTitle = "My Digital Library";

// Get purchased digital books
$stmt = $pdo->prepare("
    SELECT b.id, b.title, b.author, b.cover_image, oi.digital_downloads, oi.id as order_item_id, o.order_date as purchase_date
        FROM order_items oi
        JOIN books b ON oi.book_id = b.id
        JOIN orders o ON oi.order_id = o.id
            WHERE o.buyer_id = ? AND b.is_digital = 1 AND o.payment_status = 'completed' AND o.status = 'delivered'
            ORDER BY purchase_date DESC");
$stmt->execute([$user['id']]);
$books = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-sky-800 mb-6">My Digital Library</h1>
    
    <?php if (empty($books)): ?>
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <p class="text-gray-600 mb-4">You haven't purchased any eBooks yet.</p>
            <a href="<?= BASE_URL ?>/buyer/" class="bg-sky-600 text-white px-4 py-2 rounded hover:bg-sky-700">Browse eBooks</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($books as $book): ?>
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
                        <h3 class="font-semibold text-lg mb-1"><?php echo htmlspecialchars($book['title']); ?></h3>
                        <p class="text-gray-600 mb-2"><?php echo htmlspecialchars($book['author']); ?></p>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500">
                                Purchased: <?php echo date('M d, Y', strtotime($book['purchase_date'])); ?>
                            </span>
                            <span class="text-sm text-gray-500">
                                Downloads: <?php echo $book['digital_downloads']; ?>
                            </span>
                        </div>
                        <div class="mt-4">
                            <a href="<?= BASE_URL ?>/buyer/download.php?id=<?php echo $book['order_item_id']; ?>" 
                               class="w-full bg-sky-600 text-white py-1 px-3 rounded hover:bg-sky-700 flex items-center justify-center">
                                <i class="fas fa-download mr-2"></i> Download
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
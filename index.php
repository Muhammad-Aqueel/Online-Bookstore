<?php
// Check if the application is installed by verifying if config/database.php exists
if (!file_exists(__DIR__ . '/config/database.php')) {
    // If database.php does not exist, redirect to the installer
    header('Location: ./install/');
    exit;
}

// If database.php exists, proceed with normal application loading
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect logged-in buyers to their dashboard
// if (isLoggedIn() && hasRole('buyer')) {
    // header('Location: ' . BASE_URL . '/buyer/');
    // exit;
// }

$pageTitle = "Welcome to BookStore";

// You can fetch some public data here if needed for the landing page
// Example: latest arrivals or popular books for non-logged-in users
$publicBooks = $pdo->query("SELECT * FROM books WHERE approved = 1 ORDER BY created_at DESC LIMIT 8")->fetchAll();

require_once __DIR__ . '/includes/header.php'; // Include the header
?>

<div class="container mx-auto px-4 py-8">
    <div class="text-center py-16 bg-gradient-to-r from-sky-600 to-sky-800 text-white rounded-lg shadow-lg mb-8">
        <h1 class="text-5xl font-extrabold mb-4 animate-fade-in-down">Discover Your Next Great Read!</h1>
        <p class="text-xl mb-8 animate-fade-in-up">Explore a vast collection of physical and digital books.</p>
        <div class="space-x-4 animate-scale-in">
            <?php if ($isBuyer || !isLoggedIn()): ?>
                <a href="<?= BASE_URL ?>/buyer/" class="bg-white text-sky-800 px-8 py-4 rounded-full font-bold shadow-lg hover:bg-gray-100 transition duration-300">
                    Browse Books
                </a>
            <?php endif; ?>
            <?php if (!isLoggedIn()): ?>
                <a href="<?= BASE_URL ?>/auth/register.php" class="border-2 border-white text-white px-8 py-4 rounded-full font-bold shadow-lg hover:bg-white hover:text-sky-800 transition duration-300">
                    Join Us
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Latest Arrivals Section -->
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-sky-800 mb-6 text-center">Latest Arrivals</h2>
        <?php if (!empty($publicBooks)): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php foreach ($publicBooks as $book): ?>
                    <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-xl transition-shadow duration-300">
                        <div class="h-48 bg-gray-200 flex items-center justify-center overflow-hidden">
                            <?php if ($book['cover_image']): ?>
                                <img src="<?= BASE_URL ?>/assets/images/books/<?php echo htmlspecialchars($book['cover_image']); ?>"
                                     alt="<?php echo htmlspecialchars($book['title']); ?>" class="h-full object-cover w-full">
                            <?php else: ?>
                                <i class="fas fa-book text-6xl text-gray-400"></i>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-lg mb-1 truncate"><?php echo htmlspecialchars($book['title']); ?></h3>
                            <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($book['author']); ?></p>
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-sky-600">$<?php echo number_format($book['price'], 2); ?></span>
                                <a href="<?= BASE_URL ?>/buyer/book.php?id=<?php echo $book['id']; ?>"
                                   class="bg-sky-600 text-white py-1 px-3 rounded hover:bg-sky-700 transition duration-300 text-sm">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-gray-600">No new books available at the moment. Please check back later!</p>
        <?php endif; ?>
    </div>

    <!-- Call to Action for Sellers -->
    <?php if (!isLoggedIn()): ?>
        <div class="bg-sky-100 rounded-lg shadow p-8 text-center mt-12">
            <h2 class="text-3xl font-bold text-sky-800 mb-4">Are you an author or publisher?</h2>
            <p class="text-gray-700 mb-6 max-w-2xl mx-auto">
                Join our platform to sell your physical and digital books to a wide audience.
                It's easy to get started and manage your inventory.
            </p>
            <a href="<?= BASE_URL ?>/auth/register.php?role=seller" class="bg-sky-800 text-white px-8 py-4 rounded-full font-bold shadow-lg hover:bg-sky-900 transition duration-300">
                Become a Seller
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
    @keyframes fade-in-down {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fade-in-up {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes scale-in {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }
    .animate-fade-in-down { animation: fade-in-down 0.8s ease-out forwards; }
    .animate-fade-in-up { animation: fade-in-up 0.8s ease-out forwards 0.2s; } /* Delayed */
    .animate-scale-in { animation: scale-in 0.8s ease-out forwards 0.4s; } /* Further delayed */
</style>

<?php
require_once __DIR__ . '/includes/footer.php'; // Include the footer
?>

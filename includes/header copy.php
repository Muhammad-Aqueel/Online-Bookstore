<?php
    // We no longer call session_start() here.
    // It should be called once in the main entry file (e.g., index.php).

    require_once __DIR__ . '/../' . 'config/database.php';
    require_once BASE_PATH . '/includes/auth.php'; // Use the BASE_PATH constant

    $user = currentUser();
    $isLoggedIn = isLoggedIn();
    $isAdmin = hasRole('admin');
    $isSeller = hasRole('seller');
    $isBuyer = hasRole('buyer');

    // Calculate cart count for display in header if user is a buyer
    $cartCount = 0;
    if ($isBuyer && isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $cartCount += $item['quantity'];
        }
    }
    $_SESSION['cart_count'] = $cartCount; // Update session variable for header display (if buyer)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php if(isset($pageTitle)){echo htmlspecialchars($pageTitle) . " |";} ?> <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script> -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js for toggle -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/styles.css">
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal flex flex-col min-h-screen">
    <header class="bg-white shadow-md">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <a href="<?= BASE_URL ?>" class="hidden md:block text-2xl font-bold text-sky-800"><?php echo htmlspecialchars(SITE_NAME); ?></a>
            <!-- Search Box -->
            <form id="navbarSearchForm" action="<?= BASE_URL ?>/buyer/search.php" method="get" class="w-1/2 md:w-2/6">
                <?php
                // Preserve existing params except 'q' and 'page'
                foreach ($_GET as $key => $val) {
                    if (in_array($key, ['q','page'], true)) continue;
                    if (is_array($val)) {
                        foreach ($val as $v) {
                            echo '<input type="hidden" name="'.htmlspecialchars($key).'[]" value="'.htmlspecialchars($v).'">';
                        }
                    } else {
                        echo '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($val).'">';
                    }
                }
                ?>
                <div class="relative">
                    <input type="text" name="q" id="navbarSearchInput"
                        value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                        placeholder="Search books..."
                        autocomplete="off"
                        class="w-full border border-gray-300 rounded-full py-2 pl-4 pr-10 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-sky-600">
                        <i class="fas fa-search"></i>
                    </button>

                    <!-- Suggestions container -->
                    <div id="searchSuggestions" class="absolute left-0 right-0 bg-white border border-gray-300 rounded-b-lg shadow-lg z-50 hidden overflow-y-scroll max-h-[207px]"></div>
                </div>
            </form>
            <!-- Right side menu -->
            <div class="flex items-center space-x-4">
                <!-- Primary Navigation based on role -->
                <div class="hidden xl:flex items-center space-x-4">
                    <?php if ($isLoggedIn): ?>
                        <?php if ($isBuyer): ?>

                        <?php elseif ($isSeller): ?>

                        <?php elseif ($isAdmin): ?>

                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Public links for non-logged-in users -->
                        <a href="<?= BASE_URL ?>/" class="text-gray-600 hover:text-sky-600 transition-colors duration-300">
                        <i class="fas fa-home mr-1"></i> Home
                        </a>
                        <a href="<?= BASE_URL ?>/about.php" class="text-gray-600 hover:text-sky-600 transition-colors duration-300">
                        <i class="fas fa-info-circle mr-1"></i> About
                        </a>
                        <a href="<?= BASE_URL ?>/contact.php" class="text-gray-600 hover:text-sky-600 transition-colors duration-300">
                        <i class="fas fa-envelope mr-1"></i> Contact
                        </a>
                        <a href="<?= BASE_URL ?>/faq.php" class="text-gray-600 hover:text-sky-600 transition-colors duration-300">
                        <i class="fas fa-question-circle mr-1"></i> FAQ
                        </a>
                    <?php endif; ?>
                </div>
                <div class="space-x-4 flex items-center">
                    <?php if ($isLoggedIn): ?>
                        <?php if ($isBuyer): ?>
                            <a href="<?= BASE_URL ?>/buyer/cart.php" class="relative text-gray-600 hover:text-sky-600 transition-colors duration-300">
                                    <i class="fas fa-shopping-cart text-xl"></i>
                                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center opacity-80">
                                        <?php echo htmlspecialchars($_SESSION['cart_count'] ?? 0); ?>
                                    </span>
                                </a>
                        <?php endif; ?>
                        <div class="relative group">
                            <button class="flex items-center text-gray-800 focus:outline-none">
                                <img src="<?= BASE_URL ?>/assets/images/users/<?php echo htmlspecialchars($user['profile_picture'] ?? 'default.png'); ?>" alt="Profile" class="h-8 w-8 rounded-full object-cover mr-2">
                                <span><?php echo htmlspecialchars($user['username']); ?></span>
                            </button>
                            <div class="absolute right-0 w-48 bg-white rounded-md shadow-lg py-2 z-20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none group-hover:pointer-events-auto">
                                <?php if ($isAdmin): ?>
                                    <a href="<?= BASE_URL ?>/admin/" class="block px-4 py-2 text-gray-800 hover:bg-gray-200"><i class="fas fa-tools mr-2"></i>Dashboard</a>
                                    <a href="<?= BASE_URL ?>/admin/books.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200"><i class="fas fa-book mr-2"></i>Books</a>
                                    <a href="<?= BASE_URL ?>/admin/orders.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200"><i class="fas fa-shopping-cart mr-2"></i>Orders</a>
                                    <a href="<?= BASE_URL ?>/admin/earnings.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200"><i class="fas fa-chart-line mr-2"></i>Earnings</a>
                                    <a href="<?= BASE_URL ?>/admin/sellers.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200"><i class="fas fa-user-check mr-2"></i>Seller</a>
                                    <a href="<?= BASE_URL ?>/admin/users.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200"><i class="fas fa-users mr-2"></i>Users</a>
                                    <a href="<?= BASE_URL ?>/admin/settings.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200"><i class="fas fa-cog mr-2"></i>Settings</a>
                                <?php endif; ?>
                                <?php if ($isSeller): ?>
                                    <a href="<?= BASE_URL ?>/seller/" class="block px-4 py-2 text-gray-800 hover:bg-gray-200"><i class="fas fa-tools mr-2"></i>Dashboard</a>
                                    <a href="<?= BASE_URL ?>/seller/books.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200"><i class="fas fa-book mr-2"></i>My Books</a>
                                    <a href="<?= BASE_URL ?>/seller/add_book.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200"><i class="fas fa-circle-plus mr-2"></i>Add Book</a>
                                    <a href="<?= BASE_URL ?>/seller/earnings.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200"><i class="fas fa-chart-line mr-2"></i>Earnings</a>
                                    <a href="<?= BASE_URL ?>/seller/orders.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200"><i class="fas fa-shopping-cart mr-2"></i>Orders</a>
                                <?php endif; ?>
                                <?php if ($isBuyer): ?>
                                    <a href="<?= BASE_URL ?>/buyer/" class="block px-4 py-2 text-gray-800 hover:bg-gray-200"><i class="fas fa-book mr-2"></i>Books</a>
                                    <a href="<?= BASE_URL ?>/buyer/orders.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200"><i class="fas fa-receipt mr-2"></i>My Orders</a>
                                    <a href="<?= BASE_URL ?>/buyer/library.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200"><i class="fas fa-book-reader mr-2"></i>My Digital Library</a>
                                    <a href="<?= BASE_URL ?>/buyer/wishlist.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200"><i class="fas fa-heart mr-2"></i>Wishlist</a>
                                <?php endif; ?>
                                <a href="<?= BASE_URL ?>/auth/account.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-200"><i class="fas fa-user-circle mr-2"></i>My Profile</a>
                                <div class="border-t border-gray-200 my-2"></div>
                                <a href="<?= BASE_URL ?>/auth/logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-100"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/auth/login.php" class="bg-sky-600 text-white px-4 py-2 rounded-md hover:bg-sky-700 transition-colors duration-300">Login</a>
                        <a href="<?= BASE_URL ?>/auth/register.php" class="bg-sky-600 text-white px-4 py-2 rounded-md hover:bg-sky-700 transition-colors duration-300">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <main class="flex-grow">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 container mx-auto mt-4">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 container mx-auto mt-4">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['info'])): ?>
            <div class="bg-cyan-100 border border-cyan-400 text-cyan-600 px-4 py-3 rounded mb-4 container mx-auto mt-4">
                <?php echo $_SESSION['info']; unset($_SESSION['info']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['warning'])): ?>
            <div class="bg-yellow-100 border border-yellow-700 text-yellow-800 px-4 py-3 rounded mb-4 container mx-auto mt-4">
                <?php echo $_SESSION['warning']; unset($_SESSION['warning']); ?>
            </div>
        <?php endif; ?>
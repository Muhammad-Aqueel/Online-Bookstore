<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

requireAuth('buyer');

$user = currentUser();
$pageTitle = "Shopping Cart";

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
    $_SESSION['cart_total'] = 0;
    $_SESSION['cart_count'] = 0;
}

// Function to recalculate cart total and count
function recalculateCart() {
    $_SESSION['cart_total'] = 0;
    $_SESSION['cart_count'] = 0;
    foreach ($_SESSION['cart'] as $bookId => $item) {
        $_SESSION['cart_total'] += $item['price'] * $item['quantity'];
        $_SESSION['cart_count'] += $item['quantity'];
    }
}

// Handle remove from cart
if (isset($_GET['remove'])) {
    $bookId = $_GET['remove'];
    if (isset($_SESSION['cart'][$bookId])) {
        unset($_SESSION['cart'][$bookId]);
        recalculateCart(); // Recalculate after removal
        $_SESSION['info'] = "Book removed from cart successfully";
    }
    header('Location: ' . BASE_URL . '/buyer/cart.php');
    exit;
}

// Handle quantity update
if (isset($_POST['update_quantity'])) {
    $bookId = $_POST['book_id'];
    $quantity = max(1, (int)$_POST['quantity']);
    
    if (isset($_SESSION['cart'][$bookId])) {
        // Fetch current stock for physical books if necessary
        // This is a simplified check; a more robust solution would fetch from DB
        $stmt = $pdo->prepare("SELECT is_physical, stock FROM books WHERE id = ?");
        $stmt->execute([$bookId]);
        $bookDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($bookDetails && $bookDetails['is_physical'] && $quantity > $bookDetails['stock']) {
            $_SESSION['error'] = "Only " . (int)$bookDetails['stock'] . " " . ((int)$bookDetails['stock'] === 1 ? "copy" : "copies") . " of " . htmlspecialchars($_SESSION['cart'][$bookId]['title']) . ((int)$bookDetails['stock'] === 1 ? " is" : " are") . " available, and you already have " . (int)$_SESSION['cart'][$bookId]['quantity'] . " in your cart.";
        } else {
            $_SESSION['cart'][$bookId]['quantity'] = $quantity;
            recalculateCart(); // Recalculate after quantity update
            $_SESSION['success'] = "Cart updated successfully";
        }
    }
    header('Location: ' . BASE_URL . '/buyer/cart.php');
    exit;
}

// Get full book details for items in cart
$cartItems = [];
if (!empty($_SESSION['cart'])) {
    $bookIds = array_keys($_SESSION['cart']);
    // Ensure bookIds is not empty before generating placeholders
    if (!empty($bookIds)) {
        $placeholders = implode(',', array_fill(0, count($bookIds), '?'));
        
        $stmt = $pdo->prepare("SELECT id, title, author, price, cover_image, stock, is_physical FROM books WHERE id IN ($placeholders)");
        $stmt->execute($bookIds);
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($books as $book) {
            // Ensure the book still exists in the cart session, as it might have been removed
            if (isset($_SESSION['cart'][$book['id']])) {
                $cartItems[] = [
                    'id' => $book['id'],
                    'title' => $book['title'],
                    'author' => $book['author'],
                    'price' => $book['price'],
                    'stock' => $book['stock'],
                    'is_physical' => $book['is_physical'],
                    'cover_image' => $book['cover_image'],
                    'quantity' => $_SESSION['cart'][$book['id']]['quantity'],
                    'is_digital' => $_SESSION['cart'][$book['id']]['is_digital']
                ];
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-sky-800 mb-6">Shopping Cart</h1>

    <?php if (empty($cartItems)): ?>
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <p class="text-gray-600 mb-4">Your cart is empty</p>
            <a href="<?= BASE_URL ?>/buyer/" class="bg-sky-600 text-white px-4 py-2 rounded hover:bg-sky-700">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="data-table min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-16 w-16 bg-gray-200 rounded-md overflow-hidden">
                                                <?php if ($item['cover_image']): ?>
                                                    <img src="<?= BASE_URL ?>/assets/images/books/<?php echo htmlspecialchars($item['cover_image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="h-full w-full object-cover">
                                                <?php else: ?>
                                                    <div class="h-full w-full flex items-center justify-center text-gray-400">
                                                        <i class="fas fa-book text-xl"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-4">
                                                <h3 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['title']); ?></h3>
                                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($item['author']); ?></p>
                                                <span class="text-xs text-gray-500 mt-1">
                                                    <?php echo $item['is_digital'] ? 'eBook' : 'Physical Book'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        $<?php echo number_format($item['price'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <form method="post" class="flex items-center">
                                            <input type="hidden" name="book_id" value="<?php echo $item['id']; ?>">
                                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo ($item['is_physical']) ? $item['stock'] : '99'; ?>" class="w-16 px-2 py-1 border rounded text-center">
                                            <button type="submit" name="update_quantity" class="ml-2 text-xs bg-gray-200 text-gray-700 px-2 py-1 rounded hover:bg-gray-300">
                                                Update
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="<?= BASE_URL ?>/buyer/cart.php?remove=<?php echo $item['id']; ?>" class="text-red-600 hover:text-red-800">Remove</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>
                    <div class="space-y-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-medium">$<?php echo number_format($_SESSION['cart_total'], 2); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Shipping</span>
                            <span class="font-medium">$0.00</span>
                        </div>
                        <div class="border-t border-gray-200 pt-4 flex justify-between">
                            <span class="text-lg font-semibold">Total</span>
                            <span class="text-lg font-bold text-sky-600">$<?php echo number_format($_SESSION['cart_total'], 2); ?></span>
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>/buyer/checkout.php" class="mt-6 w-full bg-sky-600 text-white py-2 px-4 rounded-md hover:bg-sky-700 flex items-center justify-center">
                        Proceed to Checkout
                    </a>
                    <a href="<?= BASE_URL ?>/buyer/" class="mt-4 w-full bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 flex items-center justify-center">
                        Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
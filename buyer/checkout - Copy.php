<?php

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireAuth('buyer');

$user = currentUser();
$pageTitle = "Checkout";

// Redirect if cart is empty
if (empty($_SESSION['cart'])) {
    $_SESSION['error'] = "Your cart is empty.";
    header('Location: ' . BASE_URL . '/buyer/cart.php');
    exit;
}

// Generate CSRF token
$csrfToken = generateCsrfToken();

// Recalculate totals
if (!isset($_SESSION['cart_total']) || !isset($_SESSION['cart_count'])) {
    $_SESSION['cart_total'] = 0;
    $_SESSION['cart_count'] = 0;
    foreach ($_SESSION['cart'] as $item) {
        $_SESSION['cart_total'] += $item['price'] * $item['quantity'];
        $_SESSION['cart_count'] += $item['quantity'];
    }
}

// Get cart items with book details
$cartItems = [];
$bookIds = array_keys($_SESSION['cart']);
if (!empty($bookIds)) {
    $placeholders = implode(',', array_fill(0, count($bookIds), '?'));
    $stmt = $pdo->prepare("SELECT id, title, author, price, cover_image FROM books WHERE id IN ($placeholders)");
    $stmt->execute($bookIds);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($books as $book) {
        $cartItems[] = [
            'id' => $book['id'],
            'title' => $book['title'],
            'author' => $book['author'],
            'price' => $book['price'],
            'cover_image' => $book['cover_image'],
            'quantity' => $_SESSION['cart'][$book['id']]['quantity'],
            'is_digital' => $_SESSION['cart'][$book['id']]['is_digital'],
        ];
    }
}
include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-sky-800 mb-6">Checkout</h1>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="bg-red-100 text-red-700 p-4 mb-6 rounded">
            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Shipping Information</h2>
                <form action="<?= BASE_URL ?>/buyer/checkout_process.php" method="post" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="payment_method" value="Credit Card">
                    
                    <label class="block">
                        <span class="text-gray-700">Full Name</span>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>" class="mt-1 block w-full border px-3 py-2 rounded" required>
                    </label>

                    <label class="block">
                        <span class="text-gray-700">Address</span>
                        <textarea name="shipping_address" class="mt-1 block w-full border px-3 py-2 rounded" required><?= htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </label>

                    <label class="block">
                        <span class="text-gray-700">Phone Number</span>
                        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? ''); ?>" class="mt-1 block w-full border px-3 py-2 rounded" required>
                    </label>

                    <h2 class="text-lg font-semibold mt-6 mb-4">Payment Method</h2>
                    <label class="block">
                        <span class="text-gray-700">Card Number</span>
                        <input type="text" name="card_number" class="mt-1 block w-full border px-3 py-2 rounded" required>
                    </label>

                    <div class="grid grid-cols-2 gap-4">
                        <label class="block">
                            <span class="text-gray-700">Expiry (MM/YY)</span>
                            <input type="text" name="expiry" class="mt-1 block w-full border px-3 py-2 rounded" required>
                        </label>
                        <label class="block">
                            <span class="text-gray-700">CVV</span>
                            <input type="text" name="cvv" class="mt-1 block w-full border px-3 py-2 rounded" required>
                        </label>
                    </div>

                    <button type="submit" class="mt-4 bg-sky-600 text-white py-2 px-4 rounded hover:bg-sky-700">
                        Place Order
                    </button>
                </form>
            </div>
        </div>

        <div>
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Order Summary</h2>
                <div class="space-y-4">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="flex justify-between">
                            <span><?= htmlspecialchars($item['title']) ?> x <?= $item['quantity'] ?></span>
                            <span>$<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="border-t border-gray-200 pt-4 flex justify-between">
                        <span class="font-semibold">Subtotal</span>
                        <span class="font-semibold">$<?= number_format($_SESSION['cart_total'], 2) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span>Shipping</span>
                        <span>$0.00</span>
                    </div>
                    <div class="border-t border-gray-200 pt-4 flex justify-between">
                        <span class="text-lg font-bold">Total</span>
                        <span class="text-lg font-bold text-sky-600">$<?= number_format($_SESSION['cart_total'], 2) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

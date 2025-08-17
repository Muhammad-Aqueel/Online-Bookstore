<?php
require_once __DIR__ . '/includes/header.php'; // Include the header
?>

<div class="container mx-auto px-4 py-8 max-w-3xl">
    <h1 class="text-3xl font-bold text-sky-800 mb-6 text-center">Frequently Asked Questions</h1>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="space-y-4">
            <!-- FAQ Item 1 -->
            <div class="border-b border-gray-200 pb-4">
                <button class="flex justify-between items-center w-full text-left font-semibold text-lg text-gray-800"
                        onclick="toggleAnswer(this)">
                    <span>How do I place an order?</span>
                    <i class="fas fa-chevron-down text-gray-500"></i>
                </button>
                <div class="mt-2 text-gray-700 hidden">
                    <p>To place an order, simply browse our collection, add desired books to your cart, and proceed to checkout. Follow the prompts to enter your shipping details and payment information.</p>
                </div>
            </div>

            <!-- FAQ Item 2 -->
            <div class="border-b border-gray-200 pb-4">
                <button class="flex justify-between items-center w-full text-left font-semibold text-lg text-gray-800"
                        onclick="toggleAnswer(this)">
                    <span>What payment methods do you accept?</span>
                    <i class="fas fa-chevron-down text-gray-500"></i>
                </button>
                <div class="mt-2 text-gray-700 hidden">
                    <p>We accept major credit cards (Visa, MasterCard, American Express) and PayPal. All transactions are securely processed.</p>
                </div>
            </div>

            <!-- FAQ Item 3 -->
            <div class="border-b border-gray-200 pb-4">
                <button class="flex justify-between items-center w-full text-left font-semibold text-lg text-gray-800"
                        onclick="toggleAnswer(this)">
                    <span>How can I track my order?</span>
                    <i class="fas fa-chevron-down text-gray-500"></i>
                </button>
                <div class="mt-2 text-gray-700 hidden">
                    <p>Once your order has shipped, you will receive an email with a tracking number. You can use this number on the carrier's website or directly from your "My Orders" page if you are a registered user.</p>
                </div>
            </div>

            <!-- FAQ Item 4 -->
            <div class="border-b border-gray-200 pb-4">
                <button class="flex justify-between items-center w-full text-left font-semibold text-lg text-gray-800"
                        onclick="toggleAnswer(this)">
                    <span>Do you offer international shipping?</span>
                    <i class="fas fa-chevron-down text-gray-500"></i>
                </button>
                <div class="mt-2 text-gray-700 hidden">
                    <p>Yes, we offer international shipping to most countries. Shipping costs and delivery times vary based on destination. Please see our <a href="shipping.php" class="text-sky-600 hover:underline">Shipping Policy</a> for more details.</p>
                </div>
            </div>

            <!-- FAQ Item 5 -->
            <div class="pb-4">
                <button class="flex justify-between items-center w-full text-left font-semibold text-lg text-gray-800"
                        onclick="toggleAnswer(this)">
                    <span>What is your return policy?</span>
                    <i class="fas fa-chevron-down text-gray-500"></i>
                </button>
                <div class="mt-2 text-gray-700 hidden">
                    <p>We accept returns of physical books within 30 days of purchase, provided they are in their original condition. Digital books are generally non-refundable. For full details, please review our <a href="returns.php" class="text-sky-600 hover:underline">Returns & Refunds Policy</a>.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleAnswer(button) {
        const answer = button.nextElementSibling;
        const icon = button.querySelector('i');
        answer.classList.toggle('hidden');
        icon.classList.toggle('fa-chevron-down');
        icon.classList.toggle('fa-chevron-up');
    }
</script>

<?php
require_once __DIR__ . '/includes/footer.php'; // Include the footer
?>

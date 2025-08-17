<?php
require_once __DIR__ . '/includes/header.php'; // Include the header
?>

<div class="container mx-auto px-4 py-8 max-w-3xl">
    <h1 class="text-3xl font-bold text-sky-800 mb-6 text-center">Shipping Policy</h1>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Domestic Shipping</h2>
        <p class="text-gray-700 leading-relaxed mb-4">
            We offer various shipping options for domestic orders within [Your Country/Region].
            Standard shipping typically takes **3-7 business days**, while expedited options
            are available for faster delivery at an additional cost.
        </p>
        <ul class="list-disc list-inside text-gray-700 space-y-2">
            <li>Standard Shipping: Free on orders over $50, otherwise $5.99</li>
            <li>Expedited Shipping: $12.99 (2-3 business days)</li>
            <li>Overnight Shipping: $24.99 (1 business day, orders placed before 2 PM local time)</li>
        </ul>
        <p class="text-gray-700 leading-relaxed mt-4">
            Please note that delivery times are estimates and may vary due to unforeseen circumstances
            or peak seasons.
        </p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">International Shipping</h2>
        <p class="text-gray-700 leading-relaxed mb-4">
            We are proud to offer international shipping to a wide range of countries.
            International shipping costs are calculated at checkout based on the weight of your order
            and the destination.
        </p>
        <ul class="list-disc list-inside text-gray-700 space-y-2">
            <li>Delivery times: Typically **7-21 business days**, depending on the destination and customs processing.</li>
            <li>Customs Duties & Taxes: Please be aware that international orders may be subject to import duties,
                taxes, and customs fees imposed by the destination country. These charges are the
                responsibility of the recipient.</li>
        </ul>
        <p class="text-gray-700 leading-relaxed mt-4">
            For specific inquiries regarding international shipping to your country, please
            <a href="contact.php" class="text-sky-600 hover:underline">contact us</a>.
        </p>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mt-6">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Digital Products</h2>
        <p class="text-gray-700 leading-relaxed">
            Digital books are available for immediate download upon successful payment.
            No shipping fees apply to digital products. You can access your purchased eBooks
            from your "My Digital Library" page.
        </p>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php'; // Include the footer
?>

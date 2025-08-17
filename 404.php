<?php
// Set the HTTP response code to 404
http_response_code(404);

// Include the header
require_once __DIR__ . '/includes/header.php';
?>

<div class="container mx-auto px-4 py-16 text-center min-h-screen flex flex-col justify-center items-center">
    <h1 class="text-6xl font-extrabold text-red-600 mb-4">404</h1>
    <h2 class="text-3xl font-bold text-gray-800 mb-6">Page Not Found</h2>
    <p class="text-gray-600 mb-8 max-w-lg">
        The page you’re looking for doesn’t exist, has been moved, or is temporarily unavailable.
        Please check the URL or return to the homepage.
    </p>
    <a href="<?= BASE_URL ?>/" class="bg-sky-600 text-white px-6 py-3 rounded-lg shadow-md hover:bg-sky-700 transition duration-300">
        Go to Homepage
    </a>
</div>

<?php
// Include the footer
require_once __DIR__ . '/includes/footer.php';
?>

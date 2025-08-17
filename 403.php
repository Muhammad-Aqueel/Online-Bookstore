<?php
// Set the HTTP response code to 403 
http_response_code(403);

require_once __DIR__ . '/includes/header.php'; // Include the header
?>

<div class="container mx-auto px-4 py-16 text-center min-h-screen flex flex-col justify-center items-center">
    <h1 class="text-6xl font-extrabold text-red-600 mb-4">403</h1>
    <h2 class="text-3xl font-bold text-gray-800 mb-6">Access Denied</h2>
    <p class="text-gray-600 mb-8 max-w-lg">
        You do not have the necessary permissions to view this page.
        Please check your account role or contact the administrator if you believe this is an error.
    </p>
    <a href="<?= BASE_URL ?>/" class="bg-sky-600 text-white px-6 py-3 rounded-lg shadow-md hover:bg-sky-700 transition duration-300">
        Go to Homepage
    </a>
</div>

<?php
require_once __DIR__ . '/includes/footer.php'; // Include the footer
?>
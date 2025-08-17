<?php
require_once __DIR__ . '/includes/header.php'; // Include the header
?>

<div class="container mx-auto px-4 py-8 max-w-2xl">
    <h1 class="text-3xl font-bold text-sky-800 mb-6 text-center">Contact Us</h1>

    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-700 mb-6 text-center">
            Have a question, feedback, or need assistance? Fill out the form below or reach out to us directly!
        </p>

        <form action="#" method="POST" class="space-y-4">
            <div>
                <label for="name" class="block text-gray-700 text-sm font-bold mb-1">Your Name</label>
                <input type="text" id="name" name="name" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>
            <div>
                <label for="email" class="block text-gray-700 text-sm font-bold mb-1">Your Email</label>
                <input type="email" id="email" name="email" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>
            <div>
                <label for="subject" class="block text-gray-700 text-sm font-bold mb-1">Subject</label>
                <input type="text" id="subject" name="subject" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>
            <div>
                <label for="message" class="block text-gray-700 text-sm font-bold mb-1">Message</label>
                <textarea id="message" name="message" rows="6" required
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-sky-500"></textarea>
            </div>
            
            <button type="submit" class="w-full bg-sky-600 text-white py-2 px-4 rounded-md hover:bg-sky-700 transition duration-300">
                Send Message
            </button>
        </form>

        <div class="mt-8 text-center text-gray-600">
            <p>Alternatively, you can email us directly at: <a href="mailto:support@bookstore.com" class="text-sky-600 hover:text-sky-800">support@bookstore.com</a></p>
            <p class="mt-2">Or call us at: <a href="tel:+92123456789" class="text-sky-600 hover:text-sky-800">+921234567890</a></p>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php'; // Include the footer
?>
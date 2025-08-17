<?php
require_once __DIR__ . '/includes/header.php'; // Include the header
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-sky-800 mb-6">About Our Bookstore</h1>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Our Story</h2>
        <p class="text-gray-700 leading-relaxed mb-4">
            Welcome to **BookStore**, your premier online destination for a vast collection of literary treasures.
            Founded in <?php echo date('Y') - 5; ?> with a passion for books and a vision to make reading accessible to everyone,
            we've grown into a thriving community for book lovers worldwide.
        </p>
        <p class="text-gray-700 leading-relaxed">
            We believe in the power of stories to transform, educate, and inspire. From thrilling novels
            to insightful non-fiction, academic texts to enchanting children's books, our curated selection
            offers something for every reader.
        </p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Our Mission</h2>
        <p class="text-gray-700 leading-relaxed mb-4">
            Our mission is simple: to connect readers with the books they'll love, foster a vibrant reading
            culture, and support independent authors and publishers. We strive to offer both physical copies
            for those who cherish the feel of a book in their hands, and convenient digital formats for
            the modern reader on the go.
        </p>
        <p class="text-gray-700 leading-relaxed">
            Thank you for being a part of our journey. Happy reading!
        </p>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php'; // Include the footer
?>
<div class="flex items-center justify-between mb-6 flex-wrap">
    <h1 class="text-xl font-semibold text-sky-800">
        <?= $query !== '' ? 'Results for “' . htmlspecialchars($query) . '”' : 'All Books'; ?>
        <span class="ml-2 text-sm text-gray-500">(<?= (int)$totalBooks; ?>)</span>
    </h1>

    <div class="flex items-center">
        <span class="mr-2 text-gray-600">Sort by:</span>
        <form id="sortForm" method="get" class="inline">
            <?php
            // Preserve all parameters except sort & page (page resets on sort)
            $preserveKeys = array_diff(array_keys($_GET), ['sort','page']);
            foreach ($preserveKeys as $key) {
                $val = $_GET[$key];
                if (is_array($val)) {
                    foreach ($val as $v) {
                        echo '<input type="hidden" name="'.htmlspecialchars($key).'[]" value="'.htmlspecialchars($v).'">';
                    }
                } else {
                    echo '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($val).'">';
                }
            }
            ?>
            <select name="sort" class="border rounded px-3 py-1">
                <option value="relevance" <?= $sort==='relevance'?'selected':''; ?>>Relevance</option>
                <option value="price_asc" <?= $sort==='price_asc'?'selected':''; ?>>Price: Low to High</option>
                <option value="price_desc" <?= $sort==='price_desc'?'selected':''; ?>>Price: High to Low</option>
                <option value="newest" <?= $sort==='newest'?'selected':''; ?>>Newest</option>
                <option value="rating" <?= $sort==='rating'?'selected':''; ?>>Highest Rated</option>
            </select>
        </form>
    </div>
</div>

<?php if (empty($books)): ?>
    <div class="bg-white rounded-lg shadow p-8 text-center">
        <p class="text-gray-600 mb-4">No books found matching your search.</p>
        <a href="<?= BASE_URL ?>/buyer/search.php" class="bg-sky-600 text-white px-4 py-2 rounded hover:bg-sky-700">Browse All</a>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($books as $book): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow">
                <div class="h-48 bg-gray-200 flex items-center justify-center">
                    <?php if (!empty($book['cover_image'])): ?>
                        <img src="<?= BASE_URL ?>/assets/images/books/<?= htmlspecialchars($book['cover_image']); ?>"
                             alt="<?= htmlspecialchars($book['title']); ?>" class="h-full w-full object-cover">
                    <?php else: ?>
                        <!-- No Image -->
                        <i class="fas fa-book text-6xl text-gray-400"></i>
                    <?php endif; ?>
                </div>
                <div class="p-4">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-lg mb-1"><?= htmlspecialchars($book['title']); ?></h3>
                        <?php $csrfToken = generateCsrfToken(); ?>
                        <a href="<?= BASE_URL ?>/buyer/wishlist.php?add=<?= (int)$book['id']; ?>&csrf_token=<?= $csrfToken; ?>"
                           class="text-gray-600 hover:text-sky-600 flex items-center" title="Add to wishlist" target="_blank" data-tooltip="Add to wishlist">
                           <i class="<?= in_array($book['id'], $wishlistBooks) ? 'text-red-600 fas' : 'far'; ?> fa-heart mr-1"></i>
                        </a>
                    </div>
                    <p class="text-gray-600 mb-2"><?= htmlspecialchars($book['author']); ?></p>
                    <p class="text-sm text-gray-500 mb-2">Seller: <?= htmlspecialchars($book['store_name']); ?></p>
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-sky-600">$<?= number_format((float)$book['price'], 2); ?></span>
                        <a href="<?= BASE_URL ?>/buyer/book.php?id=<?= (int)$book['id']; ?>"
                           class="bg-sky-600 text-white py-1 px-3 rounded hover:bg-sky-700" target="_blank">View</a>
                    </div>
                    <div class="mt-2 text-yellow-500 text-sm">
                        <?= str_repeat('★', (int)round($book['avg_rating'])) ?>
                        <?= str_repeat('☆', 5 - (int)round($book['avg_rating'])) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <?php
        // Build pagination links while preserving params
        $params = $_GET;
        unset($params['page']);

        $build_page_url = function($pageNum, $params) {
            $params['page'] = $pageNum;
            return 'search.php?' . http_build_query($params);
        };
        ?>
        <div class="mt-8 flex justify-center">
            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <!-- Prev -->
                <?php if ($page > 1): ?>
                    <a href="<?= htmlspecialchars($build_page_url($page - 1, $params)); ?>"
                       class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php else: ?>
                    <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-200 bg-gray-100 text-sm font-medium text-gray-300">
                        <i class="fas fa-chevron-left"></i>
                    </span>
                <?php endif; ?>

                <!-- Pages -->
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="<?= htmlspecialchars($build_page_url($i, $params)); ?>"
                       class="<?= $i === $page ? 'bg-sky-50 border-sky-500 text-sky-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                        <?= $i; ?>
                    </a>
                <?php endfor; ?>

                <!-- Next -->
                <?php if ($page < $totalPages): ?>
                    <a href="<?= htmlspecialchars($build_page_url($page + 1, $params)); ?>"
                       class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-200 bg-gray-100 text-sm font-medium text-gray-300">
                        <i class="fas fa-chevron-right"></i>
                    </span>
                <?php endif; ?>
            </nav>
        </div>
    <?php endif; ?>
<?php endif; ?>

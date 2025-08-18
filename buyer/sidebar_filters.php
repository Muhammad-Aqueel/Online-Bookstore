<div id="filterSidebar" class="bg-white p-4 rounded-lg shadow sticky top-4 hidden md:block origin-top">
    <h3 class="font-semibold text-lg text-sky-800 mb-4 hidden md:block">Filters</h3>
    <form method="get" action="search.php">
        <?php
        // Preserve all existing GET params except the ones controlled by this form and 'page'
        $preserveKeys = array_diff(array_keys($_GET), [
            'search_fields', 'category', 'seller', 'rating', 'min_price', 'max_price', 'format', 'page', 'q'
        ]);
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

        <!-- Search In -->
        <div class="mb-6">
            <h4 class="font-bold text-gray-900 mb-2">Search In</h4>
            <?php foreach (['title'=>'Title','author'=>'Author','isbn'=>'ISBN','description'=>'Description'] as $field => $label): ?>
                <label class="flex items-center">
                    <input type="checkbox" name="search_fields[]" value="<?php echo $field; ?>"
                        <?php echo in_array($field, $searchFields, true) ? 'checked' : ''; ?> class="mr-2">
                    <span><?php echo $label; ?></span>
                </label>
            <?php endforeach; ?>
        </div>

        <!-- Categories -->
        <div class="mb-6">
            <h4 class="font-bold text-gray-900 mb-2">Categories</h4>
            <!-- All Categories toggle -->
            <div id="categoryList" class="overflow-auto max-h-[150px]">
                <?php renderTree($categoryTree); ?>
            </div>
        </div>

        <!-- Seller -->
        <div class="mb-6">
            <h4 class="font-bold text-gray-900 mb-2">Seller</h4>
            <select name="seller" class="w-full border rounded px-2 py-1">
                <option value="">All Sellers</option>
                <?php foreach ($sellers as $s): ?>
                    <option value="<?= (int)$s['id']; ?>" <?= $seller == $s['id'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($s['store_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Rating -->
        <div class="mb-6">
            <h4 class="font-bold text-gray-900 mb-2">Minimum Rating</h4>
            <select name="rating" class="w-full border rounded px-2 py-1">
                <option value="">All</option>
                <?php for ($i=5; $i>=1; $i--): ?>
                    <option value="<?= $i; ?>" <?= $rating == $i ? 'selected' : ''; ?>>
                        <?= $i; ?> stars & up
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <!-- Price -->
        <div class="mb-6">
            <h4 class="font-bold text-gray-900 mb-2">Price Range</h4>
            <div class="flex items-center space-x-2">
                <input type="number" name="min_price" placeholder="Min"
                       value="<?= $minPrice !== null ? htmlspecialchars($minPrice) : ''; ?>"
                       class="w-full px-2 py-1 border rounded" min="0">
                <span>to</span>
                <input type="number" name="max_price" placeholder="Max"
                       value="<?= $maxPrice !== null ? htmlspecialchars($maxPrice) : ''; ?>"
                       class="w-full px-2 py-1 border rounded" min="0">
            </div>
        </div>

        <!-- Format -->
        <div class="mb-6">
            <h4 class="font-bold text-gray-900 mb-2">Format</h4>
            <label class="flex items-center">
                <input type="radio" name="format" value="" <?php echo !$format ? 'checked' : ''; ?> class="mr-2">
                <span>All</span>
            </label>
            <label class="flex items-center">
                <input type="radio" name="format" value="physical" <?php echo $format === 'physical' ? 'checked' : ''; ?> class="mr-2">
                <span>Physical</span>
            </label>
            <label class="flex items-center">
                <input type="radio" name="format" value="digital" <?php echo $format === 'digital' ? 'checked' : ''; ?> class="mr-2">
                <span>eBook</span>
            </label>
        </div>

        <button type="button" id="resetAllBtn" class="w-full bg-sky-600 text-white py-2 px-4 rounded hover:bg-sky-700">Reset All</button>
    </form>
</div>

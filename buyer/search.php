<?php
    
    require_once '../config/database.php';
    require_once '../includes/auth.php';
    require_once '../includes/helpers.php';

    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    $pageTitle = "Search Results";

    /* === FILTERS === */
    $category  = !empty($_GET['category']) ? (int)$_GET['category'] : null;
    $minPrice  = (isset($_GET['min_price']) && $_GET['min_price'] !== '') ? (float)$_GET['min_price'] : null;
    $maxPrice  = (isset($_GET['max_price']) && $_GET['max_price'] !== '') ? (float)$_GET['max_price'] : null;
    $format    = isset($_GET['format']) ? $_GET['format'] : null;
    $seller    = !empty($_GET['seller']) ? (int)$_GET['seller'] : null;
    $rating    = !empty($_GET['rating']) ? (int)$_GET['rating'] : null;

    /* Which fields to search */
    $searchFields = isset($_GET['search_fields']) && is_array($_GET['search_fields'])
        ? array_intersect($_GET['search_fields'], ['title','author','isbn','description'])
        : ['title','author','isbn','description'];

    /* === BASE QUERY === */
    $sql = "
        SELECT b.*,
            COALESCE(AVG(r.rating),0) as avg_rating,
            s.store_name
        FROM books b
        LEFT JOIN reviews r ON r.book_id = b.id
        LEFT JOIN seller_profiles s ON s.user_id = b.seller_id
        WHERE b.approved = 1
    ";
    $params = [];

    /* === SEARCH CONDITIONS (bug fix for short tokens) === */
    if ($query !== '') {
        $conditions = [];
        foreach ($searchFields as $field) {
            // if (in_array($field, ['title','author','isbn','description'], true)) {
                $conditions[] = "b.$field LIKE ?";
                $params[] = "%$query%";
            // }
        }
        if ($conditions) {
            $sql .= " AND (" . implode(" OR ", $conditions) . ")";
        }
    }

    /* === APPLY FILTERS === */
    if ($category) {
        $categoryIds = array_map('intval', $_GET['category']);
        // Ignore empty "All Categories" selection
        if (!in_array('', $categoryIds, true)) {
            $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
            $sql .= " AND EXISTS (
                SELECT 1 FROM book_categories bc
                WHERE bc.book_id = b.id AND bc.category_id IN ($placeholders)
            )";
            $params = array_merge($params, $categoryIds);
        }
    }
    if ($minPrice !== null) { $sql .= " AND b.price >= ?"; $params[] = $minPrice; }
    if ($maxPrice !== null) { $sql .= " AND b.price <= ?"; $params[] = $maxPrice; }
    if ($format === 'physical') { $sql .= " AND b.is_physical = 1"; }
    elseif ($format === 'digital') { $sql .= " AND b.is_digital = 1"; }
    if ($seller) { $sql .= " AND b.seller_id = ?"; $params[] = $seller; }

    /* === GROUP === */
    $sql .= " GROUP BY b.id ";

    /* === HAVING RATING === */
    if ($rating) { $sql .= " HAVING avg_rating >= ?"; $params[] = $rating; }

    /* === SORTING === */
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'relevance';
    switch ($sort) {
        case 'price_asc':  $sql .= " ORDER BY b.price ASC"; break;
        case 'price_desc': $sql .= " ORDER BY b.price DESC"; break;
        case 'newest':     $sql .= " ORDER BY b.created_at DESC"; break;
        case 'rating':     $sql .= " ORDER BY avg_rating DESC, b.title ASC"; break;
        default:
            if ($query !== '') {
                // Lightweight "relevance"
                $sql .= " ORDER BY 
                    (CASE WHEN b.title LIKE ? THEN 0 ELSE 1 END),
                    (CASE WHEN b.author LIKE ? THEN 1 ELSE 2 END),
                    (CASE WHEN b.isbn LIKE ? THEN 2 ELSE 3 END),
                    b.created_at DESC";
                $params = array_merge($params, ["$query%", "$query%", "$query%"]);
            } else {
                $sql .= " ORDER BY b.created_at DESC";
            }
    }

    /* === PAGINATION === */
    $page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit  = 12;
    $offset = ($page - 1) * $limit;
    $sql .= " LIMIT $limit OFFSET $offset";

    /* === EXECUTE === */
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* === COUNT TOTAL === */
    $countSql = "SELECT COUNT(DISTINCT b.id) FROM books b WHERE b.approved = 1";
    $countParams = [];
    if ($query !== '') {
        $conditions = [];
        foreach ($searchFields as $field) {
            if (in_array($field, ['title','author','isbn','description'], true)) {
                $conditions[] = "b.$field LIKE ?";
                $countParams[] = "%$query%";
            }
        }
        if ($conditions) $countSql .= " AND (" . implode(" OR ", $conditions) . ")";
    }

    if ($category) {
        $categoryIds = array_map('intval', $_GET['category']);
        if (!in_array('', $categoryIds, true)) { // ignore empty "All Categories"
            $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
            $countSql .= " AND EXISTS (
                SELECT 1 FROM book_categories bc
                WHERE bc.book_id = b.id AND bc.category_id IN ($placeholders)
            )";
            $countParams = array_merge($countParams, $categoryIds);
        }
    }
    
    if ($minPrice !== null) { $countSql .= " AND b.price >= ?"; $countParams[] = $minPrice; }
    if ($maxPrice !== null) { $countSql .= " AND b.price <= ?"; $countParams[] = $maxPrice; }
    if ($format === 'physical') { $countSql .= " AND b.is_physical = 1"; }
    elseif ($format === 'digital') { $countSql .= " AND b.is_digital = 1"; }
    if ($seller) { $countSql .= " AND b.seller_id = ?"; $countParams[] = $seller; }

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $totalBooks = (int)$countStmt->fetchColumn();
    $totalPages = (int)ceil($totalBooks / $limit);

    /* === FILTER OPTIONS === */
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    // Convert flat array to tree
    function buildTree(array $elements, $parentId = null) {
        $branch = [];
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    $categoryTree = buildTree($categories);

    // ==============================
    // Render tree recursively
    // ==============================
    function renderTree($tree) {
        echo '<ul class="category-tree">';
        foreach ($tree as $node) {
            $hasChildren = !empty($node['children']);
            echo '<li>';
            // Add toggle button if node has children
            if ($hasChildren) {
                echo '<span class="toggle"><i class="fa fa-plus-square toggleicon"></i></span> ';
            } else {
                echo '<span class="toggle-placeholder"><i class="fa fa-square"></i></span> ';
            }
            echo '<input type="checkbox" class="category" name="category[]" 
                    value="'.$node['id'].'" 
                    data-id="'.$node['id'].'" 
                    id="cat_'.$node['id'].'">';
            echo '<label for="cat_'.$node['id'].'"> '.$node['name'].'</label>';
            if ($hasChildren) {
                echo '<div class="children" style="display:none;">';
                    renderTree($node['children']);
                echo '</div>';
            }
            echo '</li>';
        }
        echo '</ul>';
    }

    $sellers = $pdo->query("SELECT u.id, s.store_name FROM users u JOIN seller_profiles s ON u.id = s.user_id WHERE u.role='seller'")->fetchAll(PDO::FETCH_ASSOC);

    /* === USER WISHLIST === */
    try {
        $wishlistStmt = $pdo->prepare("SELECT book_id FROM wishlists WHERE user_id = :user_id");
        $wishlistStmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $wishlistStmt->execute();
        $wishlistBooks = $wishlistStmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $wishlistBooks = [];
    }

    /* === AJAX DETECTION === */
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($isAjax) {
        include 'search_results.php';
        exit;
    }

    include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row gap-2 md:gap-6">
        <!-- Sidebar Filters -->
        <div class="w-full md:w-1/4" id="filterSidebarContainer">
            <button id="filterToggle" class="md:hidden w-full flex justify-between items-center bg-sky-600 text-white px-4 py-2 rounded">
                <span>Filters</span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <?php include 'sidebar_filters.php'; ?>
        </div>

        <!-- Results -->
        <div class="w-full md:w-3/4" id="search-results">
            <?php include 'search_results.php'; ?>
        </div>
    </div>
</div>

<script>
    let state = new URLSearchParams(window.location.search);

    function ajaxLoad() {
        fetch('search.php?' + state.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            document.getElementById('search-results').innerHTML = html;
            history.pushState({}, '', 'search.php?' + state.toString());
        })
        .catch(console.error);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const filterForm = document.querySelector('#filterSidebar form');
        const resultsContainer = document.getElementById('search-results');

        if (filterForm) {
            function applyFilters(checkboxID) {
                const formData = new FormData(filterForm);

                // Remove all existing sidebar-related keys from state
                const arrayKeys = new Set();
                for (const [key] of formData.entries()) {
                    if (key.endsWith('[]')) arrayKeys.add(key);
                    else state.delete(key);
                }
                arrayKeys.forEach(k => state.delete(k));

                // Add updated form data
                for (const [key, value] of formData.entries()) {
                    if (key.endsWith('[]')) {
                        state.append(key, value);
                    } else {
                        state.set(key, value);
                    }
                }
                // on unchecked, if all checkbox unchecked then remove category[] from GET parameters
                if (checkboxID && !checkboxID.checked) {
                    // check if *all* category checkboxes are now unchecked
                    const allUnchecked = document.querySelectorAll('#categoryList input.category:checked').length === 0;
                    if (allUnchecked) {
                        state.delete("category[]");
                    }
                }
                state.set('page', '1'); // reset page on filter change
                ajaxLoad();
            }

            // Get all categories values form GET parameters on page reload
            const categories = state.getAll("category[]");

            // Loop through categories and check matching checkboxes
            categories.forEach(cat => {
            const checkbox = document.getElementById("cat_" + cat);
            if (checkbox) {
                checkbox.checked = true;
            }
            });

            // Auto-apply on change for checkboxes, radios, selects
            filterForm.addEventListener('change', function (event) {
                const el = event.target;
                if (el.type === "checkbox") {
                    // pass the checkbox id
                    applyFilters(el);
                } else {
                    // call without id
                    applyFilters();
                }
            });

            // Auto-apply for price inputs with debounce
            let priceTimeout;
            filterForm.querySelectorAll('input[type="number"]').forEach(input => {
                input.addEventListener('input', function() {
                    clearTimeout(priceTimeout);
                    priceTimeout = setTimeout(applyFilters, 500);
                });
            });
        }

        // Sorting (delegated)
        resultsContainer.addEventListener('change', function(e) {
            const select = e.target.closest('#sortForm select');
            if (select) {
                state.set('sort', select.value);
                state.set('page', '1');
                ajaxLoad();
            }
        });

        // Pagination (delegated)
        resultsContainer.addEventListener('click', function(e) {
            const link = e.target.closest('a[href]');
            if (link && link.closest('nav[aria-label="Pagination"]')) {
                e.preventDefault();
                const url = new URL(link.href, window.location.origin);
                state.set('page', url.searchParams.get('page') || '1');
                ajaxLoad();
            }
        });

        // Navbar search
        const navbarForm = document.getElementById('navbarSearchForm') ||
                        document.querySelector(`form[action$="/buyer/search.php"]`);
        if (navbarForm) {
            navbarForm.addEventListener('submit', function(e) {
                const onSearchPage = /\/buyer\/search\.php$/i.test(location.pathname);
                if (!onSearchPage) return;
                e.preventDefault();
                const fd = new FormData(navbarForm);
                state.delete('q');
                state.delete('page');
                for (const [key, value] of fd.entries()) {
                    if (key.endsWith('[]')) state.append(key, value);
                    else state.set(key, value);
                }
                state.set('page', '1');
                ajaxLoad();
            });
        }

        // Browser back/forward
        window.addEventListener('popstate', function() {
            state = new URLSearchParams(window.location.search);
            fetch(location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.text())
            .then(html => resultsContainer.innerHTML = html)
            .catch(console.error);
        });
        
        // prevent last (in search) checkbox to unchecked
        const searchFieldCheckboxes = document.querySelectorAll('input[name="search_fields[]"]');

        searchFieldCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function(e) {
                const checkedBoxes = Array.from(searchFieldCheckboxes).filter(cb => cb.checked);
                if (checkedBoxes.length === 0) {
                    // Prevent unchecking the last box
                    e.preventDefault();
                    this.checked = true;
                    alert("You must have at least one search field selected.");
                }
            });
        });

        // reset all filters + sort + searchbox
        const resetBtn = document.getElementById('resetAllBtn');
        const navbarSearch = document.querySelector('#navbarSearchForm input[name="q"]');

        if (resetBtn && filterForm) {
            resetBtn.addEventListener('click', function() {
                // Reset all form fields to default
                filterForm.reset();

                // Reset navbar search if present
                if (navbarSearch) {
                    navbarSearch.value = '';
                }

                // Clear all params from state
                state = new URLSearchParams(); 

                // Ensure default page
                state.set('page', '1');

                // Run AJAX load
                ajaxLoad();
            });
        }
    });
</script>

<?php include '../includes/footer.php'; ?>

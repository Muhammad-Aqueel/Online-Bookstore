        </main>
    </div>
</div>
        
        <footer class="bg-sky-800 text-white py-8">
            <div class="container mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div>
                        <h3 class="text-xl font-bold mb-4">BookStore</h3>
                        <p class="text-sky-200">Your one-stop shop for all kinds of books, both physical and digital.</p>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-4">Quick Links</h4>
                        <ul class="space-y-2">
                            <li><a href="<?= BASE_URL ?>/" class="text-sky-200 hover:text-white">Home</a></li>
                            <li><a href="<?= BASE_URL ?>/about.php" class="text-sky-200 hover:text-white">About Us</a></li>
                            <li><a href="<?= BASE_URL ?>/contact.php" class="text-sky-200 hover:text-white">Contact</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-4">Customer Service</h4>
                        <ul class="space-y-2">
                            <li><a href="<?= BASE_URL ?>/faq.php" class="text-sky-200 hover:text-white">FAQ</a></li>
                            <li><a href="<?= BASE_URL ?>/shipping.php" class="text-sky-200 hover:text-white">Shipping Policy</a></li>
                            <li><a href="<?= BASE_URL ?>/returns.php" class="text-sky-200 hover:text-white">Returns & Refunds</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold mb-4">Connect With Us</h4>
                        <div class="flex space-x-4">
                            <a href="#" class="text-2xl text-sky-200 hover:text-white"><i class="fab fa-facebook"></i></a>
                            <a href="#" class="text-2xl text-sky-200 hover:text-white"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="text-2xl text-sky-200 hover:text-white"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                <div class="border-t border-sky-700 mt-8 pt-6 text-center text-sky-200">
                    <p>&copy; <?php echo date('Y'); ?> Online BookStore. All rights reserved.</p>
                </div>
            </div>
        </footer>
        <?php $current_page = basename($_SERVER['PHP_SELF']); if($current_page === 'edit_book.php' || $current_page === 'add_book.php' ): ?>
            <script>
                function toggleStockField() {
                    const isPhysicalCheckbox = document.getElementById('is_physical');
                    const stockInput = document.getElementById('stock');
                    if (isPhysicalCheckbox.checked) {
                        stockInput.removeAttribute('disabled');
                    } else {
                        stockInput.setAttribute('disabled', 'disabled');
                        stockInput.value = '0'; // Set stock to 0 if not physical
                    }
                }

                // Initial call on page load to set states correctly based on server-side values (if any)
                document.addEventListener('DOMContentLoaded', () => {
                    toggleDigitalFields();
                    toggleStockField();
                });

                // Auto-select parent categories when a child is checked
                document.querySelectorAll('.category').forEach(function(checkbox) {
                    checkbox.addEventListener('change', function() {
                        if(this.checked){
                            selectParents(this);
                        } else {
                            // If unchecked, uncheck all children recursively
                            uncheckChildren(this);
                        }
                    });
                });

                // Recursive function to select parents
                function selectParents(childCheckbox){
                    let li = childCheckbox.closest('li').parentElement.closest('li');
                    if(li){
                        let parentCheckbox = li.querySelector('input.category');
                        if(parentCheckbox && !parentCheckbox.checked){
                            parentCheckbox.checked = true;
                            selectParents(parentCheckbox);
                        }
                    }
                }

                // Recursive function to uncheck all children
                function uncheckChildren(parentCheckbox){
                    let li = parentCheckbox.closest('li');
                    let childCheckboxes = li.querySelectorAll('ul input.category');
                    childCheckboxes.forEach(function(cb){
                        cb.checked = false;
                    });
                }
            </script>
        <?php endif; ?>
        <?php $current_page = basename($_SERVER['PHP_SELF']); if($current_page === 'edit_book.php' || $current_page === 'add_book.php' || $current_page === 'search.php'): ?>
            <script>
                // +/- toggle
                document.querySelectorAll('.toggleicon').forEach(function(toggle) {
                    toggle.addEventListener('click', function() {
                        if (toggle.classList.contains('placeholder')) return; // skip leaves

                        let parentLi = toggle.closest('li');
                        let childrenContainer = parentLi.querySelector('.children');
                        if (childrenContainer) {
                            if (childrenContainer.style.display === 'none') {
                                childrenContainer.style.display = 'block';
                                toggle.classList.remove('fa-plus-square');
                                toggle.classList.add('fa-minus-square');
                            } else {
                                childrenContainer.style.display = 'none';
                                toggle.classList.remove('fa-minus-square');
                                toggle.classList.add('fa-plus-square');
                            }
                        }
                    });
                });
                document.addEventListener('DOMContentLoaded', function() {
                    document.querySelectorAll('.children').forEach(function(c) {
                        c.style.display = 'block';
                    });
                    document.querySelectorAll('.toggleicon').forEach(function(icon) {
                        icon.classList.remove('fa-plus-square');
                        icon.classList.add('fa-minus-square');
                    });
                });
            </script>
        <?php endif; ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // show sugestions
                const searchInput = document.getElementById('navbarSearchInput');
                const suggestionsBox = document.getElementById('searchSuggestions');

                if (!searchInput || !suggestionsBox) return;

                let suggestTimeout;

                searchInput.addEventListener('input', function() {
                    clearTimeout(suggestTimeout);
                    const query = this.value.trim();
                    if (query.length < 2) {
                        suggestionsBox.classList.add('hidden');
                        return;
                    }

                    suggestTimeout = setTimeout(() => {
                        // Get currently checked search_fields[]
                        const checkedFields = Array.from(document.querySelectorAll('#filterSidebar input[name="search_fields[]"]:checked'))
                            .map(cb => cb.value);

                        // Build query params
                        const params = new URLSearchParams();
                        params.set('q', query);
                        checkedFields.forEach(f => params.append('search_fields[]', f));

                        fetch('<?= BASE_URL ?>/buyer/search_suggest.php?' + params.toString())
                            .then(res => res.json())
                            .then(data => {
                                if (data.length === 0) {
                                    suggestionsBox.classList.add('hidden');
                                    return;
                                }

                                suggestionsBox.innerHTML = data.map(item =>
                                    `<div class="px-4 py-2 hover:bg-gray-100 cursor-pointer">${item}</div>`
                                ).join('');

                                suggestionsBox.classList.remove('hidden');

                                // Clicking a suggestion
                                suggestionsBox.querySelectorAll('div').forEach(div => {
                                    div.addEventListener('click', function() {
                                        searchInput.value = this.textContent;
                                        suggestionsBox.classList.add('hidden');
                                    });
                                });
                            })
                            .catch(console.error);
                    }, 300); // debounce
                });

                // Hide when clicking outside
                document.addEventListener('click', function(e) {
                    if (!suggestionsBox.contains(e.target) && e.target !== searchInput) {
                        suggestionsBox.classList.add('hidden');
                    }
                });
            });
        </script>
        <script src="<?= BASE_URL; ?>/assets/js/main.js"></script>
    </body>
</html>

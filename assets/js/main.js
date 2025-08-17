// main.js - Vanilla JS version
document.addEventListener('DOMContentLoaded', function() {
    // ------------------------------
    // AJAX buttons with spinner
    // ------------------------------
    document.querySelectorAll('[data-ajax-button]').forEach(button => {
        button.addEventListener('click', function() {
            const originalText = this.innerHTML;
            this.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> ${this.dataset.loadingText || 'Processing...'}`;
            this.disabled = true;

            // Fallback to revert after 5 seconds
            setTimeout(() => {
                if (this.disabled) {
                    this.innerHTML = originalText;
                    this.disabled = false;
                }
            }, 5000);
        });
    });

    // ------------------------------
    // Tooltips (vanilla version)
    // ------------------------------
    document.querySelectorAll('[data-tooltip]').forEach(el => {
        el.addEventListener('mouseenter', function() {
            let tooltip = document.createElement('div');
            tooltip.className = 'tooltip-box';
            tooltip.textContent = el.getAttribute('data-tooltip');
            document.body.appendChild(tooltip);
    
            const rect = el.getBoundingClientRect();
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 5 + window.scrollY) + 'px';
            tooltip.style.left = (rect.left + (rect.width - tooltip.offsetWidth)/2 + window.scrollX) + 'px';
    
            el._tooltip = tooltip;
        });
    
        el.addEventListener('mouseleave', function() {
            if (el._tooltip) {
                el._tooltip.remove();
                el._tooltip = null;
            }
        });
    });

    // ------------------------------
    // Collapsible Filters Sidebar
    // ------------------------------
    const filterToggle = document.getElementById('filterToggle');
    const filterSidebar = document.getElementById('filterSidebar');
    if (filterToggle && filterSidebar) {
        filterToggle.addEventListener('click', () => {
            filterSidebar.classList.toggle('hidden');
        });
    }

    // ------------------------------
    // Simple DataTable replacement
    // ------------------------------
    // Vanilla JS sortable tables (basic)
    document.querySelectorAll('.data-table th').forEach((th, index) => {
        th.addEventListener('click', () => {
          const table = th.closest('table');
          const tbody = table.querySelector('tbody');
          const rows = Array.from(tbody.querySelectorAll('tr'));
          const isAscending = th.classList.contains('asc');
      
          rows.sort((a, b) => {
            const aText = a.children[index].textContent.trim();
            const bText = b.children[index].textContent.trim();
      
            // If numeric, compare as numbers
            if (!isNaN(aText) && !isNaN(bText)) {
              return isAscending ? aText - bText : bText - aText;
            }
      
            return isAscending ? aText.localeCompare(bText) : bText.localeCompare(aText);
          });
      
          // Re-append sorted rows
          rows.forEach(row => tbody.appendChild(row));
      
          // Toggle sort classes
          th.classList.toggle('asc', !isAscending);
          th.classList.toggle('desc', isAscending);
        });
    });
});

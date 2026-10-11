    </div> <!-- /container -->
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Check if we are on a tabbed page
    var tabEls = document.querySelectorAll('button[data-bs-toggle="tab"], a[data-bs-toggle="tab"]');
    if (tabEls.length > 0) {
        // Create a unique key for the page
        var pageKey = "activeTab_" + window.location.pathname;
        
        // Restore tab
        var activeTab = sessionStorage.getItem(pageKey);
        if (activeTab) {
            var el = document.querySelector('[data-bs-target="' + activeTab + '"]');
            if (el) {
                var tab = new bootstrap.Tab(el);
                tab.show();
            }
        }
        
        // Save tab on change
        tabEls.forEach(function(el) {
            el.addEventListener('shown.bs.tab', function (event) {
                sessionStorage.setItem(pageKey, event.target.getAttribute('data-bs-target'));
            });
        });
    }
});
</script>

(function($) {
  "use strict";
  $(document).ready(function() {
    $('#sync-products-btn').on('click', function(e) {
        e.preventDefault();
        // Simulate API call for product sync.
        alert("Initiating Product Sync...");
    });
  });
})(jQuery);
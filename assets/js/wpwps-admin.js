/**
 * Admin JS for Printify Sync
 */
jQuery(function($) {
    // Toggle mobile navigation
    $('#navbar-toggler').on('click', function() {
        $('.wpwps-navbar-menu-wrapper').toggleClass('active');
    });

    // Close mobile menu when clicking outside
    $(document).on('click', function(e) {
        if (
            !$(e.target).closest('.wpwps-navbar-menu-wrapper').length &&
            !$(e.target).closest('#navbar-toggler').length &&
            $('.wpwps-navbar-menu-wrapper').hasClass('active')
        ) {
            $('.wpwps-navbar-menu-wrapper').removeClass('active');
        }
    });

    // Handle window resize
    $(window).on('resize', function() {
        if ($(window).width() > 992 && $('.wpwps-navbar-menu-wrapper').hasClass('active')) {
            $('.wpwps-navbar-menu-wrapper').removeClass('active');
        }
    });
    
    // Dropdown functionality
    $('.wpwps-dropdown-toggle').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $parent = $(this).parent('.wpwps-dropdown');
        const isActive = $parent.hasClass('show');
        
        // Close all other dropdowns
        $('.wpwps-dropdown').not($parent).removeClass('show');
        
        // Toggle current dropdown
        $parent.toggleClass('show', !isActive);
    });
    
    // Close dropdowns when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.wpwps-dropdown').length) {
            $('.wpwps-dropdown').removeClass('show');
        }
    });
    
    // Role dropdown item click
    $('#role-dropdown-menu .wpwps-dropdown-item').on('click', function(e) {
        e.preventDefault();
        
        // Update active state
        $('#role-dropdown-menu .wpwps-dropdown-item').removeClass('active');
        $(this).addClass('active');
        
        // Update badge text
        const roleText = $(this).text().trim();
        $('#role-dropdown-toggle .wpwps-role-badge').text(roleText);
        
        // Close dropdown
        $(this).closest('.wpwps-dropdown').removeClass('show');
    });
});

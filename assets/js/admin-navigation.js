/**
 * Admin Navigation Functionality
 * 
 * Handles hamburger menu, sidebar collapse, and responsive adjustments
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const hamburgerBtn = document.getElementById('sidebar-toggle');
    const collapseSidebarBtn = document.getElementById('collapse-sidebar-btn');
    const sidebar = document.getElementById('sidebar-nav');
    const dashboard = document.querySelector('.printify-dashboard-wrapper');
    
    // Toggle sidebar visibility on mobile
    if (hamburgerBtn) {
        hamburgerBtn.addEventListener('click', function() {
            this.classList.toggle('hamburger-open');
            sidebar.classList.toggle('sidebar-visible');
        });
    }
    
    // Collapse/expand sidebar
    if (collapseSidebarBtn) {
        collapseSidebarBtn.addEventListener('click', function() {
            sidebar.classList.toggle('sidebar-collapsed');
            
            // Save user preference
            const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
            localStorage.setItem('printify_sidebar_collapsed', isCollapsed ? 'true' : 'false');
        });
    }
    
    // Check if sidebar was previously collapsed
    if (localStorage.getItem('printify_sidebar_collapsed') === 'true') {
        sidebar.classList.add('sidebar-collapsed');
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const isMobile = window.innerWidth <= 992;
        const isClickInsideSidebar = sidebar.contains(event.target);
        const isClickOnHamburger = hamburgerBtn.contains(event.target);
        
        if (isMobile && sidebar.classList.contains('sidebar-visible') && 
            !isClickInsideSidebar && !isClickOnHamburger) {
            sidebar.classList.remove('sidebar-visible');
            hamburgerBtn.classList.remove('hamburger-open');
        }
    });
    
    // User dropdown menu handling
    const userProfile = document.querySelector('.user-profile');
    const userDropdown = document.querySelector('.user-menu-dropdown');
    
    if (userProfile && userDropdown) {
        userProfile.addEventListener('click', function(event) {
            event.stopPropagation();
            userDropdown.classList.toggle('show-dropdown');
        });
        
        document.addEventListener('click', function(event) {
            if (!userProfile.contains(event.target)) {
                userDropdown.classList.remove('show-dropdown');
            }
        });
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            hamburgerBtn.classList.remove('hamburger-open');
            sidebar.classList.remove('sidebar-visible');
        }
    });
});
jQuery(document).ready(function($) {
    var menuIcon = $('#toplevel_page_wpwprintifysync .dashicons-store');
    if (menuIcon.length) {
        menuIcon.removeClass('dashicons dashicons-store');
        menuIcon.addClass('fas fa-tshirt');
    }
});
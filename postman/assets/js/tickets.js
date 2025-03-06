/* Tickets page scripts go here */
jQuery(document).ready(function($) {
    console.log('Tickets JS loaded');

    // Add event listeners for ticket actions if needed
    $('.btn-danger').on('click', function() {
        alert('Ticket closed');
    });
});
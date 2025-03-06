/* Exchange Rate page scripts go here */
jQuery(document).ready(function($) {
    console.log('Exchange Rate JS loaded');

    // Add event listeners for exchange rate actions if needed
    $('.btn-primary').on('click', function() {
        alert('Exchange rate updated');
    });

    $('.btn-success').on('click', function() {
        alert('All exchange rates updated');
    });
});
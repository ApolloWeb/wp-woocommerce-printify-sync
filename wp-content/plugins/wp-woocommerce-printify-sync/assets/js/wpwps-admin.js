jQuery(document).ready(function($) {
    console.log('WP WooCommerce Printify Sync admin loaded.');

    // Initialize Chart.js if element exists
    if ($('#myChart').length) {
        const ctx = document.getElementById('myChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Red', 'Blue', 'Yellow'],
                datasets: [{
                    label: 'Demo Data',
                    data: [12, 19, 3],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)'
                    ],
                    borderColor: [
                        'rgba(255,99,132,1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: { responsive: true }
        });
    }

    // New: Initialize Sales Chart
    if ($('#salesChart').length) {
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['January', 'February', 'March', 'April'],
                datasets: [{
                    label: 'Sales',
                    data: [150, 200, 180, 220],
                    backgroundColor: 'rgba(150, 88, 138, 0.2)',
                    borderColor: 'rgba(150, 88, 138, 1)',
                    fill: true
                }]
            },
            options: { responsive: true }
        });
    }

    // Simulate loading widget data for queued emails
    setTimeout(function() {
        $('#queued-emails').html('5 emails are queued for delivery.');
    }, 1000);

    // Simulate loading widget data for import queue progress
    setTimeout(function() {
        $('#import-queue-progress').html('<progress value="30" max="100"></progress> 30% complete');
    }, 1200);

    // Simulate loading widget data for sync results
    setTimeout(function() {
        $('#sync-results').html('Last sync: Success with 3 products updated.');
    }, 1400);

    // Handle Test Connection AJAX
    $('#test-connection').on('click', function(e) {
        e.preventDefault();
        $('#test-connection-result').html('Testing...');
        var data = {
            action: 'wpwpps_test_connection',
            printify_api_key: $('#printify_api_key').val(),
            api_endpoint: $('#api_endpoint').val()
        };
        $.post(wpwpps_ajax.ajax_url, data, function(response) {
            if(response.success) {
                $('#test-connection-result').html('Success: ' + response.data.message);
                var shopSelect = $('#shop_id');
                shopSelect.empty();
                $.each(response.data.shops, function(index, shop) {
                    shopSelect.append($('<option>', { value: shop.id, text: shop.name }));
                });
            } else {
                $('#test-connection-result').html('Error: ' + response.data.message);
            }
        });
    });

    // Handle Settings Form Submission AJAX
    $('#wpwpps-settings-form').on('submit', function(e) {
        e.preventDefault();
        var data = {
            action: 'wpwpps_save_settings',
            printify_api_key: $('#printify_api_key').val(),
            api_endpoint: $('#api_endpoint').val(),
            shop_id: $('#shop_id').val(),
            monthly_spend_cap: $('#monthly_spend_cap').val(),
            tokens: $('#tokens').val(),
            temperature: $('#temperature').val()
        };
        $.post(wpwpps_ajax.ajax_url, data, function(response) {
            if(response.success) {
                $('#save-settings-result').html(response.data.message);
            } else {
                $('#save-settings-result').html('Error: ' + response.data.message);
            }
        });
    });

    // Handle Test Monthly Estimate AJAX
    $('#test-monthly-estimate').on('click', function(e) {
        e.preventDefault();
        $('#monthly-estimate-result').html('Calculating...');
        var data = {
            action: 'wpwpps_test_monthly_estimate',
            monthly_spend_cap: $('#monthly_spend_cap').val(),
            tokens: $('#tokens').val(),
            temperature: $('#temperature').val()
        };
        $.post(wpwpps_ajax.ajax_url, data, function(response) {
            if(response.success) {
                $('#monthly-estimate-result').html('Estimated cost: ' + response.data.estimate);
            } else {
                $('#monthly-estimate-result').html('Error calculating estimate');
            }
        });
    });
});

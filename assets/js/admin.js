jQuery(document).ready(function($) {
    // Initialize Materialize components
    $('.sidenav').sidenav();
    $('.collapsible').collapsible();
    $('.modal').modal();
    $('.tooltipped').tooltip();

    // Toggle API key visibility
    $('.toggle-visibility').on('click', function() {
        var input = $(this).siblings('input');
        var type = input.attr('type') === 'password' ? 'text' : 'password';
        input.attr('type', type);
        $(this).toggleClass('fa-eye fa-eye-slash');
    });

    // Save API key
    $('#save-api-key-btn').on('click', function() {
        var apiKey = $('#api-key').val();
        $.ajax({
            url: wpwcsAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'save_api_key',
                api_key: apiKey
            },
            success: function(response) {
                if (response.success) {
                    M.toast({html: 'API Key saved successfully.'});
                    $('#api-key').val('****************');
                } else {
                    M.toast({html: 'Failed to save API Key.'});
                }
            },
            error: function() {
                M.toast({html: 'An error occurred while saving API Key.'});
            }
        });
    });

    // Dummy data for chart
    var ctx = document.getElementById('sales-graph').getContext('2d');
    var chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
            datasets: [{
                label: 'Sales',
                data: [10, 20, 30, 40, 50, 60, 70],
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                fill: true,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Sales Over Time'
                }
            }
        }
    });

    // Circular progress bar for product import progress
    var progressBar = new ProgressBar.Circle('#product-import-progress', {
        color: '#3498db',
        strokeWidth: 6,
        trailWidth: 2,
        easing: 'easeInOut',
        duration: 1400,
        text: {
            autoStyleContainer: false
        },
        from: { color: '#3498db', width: 2 },
        to: { color: '#3498db', width: 6 },
        step: function(state, circle) {
            circle.path.setAttribute('stroke', state.color);
            circle.path.setAttribute('stroke-width', state.width);

            var value = Math.round(circle.value() * 100);
            if (value === 0) {
                circle.setText('');
            } else {
                circle.setText(value + '%');
            }
        }
    });
    progressBar.text.style.fontFamily = '"Raleway", Helvetica, sans-serif';
    progressBar.text.style.fontSize = '2rem';

    // Simulate product import progress
    var simulateProgress = function() {
        var value = 0;
        var interval = setInterval(function() {
            value += 0.01;
            progressBar.animate(value);
            if (value >= 1) {
                clearInterval(interval);
            }
        }, 100);
    };
    simulateProgress();
});
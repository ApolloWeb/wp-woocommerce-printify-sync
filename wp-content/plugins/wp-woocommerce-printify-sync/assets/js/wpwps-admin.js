jQuery(document).ready(function($) {
    // Toast notifications
    function showToast(message, type = 'success') {
        const toast = $(`
            <div class="wpwps-toast wpwps-toast-${type}">
                <div class="wpwps-toast-content">${message}</div>
            </div>
        `);
        
        $('.wpwps-container').append(toast);
        
        setTimeout(() => {
            toast.addClass('show');
        }, 100);
        
        setTimeout(() => {
            toast.removeClass('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    // Estimated cost calculator
    function updateEstimatedCost() {
        const tokenLimit = parseInt($('#token_limit').val()) || 0;
        const temperature = parseFloat($('#temperature').val()) || 0;
        const costPer1k = 0.002; // GPT-3.5-Turbo rate
        
        const estimatedCost = (tokenLimit / 1000) * costPer1k * temperature;
        $('#estimated-cost').text(estimatedCost.toFixed(4));
    }
    
    $('#token_limit, #temperature').on('input', updateEstimatedCost);
    
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Handle form submission feedback
    $(document).ajaxSuccess(function(event, xhr, settings) {
        if (settings.data && settings.data.includes('wpwps_save_settings')) {
            showToast('Settings saved successfully');
        }
    });
    
    $(document).ajaxError(function() {
        showToast('An error occurred', 'error');
    });
});
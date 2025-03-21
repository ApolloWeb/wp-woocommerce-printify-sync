(function($) {
    'use strict';

    function showToast(message, type = 'success') {
        const toast = $(`
            <div class="wpwps-toast wpwps-toast-${type}">
                <div class="wpwps-toast-header">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                    <button type="button" class="btn-close"></button>
                </div>
                <div class="wpwps-toast-body">${message}</div>
            </div>
        `).appendTo('.wpwps-toast-container');
        
        setTimeout(() => toast.fadeOut(300, () => toast.remove()), 5000);
        toast.find('.btn-close').on('click', () => toast.remove());
    }

    // ...existing code...

    function testGptConnection() {
        $('#test-gpt-connection').on('click', function() {
            const button = $(this);
            const gptApiKey = $('#gpt_api_key').val();
            const gptTokens = $('#gpt_tokens').val();
            const gptTemperature = $('#gpt_temperature').val();
            const gptBudget = $('#gpt_budget').val();
            
            if (!gptApiKey) {
                showToast('Please enter your OpenAI API key.', 'warning');
                return;
            }

            if (gptBudget < 1) {
                showToast('Monthly budget must be at least $1', 'warning');
                return;
            }
            
            button.addClass('loading').prop('disabled', true);
            
            $.ajax({
                url: wpwps.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpwps_test_gpt_connection',
                    nonce: wpwps.nonce,
                    gpt_api_key: gptApiKey,
                    gpt_tokens: gptTokens,
                    gpt_temperature: gptTemperature,
                    gpt_budget: gptBudget
                },
                success: function(response) {
                    if (response.success) {
                        showToast(response.data.message);
                        
                        $('#gpt-cost-daily').text(response.data.estimated_cost.per_day.toFixed(2));
                        $('#gpt-cost-monthly').text(response.data.estimated_cost.per_month.toFixed(2));
                        $('.gpt-cost-estimate-container').show();

                        if (response.data.budget_warning) {
                            showToast(response.data.budget_warning, 'warning');
                        }
                    } else {
                        showToast(response.data.message, 'danger');
                    }
                },
                error: function() {
                    showToast('An error occurred. Please try again.', 'danger');
                },
                complete: function() {
                    button.removeClass('loading').prop('disabled', false);
                }
            });
        });
    }

    // ...existing code...
})(jQuery);

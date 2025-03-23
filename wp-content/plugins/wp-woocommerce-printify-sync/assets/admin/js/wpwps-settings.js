(function($) {
    'use strict';

    const Settings = {
        init() {
            this.initializeFormHandlers();
            this.initializeTestHandlers();
            this.initializePasswordToggles();
        },

        initializeFormHandlers() {
            $('#wpps-settings-form').on('submit', (e) => {
                e.preventDefault();
                this.saveSettings();
            });

            $('#estimate_cost').on('click', () => this.estimateCost());
        },

        initializeTestHandlers() {
            $('#test_printify').on('click', () => this.testPrintifyConnection());
            $('#test_chatgpt').on('click', () => this.testChatGPTConnection());
        },

        initializePasswordToggles() {
            $('.toggle-password').on('click', function() {
                const input = $(this).closest('.input-group').find('input');
                const icon = $(this).find('i');
                
                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });
        },

        async saveSettings() {
            try {
                const response = await $.post(wppsAdmin.ajaxUrl, {
                    action: 'wpps_save_settings',
                    _ajax_nonce: wppsAdmin.nonce,
                    ...this.getFormData()
                });

                if (response.success) {
                    wppsAdmin.showToast(response.data.message, 'success');
                } else {
                    wppsAdmin.showToast(response.data.message, 'error');
                }
            } catch (error) {
                wppsAdmin.showToast(error.message, 'error');
            }
        },

        async testPrintifyConnection() {
            try {
                const response = await $.post(wppsAdmin.ajaxUrl, {
                    action: 'wpps_test_printify',
                    _ajax_nonce: wppsAdmin.nonce,
                    api_key: $('#printify_key').val()
                });

                if (response.success) {
                    wppsAdmin.showToast(response.data.message, 'success');
                    this.populateShopSelector(response.data.shops);
                } else {
                    wppsAdmin.showToast(response.data.message, 'error');
                }
            } catch (error) {
                wppsAdmin.showToast(error.message, 'error');
            }
        },

        async testChatGPTConnection() {
            try {
                const response = await $.post(wppsAdmin.ajaxUrl, {
                    action: 'wpps_test_chatgpt',
                    _ajax_nonce: wppsAdmin.nonce,
                    api_key: $('#chatgpt_key').val()
                });

                if (response.success) {
                    wppsAdmin.showToast(response.data.message, 'success');
                } else {
                    wppsAdmin.showToast(response.data.message, 'error');
                }
            } catch (error) {
                wppsAdmin.showToast(error.message, 'error');
            }
        },

        estimateCost() {
            const tokenLimit = parseInt($('#token_limit').val());
            const monthlyCap = parseInt($('#monthly_cap').val());
            const costPer1k = 0.002;
            const estimatedCost = (tokenLimit * monthlyCap * costPer1k) / 1000;
            
            wppsAdmin.showToast(
                `Estimated monthly cost: $${estimatedCost.toFixed(2)}`,
                'info'
            );
        },

        populateShopSelector(shops) {
            const $select = $('#shop_id');
            $select.empty().append(
                `<option value="">${wppsL10n.select_shop}</option>`
            );
            
            shops.forEach(shop => {
                $select.append(
                    `<option value="${shop.id}">${shop.title}</option>`
                );
            });

            $('.shop-selector').removeClass('d-none');
        },

        getFormData() {
            const form = document.getElementById('wpps-settings-form');
            const formData = new FormData(form);
            return Object.fromEntries(formData.entries());
        }
    };

    $(document).ready(() => Settings.init());

})(jQuery);

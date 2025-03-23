(function($) {
    'use strict';
    
    const Settings = {
        init() {
            this.initFormHandlers();
            this.initTestHandlers();
            this.initPasswordToggles();
        },
        
        initFormHandlers() {
            $('#wpwps-settings-form').on('submit', (e) => {
                e.preventDefault();
                this.saveSettings();
            });
            
            $('#wpwps-estimate-cost').on('click', () => this.estimateCost());
        },
        
        initTestHandlers() {
            $('#wpwps-test-printify').on('click', () => this.testPrintifyConnection());
            $('#wpwps-test-chatgpt').on('click', () => this.testChatGPTConnection());
        },
        
        initPasswordToggles() {
            $('.wpwps-toggle-password').on('click', function() {
                const input = $(this).closest('.wpwps-input-group').find('input');
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
                const form = document.getElementById('wpwps-settings-form');
                const formData = WPWPS.form.serialize(form);
                
                const response = await WPWPS.api.post('save_settings', formData);
                
                if (response.success) {
                    WPWPS.toast.success(response.data.message);
                } else {
                    WPWPS.toast.error(response.data.message || wppsAdmin.i18n.error);
                }
            } catch (error) {
                WPWPS.toast.error(error.message || wppsAdmin.i18n.error);
            }
        },
        
        async testPrintifyConnection() {
            try {
                WPWPS.toast.info(wppsAdmin.i18n.testing);
                
                const apiKey = $('#wpwps-printify-key').val();
                if (!apiKey) {
                    WPWPS.toast.error(wppsAdmin.i18n.noApiKey);
                    return;
                }
                
                const response = await WPWPS.api.post('test_printify', {
                    api_key: apiKey
                });
                
                if (response.success) {
                    WPWPS.toast.success(response.data.message);
                    this.populateShopSelector(response.data.shops);
                } else {
                    WPWPS.toast.error(response.data.message || wppsAdmin.i18n.connectionFailed);
                }
            } catch (error) {
                WPWPS.toast.error(error.message || wppsAdmin.i18n.connectionFailed);
            }
        },
        
        async testChatGPTConnection() {
            try {
                WPWPS.toast.info(wppsAdmin.i18n.testing);
                
                const apiKey = $('#wpwps-chatgpt-key').val();
                if (!apiKey) {
                    WPWPS.toast.error(wppsAdmin.i18n.noApiKey);
                    return;
                }
                
                const response = await WPWPS.api.post('test_chatgpt', {
                    api_key: apiKey,
                    model: $('#wpwps-chatgpt-model').val()
                });
                
                if (response.success) {
                    WPWPS.toast.success(response.data.message);
                } else {
                    WPWPS.toast.error(response.data.message || wppsAdmin.i18n.connectionFailed);
                }
            } catch (error) {
                WPWPS.toast.error(error.message || wppsAdmin.i18n.connectionFailed);
            }
        },
        
        estimateCost() {
            const tokenLimit = parseInt($('#wpwps-token-limit').val()) || 1000;
            const monthlyCap = parseInt($('#wpwps-monthly-cap').val()) || 100;
            const model = $('#wpwps-chatgpt-model').val();
            
            // Rate per 1000 tokens - adjust for GPT-4 which is more expensive
            const costPer1k = model.includes('gpt-4') ? 0.03 : 0.002;
            const estimatedCost = (tokenLimit * monthlyCap * costPer1k) / 1000;
            
            WPWPS.toast.info(
                wppsAdmin.i18n.estimatedCost.replace('{cost}', estimatedCost.toFixed(2))
            );
        },
        
        populateShopSelector(shops) {
            if (!shops || !shops.length) return;
            
            const $select = $('#wpwps-shop-id');
            $select.empty().append(
                `<option value="">${wppsAdmin.i18n.selectShop}</option>`
            );
            
            shops.forEach(shop => {
                $select.append(
                    `<option value="${shop.id}">${shop.title}</option>`
                );
            });
            
            $('.wpwps-shop-selector').removeClass('d-none');
        }
    };
    
    $(document).ready(() => Settings.init());
    
})(jQuery);

const WPWPS = window.WPWPS || {};

WPWPS.Settings = {
    init() {
        this.initApiSettings();
        this.initSyncSettings();
        this.initCacheSettings();
        this.initImageSettings();
        this.bindEvents();
    },

    initApiSettings() {
        // API Key verification
        $('#verify-api-key').on('click', () => {
            this.verifyApiKey();
        });

        // Toggle API key visibility
        $('#toggle-api-key').on('click', (e) => {
            const input = $('#api-key');
            const icon = $(e.currentTarget).find('i');
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        // Shop selection
        $('#shop-id').on('change', () => {
            this.loadShopDetails();
        });
    },

    initSyncSettings() {
        // Redis cache integration
        if (wpwpsData.hasRedisCache) {
            this.initRedisCacheSettings();
        }

        // WP Smush integration
        if (wpwpsData.hasSmush) {
            this.initSmushIntegration();
        }
    },

    initCacheSettings() {
        $('#clear-cache').on('click', () => {
            this.clearCache();
        });

        $('#optimize-tables').on('click', () => {
            this.optimizeTables();
        });
    },

    initImageSettings() {
        // WP Smush integration settings
        $('#enable-smush').on('change', (e) => {
            $('.smush-settings').toggleClass('d-none', !e.target.checked);
        });

        // Image optimization settings
        $('#image-quality').on('input', (e) => {
            $('#quality-value').text(e.target.value);
        });
    },

    async verifyApiKey() {
        const btn = $('#verify-api-key');
        const originalText = btn.html();
        
        try {
            btn.html('<i class="fas fa-spinner fa-spin"></i> Verifying...').prop('disabled', true);
            
            const response = await $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'wpwps_verify_api_key',
                    api_key: $('#api-key').val(),
                    nonce: wpwpsData.nonce
                }
            });

            if (response.success) {
                this.showToast('success', 'API key verified successfully');
                this.updateShopsList(response.shops);
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            this.showToast('error', error.message || 'Failed to verify API key');
        } finally {
            btn.html(originalText).prop('disabled', false);
        }
    },

    async loadShopDetails() {
        const shopId = $('#shop-id').val();
        if (!shopId) return;

        try {
            const response = await $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'wpwps_get_shop_details',
                    shop_id: shopId,
                    nonce: wpwpsData.nonce
                }
            });

            if (response.success) {
                this.updateShopStats(response.stats);
            }
        } catch (error) {
            this.showToast('error', 'Failed to load shop details');
        }
    },

    async clearCache() {
        try {
            const result = await Swal.fire({
                title: 'Clear Cache',
                text: 'Are you sure you want to clear all cached data?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, clear it'
            });

            if (result.isConfirmed) {
                const response = await $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'wpwps_clear_cache',
                        nonce: wpwpsData.nonce
                    }
                });

                if (response.success) {
                    this.showToast('success', 'Cache cleared successfully');
                    // Refresh cache stats
                    this.updateCacheStats();
                }
            }
        } catch (error) {
            this.showToast('error', 'Failed to clear cache');
        }
    },

    showToast(type, message) {
        Swal.fire({
            text: message,
            icon: type,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    },

    updateShopStats(stats) {
        const statsContainer = $('#shop-stats');
        statsContainer.html(`
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="fas fa-box text-primary"></i>
                        <div class="stat-content">
                            <h4>${stats.total_products}</h4>
                            <span>Total Products</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="fas fa-sync text-success"></i>
                        <div class="stat-content">
                            <h4>${stats.synced_products}</h4>
                            <span>Synced Products</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="fas fa-exclamation-triangle text-warning"></i>
                        <div class="stat-content">
                            <h4>${stats.pending_sync}</h4>
                            <span>Pending Sync</span>
                        </div>
                    </div>
                </div>
            </div>
        `);
    },

    initSmushIntegration() {
        // Initialize WP Smush integration settings
        const smushSettings = {
            auto_optimize: true,
            keep_original: false,
            ...wpwpsData.smushSettings
        };

        Object.entries(smushSettings).forEach(([key, value]) => {
            $(`#smush-${key}`).prop('checked', value);
        });
    },

    initRedisCacheSettings() {
        // Initialize Redis cache settings
        const redisSettings = {
            enable_redis: true,
            ttl: 3600,
            ...wpwpsData.redisSettings
        };

        $('#enable-redis').prop('checked', redisSettings.enable_redis);
        $('#redis-ttl').val(redisSettings.ttl);
    }
};

$(document).ready(() => {
    WPWPS.Settings.init();
});
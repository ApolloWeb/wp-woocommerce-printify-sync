<footer class="wpwps-footer mt-auto py-3">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center border-top pt-3">
            <div>
                <small class="text-muted">&copy; {{ date('Y') }} WooCommerce Printify Sync v{{ WPWPS_VERSION }}</small>
            </div>
            <div class="social-links">
                <a href="#" class="text-muted me-3" title="Facebook">
                    <img src="{{ plugin_dir_url('') }}wp-woocommerce-printify-sync/assets/images/facebook.svg" 
                         alt="Facebook" 
                         width="20">
                </a>
                <a href="#" class="text-muted me-3" title="Instagram">
                    <img src="{{ plugin_dir_url('') }}wp-woocommerce-printify-sync/assets/images/instagram.svg" 
                         alt="Instagram" 
                         width="20">
                </a>
                <a href="#" class="text-muted me-3" title="TikTok">
                    <img src="{{ plugin_dir_url('') }}wp-woocommerce-printify-sync/assets/images/tiktok.svg" 
                         alt="TikTok" 
                         width="20">
                </a>
                <a href="#" class="text-muted" title="YouTube">
                    <img src="{{ plugin_dir_url('') }}wp-woocommerce-printify-sync/assets/images/youtube.svg" 
                         alt="YouTube" 
                         width="20">
                </a>
            </div>
        </div>
    </div>
</footer>

<style>
.wpwps-footer {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.wpwps-footer .social-links img {
    transition: opacity 0.3s ease;
}

.wpwps-footer .social-links a:hover img {
    opacity: 0.7;
}
</style>
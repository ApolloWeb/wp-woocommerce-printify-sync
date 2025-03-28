<footer class="wpwps-footer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="mb-3">
                    <a href="https://github.com/ApolloWeb/wp-woocommerce-printify-sync" target="_blank" class="text-decoration-none text-muted mx-2 wpwps-hover-scale">
                        <i class="fab fa-github fa-lg"></i>
                    </a>
                    <a href="https://developers.printify.com/" target="_blank" class="text-decoration-none text-muted mx-2 wpwps-hover-scale">
                        <i class="fas fa-book fa-lg"></i>
                    </a>
                    <a href="https://woocommerce.com/documentation/woocommerce/" target="_blank" class="text-decoration-none text-muted mx-2 wpwps-hover-scale">
                        <i class="fas fa-shopping-cart fa-lg"></i>
                    </a>
                </div>
                <p class="mb-0 small">
                    &copy; {{ date('Y') }} ApolloWeb &middot; {{ __('Version', 'wp-woocommerce-printify-sync') }} {{ WPWPS_VERSION }}
                </p>
                <p class="text-muted small mt-1">
                    {{ __('Built with', 'wp-woocommerce-printify-sync') }} 
                    <i class="fas fa-heart text-danger"></i> 
                    {{ __('for WooCommerce', 'wp-woocommerce-printify-sync') }}
                </p>
            </div>
        </div>
    </div>
</footer>
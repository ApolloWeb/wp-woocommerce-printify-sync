<div class="d-flex justify-content-between align-items-center mt-4">
    <div class="text-muted">
        {{ __('Showing', 'wp-woocommerce-printify-sync') }} 
        <span id="showing-start">1</span>-<span id="showing-end">10</span> 
        {{ __('of', 'wp-woocommerce-printify-sync') }} 
        <span id="total-items">{{ count($products) }}</span> 
        {{ __('items', 'wp-woocommerce-printify-sync') }}
    </div>
    
    <div class="d-flex align-items-center gap-2">
        <select class="form-select form-select-sm" id="per-page" style="width: auto;">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>

        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item disabled" id="prev-page">
                    <a class="page-link" href="#" tabindex="-1">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item" id="next-page">
                    <a class="page-link" href="#">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</div>
<?php defined('ABSPATH') || exit; ?>

<div id="apiLogs">
    <div class="card mt-4">
        <div class="card-header">
            <h4 class="mb-0">
                <i class="fas fa-history me-2"></i>
                <?php echo esc_html__('API Logs', 'wp-woocommerce-printify-sync'); ?>
            </h4>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <select class="form-select" id="logStatus">
                        <option value=""><?php echo esc_html__('All Statuses', 'wp-woocommerce-printify-sync'); ?></option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="logEndpoint">
                        <option value=""><?php echo esc_html__('All Endpoints', 'wp-woocommerce-printify-sync'); ?></option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" id="logDateFrom" placeholder="<?php echo esc_attr__('From Date', 'wp-woocommerce-printify-sync'); ?>">
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" id="logDateTo" placeholder="<?php echo esc_attr__('To Date', 'wp-woocommerce-printify-sync'); ?>">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Date', 'wp-woocommerce-printify-sync'); ?></th>
                            <th><?php echo esc_html__('Endpoint', 'wp-woocommerce-printify-sync'); ?></th>
                            <th><?php echo esc_html__('Status', 'wp-woocommerce-printify-sync'); ?></th>
                            <th><?php echo esc_html__('Code', 'wp-woocommerce-printify-sync'); ?></th>
                            <th><?php echo esc_html__('Message', 'wp-woocommerce-printify-sync'); ?></th>
                            <th><?php echo esc_html__('Details', 'wp-woocommerce-printify-sync'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="logsTableBody">
                        <tr>
                            <td colspan="6" class="text-center">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    <select class="form-select form-select-sm" id="logsPerPage">
                        <option value="10">10</option>
                        <option value="20" selected>20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <nav>
                    <ul class="pagination mb-0" id="logsPagination"></ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Log Details Modal -->
    <div class="modal fade" id="logDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo esc_html__('Log Details', 'wp-woocommerce-printify-sync'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <pre class="bg-light p-3 rounded" id="logDetailsContent"></pre>
                </div>
            </div>
        </div>
    </div>
</div>
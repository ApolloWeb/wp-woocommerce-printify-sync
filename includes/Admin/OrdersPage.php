<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

/**
 * Orders Management Page
 */
class OrdersPage extends AdminPage {
    /**
     * Constructor
     */
    public function __construct() {
        $this->page_title = __('Printify Orders', 'wp-woocommerce-printify-sync');
        $this->menu_title = __('Orders', 'wp-woocommerce-printify-sync');
        $this->menu_slug = 'wpwps-orders';
        $this->parent_slug = 'wpwps-dashboard';
        $this->capability = 'manage_options';
    }
    
    /**
     * Render the page content
     */
    protected function render_content() {
        ?>
        <div class="container-fluid">
            <!-- Action Bar Row -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <div class="d-flex align-items-center mb-2 mb-md-0">
                                    <button class="btn btn-primary me-2" id="sync-orders">
                                        <i class="fas fa-sync-alt me-2"></i> Sync Orders
                                    </button>
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Status Filter
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#">All Orders</a></li>
                                        <li><a class="dropdown-item" href="#">Processing</a></li>
                                        <li><a class="dropdown-item" href="#">Fulfilled</a></li>
                                        <li><a class="dropdown-item" href="#">Cancelled</a></li>
                                    </ul>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="Search orders" id="order-search">
                                        <button class="btn btn-outline-secondary" type="button">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                    <div class="d-flex align-items-center ms-2">
                                        <div class="input-group">
                                            <input type="date" class="form-control" placeholder="Date from">
                                            <input type="date" class="form-control" placeholder="Date to">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Row -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm wpwps-stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Total Orders</h6>
                                    <h2 class="mb-0 fw-bold">124</h2>
                                </div>
                                <div class="bg-primary bg-opacity-10 p-3 rounded">
                                    <i class="fas fa-shopping-cart fa-2x text-primary"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="text-success">
                                    <i class="fas fa-arrow-up"></i> 12.5%
                                </span>
                                <span class="text-muted ms-2">From previous period</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm wpwps-stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Pending Orders</h6>
                                    <h2 class="mb-0 fw-bold">18</h2>
                                </div>
                                <div class="bg-warning bg-opacity-10 p-3 rounded">
                                    <i class="fas fa-clock fa-2x text-warning"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="text-danger">
                                    <i class="fas fa-arrow-up"></i> 5.2%
                                </span>
                                <span class="text-muted ms-2">From previous period</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm wpwps-stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Fulfilled Orders</h6>
                                    <h2 class="mb-0 fw-bold">95</h2>
                                </div>
                                <div class="bg-success bg-opacity-10 p-3 rounded">
                                    <i class="fas fa-check-circle fa-2x text-success"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="text-success">
                                    <i class="fas fa-arrow-up"></i> 18.3%
                                </span>
                                <span class="text-muted ms-2">From previous period</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm wpwps-stat-card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Cancelled Orders</h6>
                                    <h2 class="mb-0 fw-bold">11</h2>
                                </div>
                                <div class="bg-danger bg-opacity-10 p-3 rounded">
                                    <i class="fas fa-times-circle fa-2x text-danger"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="text-success">
                                    <i class="fas fa-arrow-down"></i> 4.1%
                                </span>
                                <span class="text-muted ms-2">From previous period</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Orders Table Row -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Orders</h5>
                            <span class="badge bg-primary">24 New Today</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover orders-table">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Customer</th>
                                            <th>Products</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php for ($i = 1; $i <= 10; $i++): 
                                            $status = $i % 4;
                                            switch ($status) {
                                                case 0:
                                                    $statusClass = 'success';
                                                    $statusText = 'Fulfilled';
                                                    break;
                                                case 1:
                                                    $statusClass = 'warning';
                                                    $statusText = 'Processing';
                                                    break;
                                                case 2:
                                                    $statusClass = 'primary';
                                                    $statusText = 'Pending';
                                                    break;
                                                case 3:
                                                    $statusClass = 'danger';
                                                    $statusText = 'Cancelled';
                                                    break;
                                            }
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="fw-bold">#PRN-<?php echo 10000 + $i; ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px">
                                                            <i class="fas fa-user text-secondary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="ms-3">
                                                        <p class="fw-bold mb-0">Customer <?php echo $i; ?></p>
                                                        <small class="text-muted">customer<?php echo $i; ?>@example.com</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo rand(1, 3); ?> items</td>
                                            <td>$<?php echo number_format(rand(2000, 10000) / 100, 2); ?></td>
                                            <td><span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                            <td><?php echo date('M d, Y', strtotime("-$i days")); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                    <a href="#" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-print"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endfor; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <nav aria-label="Orders pagination">
                                <ul class="pagination justify-content-center mb-0">
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                                    </li>
                                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                                    <li class="page-item">
                                        <a class="page-link" href="#">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Handle sync orders button
                $('#sync-orders').on('click', function() {
                    const $button = $(this);
                    
                    $button.prop('disabled', true).html(
                        '<i class="fas fa-spinner fa-spin me-2"></i> Syncing...'
                    );
                    
                    setTimeout(function() {
                        $button.prop('disabled', false).html(
                            '<i class="fas fa-sync-alt me-2"></i> Sync Orders'
                        );
                        
                        wpwpsToastManager.showToast(
                            'success',
                            'Success',
                            'Orders have been synced successfully.',
                            5000
                        );
                    }, 2000);
                });
            });
        </script>
        <?php
    }
}

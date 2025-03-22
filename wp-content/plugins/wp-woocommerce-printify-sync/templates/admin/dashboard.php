<?php $this->layout('admin/layout', ['title' => __('Dashboard', 'wp-woocommerce-printify-sync')]) ?>

<div class="row g-4">
    <!-- Stats Cards -->
    <div class="col-md-3">
        <div class="wpps-card p-4">
            <h3 class="h6 text-muted mb-2"><?= __('Total Products', 'wp-woocommerce-printify-sync') ?></h3>
            <h4 class="h2 mb-0">247</h4>
            <small class="text-success">+12% <i class="fas fa-arrow-up"></i></small>
        </div>
    </div>
    
    <!-- Orders Chart -->
    <div class="col-md-8">
        <div class="wpps-chart-container">
            <canvas id="ordersChart"></canvas>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="col-md-4">
        <?= $this->insert('admin/partials/recent-activity') ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('ordersChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: '<?= __('Orders', 'wp-woocommerce-printify-sync') ?>',
                data: [12, 19, 3, 5, 2, 3],
                borderColor: '#96588a',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: '<?= __('Orders Overview', 'wp-woocommerce-printify-sync') ?>'
                }
            }
        }
    });
});
</script>

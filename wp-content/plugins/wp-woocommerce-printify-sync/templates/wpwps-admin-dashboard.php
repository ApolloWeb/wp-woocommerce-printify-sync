<div class="wrap">
    <?php $this->include('notices'); ?>
    <?php $this->include('wpwpps-navbar'); ?>

    <div class="container-fluid py-4">
        <div class="row">
            <?php $this->include('dashboard/stats-widget', ['stats' => $stats]); ?>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <?php $this->include('dashboard/chart-widget', ['chart_data' => $chart_data]); ?>
            </div>
        </div>
    </div>
</div>

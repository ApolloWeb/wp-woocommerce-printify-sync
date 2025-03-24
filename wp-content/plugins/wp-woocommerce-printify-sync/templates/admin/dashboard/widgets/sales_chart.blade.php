<div class="wpwps-widget wpwps-sales-chart card">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Sales Overview</h6>
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary active" data-range="30">30 Days</button>
                <button type="button" class="btn btn-outline-secondary" data-range="90">90 Days</button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <canvas id="wpwps-sales-chart" height="300"></canvas>
    </div>
</div>

<script>
const chart = new Chart(document.getElementById('wpwps-sales-chart'), {
    type: 'line',
    data: {!! json_encode($datasets) !!},
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    borderDash: [2, 2]
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});
</script>

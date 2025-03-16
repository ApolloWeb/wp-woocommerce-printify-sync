document.addEventListener('DOMContentLoaded', function() {
    // Generate dummy data
    const generateData = (days) => {
        const data = [];
        const labels = [];
        const now = new Date();
        
        for (let i = days; i > 0; i--) {
            const date = new Date(now);
            date.setDate(date.getDate() - i);
            labels.push(date.toLocaleDateString());
            data.push(Math.floor(Math.random() * 1000) + 100);
        }
        
        return { labels, data };
    };

    // Initialize chart
    const ctx = document.getElementById('salesChart').getContext('2d');
    const { labels, data } = generateData(30); // Default to monthly view

    const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sales',
                data: data,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
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

    // Period selector handling
    document.querySelectorAll('[data-period]').forEach(button => {
        button.addEventListener('click', function() {
            const period = this.dataset.period;
            const days = {
                day: 1,
                week: 7,
                month: 30,
                year: 365
            }[period];

            const { labels, data } = generateData(days);
            salesChart.data.labels = labels;
            salesChart.data.datasets[0].data = data;
            salesChart.update();

            // Update active button
            document.querySelectorAll('[data-period]').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
        });
    });
});
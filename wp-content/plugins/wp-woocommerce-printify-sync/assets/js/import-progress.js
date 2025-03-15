document.addEventListener('DOMContentLoaded', function() {
    let progressChart = null;

    function initializeProgressVisualization(batchId) {
        const progressContainer = document.getElementById('import-progress');
        progressContainer.innerHTML = `
            <div class="progress-summary">
                <div class="progress-chart">
                    <canvas id="progressChart"></canvas>
                </div>
                <div class="progress-stats">
                    <div class="stat-box">
                        <span class="stat-label">Total Products</span>
                        <span class="stat-value" id="totalProducts">0</span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">Completed</span>
                        <span class="stat-value" id="completedChunks">0</span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">Failed</span>
                        <span class="stat-value" id="failedChunks">0</span>
                    </div>
                </div>
            </div>
            <div class="chunks-grid" id="chunksGrid"></div>
            <div class="retry-section" id="retrySection" style="display: none;">
                <button id="retryFailed" class="button button-primary">Retry Failed Chunks</button>
            </div>
        `;

        // Initialize Chart.js
        const ctx = document.getElementById('progressChart').getContext('2d');
        progressChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Failed', 'Pending'],
                datasets: [{
                    data: [0, 0, 100],
                    backgroundColor: ['#28a745', '#dc3545', '#f8f9fa']
                }]
            },
            options: {
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Start progress monitoring
        monitorProgress(batchId);
    }

    function updateVisualization(progress) {
        // Update chart
        progressChart.data.datasets[0].data = [
            progress.stats.completed_chunks,
            progress.stats.failed_chunks,
            progress.stats.total_chunks - (progress.stats.completed_chunks + progress.stats.failed_chunks)
        ];
        progressChart.update();

        // Update stats
        document.getElementById('totalProducts').textContent = progress.stats.total_chunks * 10;
        document.getElementById('completedChunks').textContent = progress.stats.completed_chunks * 10;
        document.getElementById('failedChunks').textContent = progress.stats.failed_chunks * 10;

        // Update chunks grid
        updateChunksGrid(progress.chunks || []);

        // Show/hide retry section
        const retrySection = document.getElementById('retrySection');
        retrySection.style.display = progress.stats.failed_chunks > 0 ? 'block' : 'none';
    }

    function updateChunksGrid(chunks) {
        const grid = document.getElementById('chunksGrid');
        grid.innerHTML = chunks.map(chunk => `
            <div class="chunk-box ${chunk.status}" data-chunk="${chunk.chunk_index}">
                <div class="chunk-header">Chunk ${chunk.chunk_index + 1}</div>
                <div class="chunk-status">${chunk.status}</div>
                ${chunk.error ? `<div class="chunk-error">${chunk.error}</div>` : ''}
            </div>
        `).join('');
    }

    // Export for global use
    window.wpwpsInitProgress = initializeProgressVisualization;
});
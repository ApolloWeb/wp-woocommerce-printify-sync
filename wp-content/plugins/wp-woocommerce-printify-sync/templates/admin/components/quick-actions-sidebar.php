<div class="printify-quick-actions-sidebar" id="quickActionsSidebar">
    <div class="sidebar-header">
        <h3>Quick Actions</h3>
        <button class="close-sidebar" id="closeSidebar">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="sidebar-content">
        <!-- Recent Activity -->
        <div class="sidebar-section">
            <h4>Recent Activity</h4>
            <div class="activity-list" id="recentActivity">
                <!-- Dynamically populated -->
            </div>
        </div>

        <!-- Quick Tasks -->
        <div class="sidebar-section">
            <h4>Quick Tasks</h4>
            <div class="quick-tasks">
                <button class="task-button" data-action="sync-products">
                    <i class="fas fa-sync"></i>
                    <span>Sync Products</span>
                </button>
                <button class="task-button" data-action="check-stock">
                    <i class="fas fa-boxes"></i>
                    <span>Check Stock</span>
                </button>
                <button class="task-button" data-action="view-logs">
                    <i class="fas fa-list"></i>
                    <span>View Logs</span>
                </button>
                <button class="task-button" data-action="clear-cache">
                    <i class="fas fa-broom"></i>
                    <span>Clear Cache</span>
                </button>
            </div>
        </div>

        <!-- System Status -->
        <div class="sidebar-section">
            <h4>System Status</h4>
            <div class="status-indicators">
                <div class="status-item">
                    <span class="status-label">API Connection</span>
                    <span class="status-value" id="apiStatus">
                        <i class="fas fa-circle text-success"></i> Connected
                    </span>
                </div>
                <div class="status-item">
                    <span class="status-label">Last Sync</span>
                    <span class="status-value" id="lastSync">5 min ago</span>
                </div>
                <div class="status-item">
                    <span class="status-label">Queue Status</span>
                    <span class="status-value" id="queueStatus">
                        <i class="fas fa-circle text-success"></i> Processing
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
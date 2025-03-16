// Add to your existing Plugin class

public function initializeScheduler(): void
{
    $scheduler = new StockSyncScheduler();
    $scheduler->register();
}

public function deactivate(): void
{
    // Clear the scheduled event
    wp_clear_scheduled_hook('printify_stock_sync_event');
}
<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\Import;

class ProductImportScheduler
{
    // ... existing code ...

    private function handleImportError(
        string $productId,
        \Exception $error,
        array $context
    ): void {
        $batchId = $context['batch_id'];
        $failedProducts = get_option("wpwps_import_{$batchId}_failed", []);
        
        $failedProducts[$productId] = [
            'error' => $error->getMessage(),
            'time' => $this->getCurrentTime(),
            'context' => $context
        ];

        update_option("wpwps_import_{$batchId}_failed", $failedProducts);

        // Record in monitoring
        $this->monitor->updateProgress($batchId, [
            'failed' => count($failedProducts),
            'last_error' => $error->getMessage()
        ]);

        // If critical error, pause the import
        if ($this->isCriticalError($error)) {
            $this->pauseImport($batchId);
            
            // Notify admin
            $this->notifyAdmin(
                'Critical import error',
                $this->formatErrorNotification($error, $context)
            );
        }
    }

    private function isCriticalError(\Exception $error): bool
    {
        return (
            $error instanceof ApiRateLimitException ||
            $error instanceof ApiAuthenticationException ||
            $error instanceof DatabaseException
        );
    }

    private function pauseImport(string $batchId): void
    {
        // Get all scheduled actions for this batch
        $actions = as_get_scheduled_actions([
            'hook' => [
                self::HOOK_IMPORT_PRODUCTS,
                self::HOOK_IMPORT_IMAGES
            ],
            'args' => ['batch_id' => $batchId],
            'status' => ActionScheduler_Store::STATUS_PENDING
        ]);

        // Pause them
        foreach ($actions as $action) {
            as_unschedule_action(
                $action->get_hook(),
                $action->get_args(),
                $action->get_group()
            );
        }

        $this->logger->warning('Import paused due to critical error', [
            'batch_id' => $batchId,
            'timestamp' => $this->getCurrentTime()
        ]);
    }

    public function resumeImport(string $batchId): void
    {
        $status = $this->monitor->getImportStatus($batchId);
        
        if ($status['status'] !== 'paused') {
            throw new \Exception('Import is not paused');
        }

        // Get failed products
        $failedProducts = get_option("wpwps_import_{$batchId}_failed", []);

        // Reschedule failed products
        $this->scheduleImport(
            array_keys($failedProducts),
            'resume_' . $status['source'],
            ['original_batch_id' => $batchId]
        );

        $this->logger->info('Import resumed', [
            'batch_id' => $batchId,
            'failed_products' => count($failedProducts),
            'timestamp' => $this->getCurrentTime()
        ]);
    }

    private function notifyAdmin(string $subject, string $message): void
    {
        $adminEmail = get_option('admin_email');
        $blogName = get_bloginfo('name');

        wp_mail(
            $adminEmail,
            sprintf('[%s] %s', $blogName, $subject),
            $message
        );
    }

    private function formatErrorNotification(
        \Exception $error,
        array $context
    ): string {
        return sprintf(
            "A critical error occurred during product import:\n\n" .
            "Error: %s\n" .
            "Batch ID: %s\n" .
            "Source: %s\n" .
            "Products Affected: %d\n" .
            "Time: %s\n\n" .
            "The import has been paused. Please check the logs and resolve the issue before resuming.",
            $error->getMessage(),
            $context['batch_id'],
            $context['source'],
            count($context['product_ids']),
            $this->getCurrentTime()
        );
    }
}
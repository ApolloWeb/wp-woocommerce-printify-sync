<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\API;

use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class RetryManager
{
    use TimeStampTrait;

    private const MAX_RETRIES = 5;
    private const JITTER_MAX = 1000; // Maximum jitter in milliseconds

    private LoggerInterface $logger;
    private ConfigService $config;

    private array $retryableOperations = [];
    private array $failedOperations = [];

    public function __construct(LoggerInterface $logger, ConfigService $config)
    {
        $this->logger = $logger;
        $this->config = $config;
    }

    public function registerRetryableOperation(
        string $operationId,
        callable $operation,
        array $context = []
    ): void {
        $this->retryableOperations[$operationId] = [
            'operation' => $operation,
            'context' => $context,
            'retries' => 0,
            'last_attempt' => null,
            'next_retry' => null
        ];
    }

    public function executeWithRetry(string $operationId): mixed
    {
        if (!isset($this->retryableOperations[$operationId])) {
            throw new \InvalidArgumentException("Unknown operation: {$operationId}");
        }

        $operation = $this->retryableOperations[$operationId];
        
        try {
            $result = $operation['operation']();
            
            // Clear from failed operations if it was there
            unset($this->failedOperations[$operationId]);
            
            return $result;

        } catch (\Exception $e) {
            return $this->handleOperationFailure($operationId, $e);
        }
    }

    private function handleOperationFailure(string $operationId, \Exception $e): mixed
    {
        $operation = &$this->retryableOperations[$operationId];
        $operation['retries']++;
        $operation['last_attempt'] = $this->getCurrentTime();

        if ($operation['retries'] >= self::MAX_RETRIES) {
            $this->handleMaxRetriesExceeded($operationId, $e);
            throw $e;
        }

        // Calculate next retry time with exponential backoff and jitter
        $backoff = $this->calculateBackoffWithJitter($operation['retries']);
        $operation['next_retry'] = time() + $backoff;

        // Log the failure and schedule retry
        $this->logRetry($operationId, $operation, $e);

        // Add to failed operations queue
        $this->failedOperations[$operationId] = $operation;

        // Attempt immediate retry if appropriate
        if ($this->shouldRetryImmediately($e)) {
            return $this->executeWithRetry($operationId);
        }

        throw new RetryScheduledException(
            "Operation scheduled for retry",
            $operation['next_retry']
        );
    }

    private function calculateBackoffWithJitter(int $retryCount): int
    {
        // Base delay with exponential backoff
        $baseDelay = min(pow(2, $retryCount), 32);
        
        // Add random jitter
        $jitter = rand(0, self::JITTER_MAX) / 1000; // Convert to seconds
        
        return $baseDelay + $jitter;
    }

    private function shouldRetryImmediately(\Exception $e): bool
    {
        // Retry immediately for certain error types
        return $e instanceof TemporaryNetworkException
            || $e instanceof RateLimitException
            || ($e instanceof ApiException && $e->getCode() >= 500);
    }

    public function processFailedOperations(): void
    {
        $currentTime = time();

        foreach ($this->failedOperations as $operationId => $operation) {
            if ($operation['next_retry'] <= $currentTime) {
                try {
                    $this->executeWithRetry($operationId);
                } catch (RetryScheduledException $e) {
                    // Operation rescheduled, continue with next
                    continue;
                } catch (\Exception $e) {
                    // Log but continue processing other operations
                    $this->logger->error('Retry failed', [
                        'operation_id' => $operationId,
                        'error' => $e->getMessage(),
                        'timestamp' => $this->getCurrentTime()
                    ]);
                }
            }
        }
    }

    private function handleMaxRetriesExceeded(string $operationId, \Exception $e): void
    {
        $operation = $this->retryableOperations[$operationId];
        
        $this->logger->error('Max retries exceeded', [
            'operation_id' => $operationId,
            'context' => $operation['context'],
            'total_retries' => $operation['retries'],
            'final_error' => $e->getMessage(),
            'timestamp' => $this->getCurrentTime()
        ]);

        // Cleanup
        unset($this->retryableOperations[$operationId]);
        unset($this->failedOperations[$operationId]);

        // Notify admin if configured
        if ($this->config->get('notify_on_max_retries', true)) {
            $this->notifyAdminOfFailure($operationId, $operation, $e);
        }
    }

    private function logRetry(string $operationId, array $operation, \Exception $e): void
    {
        $this->logger->warning('Operation failed, scheduling retry', [
            'operation_id' => $operationId,
            'retry_count' => $operation['retries'],
            'next_retry' => date('Y-m-d H:i:s', $operation['next_retry']),
            'error' => $e->getMessage(),
            'context' => $operation['context'],
            'timestamp' => $this->getCurrentTime()
        ]);
    }

    private function notifyAdminOfFailure(
        string $operationId,
        array $operation,
        \Exception $e
    ): void {
        $adminEmail = get_option('admin_email');
        $subject = sprintf(
            '[%s] Operation failed after maximum retries',
            get_bloginfo('name')
        );

        $message = sprintf(
            "Operation '%s' has failed after %d retries.\n\n" .
            "Last error: %s\n\n" .
            "Context: %s\n\n" .
            "Timestamp: %s",
            $operationId,
            $operation['retries'],
            $e->getMessage(),
            json_encode($operation['context'], JSON_PRETTY_PRINT),
            $this->getCurrentTime()
        );

        wp_mail($adminEmail, $subject, $message);
    }
}
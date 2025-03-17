<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\API;

use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class APIManager
{
    use TimeStampTrait;

    private EnhancedRateLimiter $rateLimiter;
    private CircuitBreaker $circuitBreaker;
    private RetryManager $retryManager;
    private LoggerInterface $logger;

    public function __construct(
        EnhancedRateLimiter $rateLimiter,
        CircuitBreaker $circuitBreaker,
        RetryManager $retryManager,
        LoggerInterface $logger
    ) {
        $this->rateLimiter = $rateLimiter;
        $this->circuitBreaker = $circuitBreaker;
        $this->retryManager = $retryManager;
        $this->logger = $logger;
    }

    public function executeRequest(
        string $endpoint,
        callable $operation,
        array $options = []
    ): mixed {
        $operationId = md5($endpoint . serialize($options));

        // Register the operation with the retry manager
        $this->retryManager->registerRetryableOperation($operationId, function() use ($endpoint, $operation) {
            return $this->circuitBreaker->executeOperation($endpoint, function() use ($endpoint, $operation) {
                return $this->rateLimiter->executeWithRetry($operation, $endpoint);
            });
        }, [
            'endpoint' => $endpoint,
            'options' => $options,
            'timestamp' => $this->getCurrentTime()
        ]);

        try {
            return $this->retryManager->executeWithRetry($operationId);
        } catch (\Exception $e) {
            $this->logger->error('Request failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'timestamp' => $this->getCurrentTime()
            ]);
            throw $e;
        }
    }

    public function getEndpointStatus(string $endpoint): array
    {
        return [
            'circuit_status' => $this->circuitBreaker->getCircuitStatus($endpoint),
            'rate_limit_status' => $this->rateLimiter->getRateLimitStatus($endpoint),
            'retry_status' => $this->retryManager->getRetryStatus($endpoint),
            'timestamp' => $this->getCurrentTime()
        ];
    }
}
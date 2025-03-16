<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\API;

use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class CircuitBreaker
{
    use TimeStampTrait;

    private const STATE_CLOSED = 'closed';      // Normal operation
    private const STATE_OPEN = 'open';          // No operations allowed
    private const STATE_HALF_OPEN = 'half_open';// Testing if service is back

    private const CACHE_GROUP = 'printify_circuit_breaker';
    private const DEFAULT_THRESHOLD = 5;        // Failures before opening
    private const DEFAULT_TIMEOUT = 60;         // Seconds to wait before half-open
    private const DEFAULT_WINDOW = 120;         // Rolling window in seconds

    private CacheInterface $cache;
    private LoggerInterface $logger;
    private ConfigService $config;

    private array $endpoints = [];
    private array $failureCounters = [];

    public function __construct(
        CacheInterface $cache,
        LoggerInterface $logger,
        ConfigService $config
    ) {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->config = $config;
    }

    public function registerEndpoint(
        string $endpoint,
        ?int $threshold = null,
        ?int $timeout = null
    ): void {
        $this->endpoints[$endpoint] = [
            'threshold' => $threshold ?? self::DEFAULT_THRESHOLD,
            'timeout' => $timeout ?? self::DEFAULT_TIMEOUT
        ];
    }

    public function executeOperation(string $endpoint, callable $operation): mixed
    {
        $this->ensureEndpointRegistered($endpoint);
        $state = $this->getState($endpoint);

        switch ($state) {
            case self::STATE_OPEN:
                if ($this->shouldAttemptReset($endpoint)) {
                    $this->setState($endpoint, self::STATE_HALF_OPEN);
                    return $this->executeWithMonitoring($endpoint, $operation);
                }
                throw new CircuitBreakerException("Circuit is open for {$endpoint}");

            case self::STATE_HALF_OPEN:
                return $this->executeWithMonitoring($endpoint, $operation);

            case self::STATE_CLOSED:
                return $this->executeWithMonitoring($endpoint, $operation);

            default:
                throw new \RuntimeException("Invalid circuit state: {$state}");
        }
    }

    private function executeWithMonitoring(string $endpoint, callable $operation): mixed
    {
        try {
            $result = $operation();
            $this->handleSuccess($endpoint);
            return $result;

        } catch (\Exception $e) {
            $this->handleFailure($endpoint, $e);
            throw $e;
        }
    }

    private function handleSuccess(string $endpoint): void
    {
        $state = $this->getState($endpoint);

        if ($state === self::STATE_HALF_OPEN) {
            $this->setState($endpoint, self::STATE_CLOSED);
            $this->resetFailureCount($endpoint);
            
            $this->logger->info('Circuit closed after successful test', [
                'endpoint' => $endpoint,
                'timestamp' => $this->getCurrentTime()
            ]);
        }
    }

    private function handleFailure(string $endpoint, \Exception $e): void
    {
        $state = $this->getState($endpoint);
        $failureCount = $this->incrementFailureCount($endpoint);
        $threshold = $this->endpoints[$endpoint]['threshold'];

        if ($state === self::STATE_HALF_OPEN || $failureCount >= $threshold) {
            $this->setState($endpoint, self::STATE_OPEN);
            $this->setLastFailureTime($endpoint);

            $this->logger->warning('Circuit opened due to failures', [
                'endpoint' => $endpoint,
                'failure_count' => $failureCount,
                'last_error' => $e->getMessage(),
                'timestamp' => $this->getCurrentTime()
            ]);

            // Notify about service degradation
            $this->notifyServiceDegradation($endpoint, $failureCount, $e);
        }
    }

    private function shouldAttemptReset(string $endpoint): bool
    {
        $lastFailure = $this->getLastFailureTime($endpoint);
        $timeout = $this->endpoints[$endpoint]['timeout'];

        return (time() - $lastFailure) >= $timeout;
    }

    private function getState(string $endpoint): string
    {
        return $this->cache->get(
            "circuit_state:{$endpoint}",
            self::CACHE_GROUP
        ) ?: self::STATE_CLOSED;
    }

    private function setState(string $endpoint, string $state): void
    {
        $this->cache->set(
            "circuit_state:{$endpoint}",
            $state,
            DAY_IN_SECONDS,
            self::CACHE_GROUP
        );
    }

    private function incrementFailureCount(string $endpoint): int
    {
        $key = "failures:{$endpoint}:" . floor(time() / self::DEFAULT_WINDOW);
        $count = ($this->failureCounters[$endpoint] ?? 0) + 1;
        $this->failureCounters[$endpoint] = $count;

        $this->cache->set(
            $key,
            $count,
            self::DEFAULT_WINDOW,
            self::CACHE_GROUP
        );

        return $count;
    }

    private function resetFailureCount(string $endpoint): void
    {
        $key = "failures:{$endpoint}:" . floor(time() / self::DEFAULT_WINDOW);
        $this->failureCounters[$endpoint] = 0;
        $this->cache->delete($key, self::CACHE_GROUP);
    }

    private function getLastFailureTime(string $endpoint): int
    {
        return (int) $this->cache->get(
            "last_failure:{$endpoint}",
            self::CACHE_GROUP
        ) ?: 0;
    }

    private function setLastFailureTime(string $endpoint): void
    {
        $this->cache->set(
            "last_failure:{$endpoint}",
            time(),
            DAY_IN_SECONDS,
            self::CACHE_GROUP
        );
    }

    private function ensureEndpointRegistered(string $endpoint): void
    {
        if (!isset($this->endpoints[$endpoint])) {
            $this->registerEndpoint($endpoint);
        }
    }

    private function notifyServiceDegradation(
        string $endpoint,
        int $failureCount,
        \Exception $e
    ): void {
        // Log detailed information
        $this->logger->error('Service degradation detected', [
            'endpoint' => $endpoint,
            'failure_count' => $failureCount,
            'last_error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'timestamp' => $this->getCurrentTime()
        ]);

        // Send admin notification
        if ($this->config->get('notify_on_degradation', true)) {
            $adminEmail = get_option('admin_email');
            $subject = sprintf(
                '[%s] Service Degradation Detected',
                get_bloginfo('name')
            );

            $message = sprintf(
                "Service degradation detected for endpoint: %s\n\n" .
                "Failure Count: %d\n" .
                "Last Error: %s\n\n" .
                "Circuit breaker has been opened to protect the system.\n" .
                "The service will be tested again in %d seconds.\n\n" .
                "Timestamp: %s",
                $endpoint,
                $failureCount,
                $e->getMessage(),
                $this->endpoints[$endpoint]['timeout'],
                $this->getCurrentTime()
            );

            wp_mail($adminEmail, $subject, $message);
        }

        // Trigger action for additional handling
        do_action('printify_service_degradation', $endpoint, $failureCount, $e);
    }

    public function getCircuitStatus(string $endpoint): array
    {
        return [
            'state' => $this->getState($endpoint),
            'failure_count' => $this->failureCounters[$endpoint] ?? 0,
            'last_failure' => $this->getLastFailureTime($endpoint),
            'threshold' => $this->endpoints[$endpoint]['threshold'] ?? self::DEFAULT_THRESHOLD,
            'timeout' => $this->endpoints[$endpoint]['timeout'] ?? self::DEFAULT_TIMEOUT
        ];
    }
}
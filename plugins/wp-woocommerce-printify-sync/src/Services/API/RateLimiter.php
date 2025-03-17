<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\API;

use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class RateLimiter
{
    use TimeStampTrait;

    private const CACHE_GROUP = 'printify_rate_limit';
    private const DEFAULT_LIMIT = 60; // requests per minute
    private const WINDOW_SIZE = 60; // 1 minute

    private $cache;
    private $logger;

    public function __construct(CacheInterface $cache, LoggerInterface $logger)
    {
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function checkLimit(string $endpoint): bool
    {
        $key = $this->getCacheKey($endpoint);
        $current = $this->getCurrentRequests($key);

        if ($current >= self::DEFAULT_LIMIT) {
            $this->logger->warning('Rate limit exceeded', [
                'endpoint' => $endpoint,
                'limit' => self::DEFAULT_LIMIT,
                'window' => self::WINDOW_SIZE,
                'timestamp' => $this->getCurrentTime()
            ]);
            return false;
        }

        $this->incrementRequests($key);
        return true;
    }

    private function getCacheKey(string $endpoint): string
    {
        return sprintf(
            'rate_limit:%s:%s',
            md5($endpoint),
            floor(time() / self::WINDOW_SIZE)
        );
    }

    private function getCurrentRequests(string $key): int
    {
        return (int) $this->cache->get($key, self::CACHE_GROUP) ?: 0;
    }

    private function incrementRequests(string $key): void
    {
        $current = $this->getCurrentRequests($key);
        $this->cache->set(
            $key,
            $current + 1,
            self::WINDOW_SIZE,
            self::CACHE_GROUP
        );
    }

    public function waitIfNeeded(string $endpoint): void
    {
        if (!$this->checkLimit($endpoint)) {
            $this->logger->info('Rate limit reached, waiting...', [
                'endpoint' => $endpoint,
                'timestamp' => $this->getCurrentTime()
            ]);
            sleep(1); // Wait 1 second before retrying
        }
    }
}
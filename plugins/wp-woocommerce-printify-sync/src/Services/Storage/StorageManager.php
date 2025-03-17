<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\Storage;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\StorageInterface;
use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class StorageManager
{
    use TimeStampTrait;

    private StorageFactory $factory;
    private LoggerInterface $logger;
    private array $instances = [];

    public function __construct(StorageFactory $factory, LoggerInterface $logger)
    {
        $this->factory = $factory;
        $this->logger = $logger;
    }

    public function store(string $provider, string $path, string $content): array
    {
        try {
            $storage = $this->getInstance($provider);
            $result = $storage->upload($path, $content);
            
            return $this->addTimeStampData([
                'success' => true,
                'url' => $result['url'],
                'provider' => $provider,
                'path' => $path
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Storage upload failed', [
                'provider' => $provider,
                'path' => $path,
                'error' => $e->getMessage()
            ]);

            return $this->addTimeStampData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function retrieve(string $provider, string $path): ?string
    {
        try {
            $storage = $this->getInstance($provider);
            return $storage->download($path);

        } catch (\Exception $e) {
            $this->logger->error('Storage download failed', [
                'provider' => $provider,
                'path' => $path,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    public function delete(string $provider, string $path): bool
    {
        try {
            $storage = $this->getInstance($provider);
            return $storage->delete($path);

        } catch (\Exception $e) {
            $this->logger->error('Storage delete failed', [
                'provider' => $provider,
                'path' => $path,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function list(string $provider, string $prefix = ''): array
    {
        try {
            $storage = $this->getInstance($provider);
            return $storage->list($prefix);

        } catch (\Exception $e) {
            $this->logger->error('Storage list failed', [
                'provider' => $provider,
                'prefix' => $prefix,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    public function testConnection(string $provider): bool
    {
        try {
            $storage = $this->getInstance($provider);
            return $storage->testConnection();

        } catch (\Exception $e) {
            $this->logger->error('Storage connection test failed', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    private function getInstance(string $provider): StorageInterface
    {
        if (!isset($this->instances[$provider])) {
            $this->instances[$provider] = $this->factory->create($provider);
        }

        return $this->instances[$provider];
    }
}
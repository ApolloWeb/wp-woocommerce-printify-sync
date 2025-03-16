<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\Storage;

use ApolloWeb\WPWooCommercePrintifySync\Services\Storage\Providers\GoogleDriveStorage;
use ApolloWeb\WPWooCommercePrintifySync\Services\Storage\Providers\R2Storage;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\StorageInterface;

class StorageFactory
{
    private ConfigService $config;
    private LoggerInterface $logger;
    private EncryptionService $encryption;

    public function __construct(
        ConfigService $config,
        LoggerInterface $logger,
        EncryptionService $encryption
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->encryption = $encryption;
    }

    public function create(string $provider): StorageInterface
    {
        switch ($provider) {
            case 'google_drive':
                return $this->createGoogleDrive();
            case 'r2':
                return $this->createR2();
            default:
                throw new \InvalidArgumentException("Unknown storage provider: {$provider}");
        }
    }

    private function createGoogleDrive(): StorageInterface
    {
        $config = $this->config->get('google_drive');
        
        return new GoogleDriveStorage(
            $this->encryption->decrypt($config['client_id']),
            $this->encryption->decrypt($config['client_secret']),
            $this->encryption->decrypt($config['refresh_token']),
            $config['folder_id'],
            $this->logger
        );
    }

    private function createR2(): StorageInterface
    {
        $config = $this->config->get('r2');
        
        return new R2Storage(
            $config['account_id'],
            $this->encryption->decrypt($config['access_key_id']),
            $this->encryption->decrypt($config['secret_access_key']),
            $config['bucket_name'],
            $config['bucket_region'] ?? 'auto',
            $this->logger
        );
    }
}
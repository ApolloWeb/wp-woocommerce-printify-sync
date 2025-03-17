<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\Storage;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;
use Aws\S3\S3Client;

class R2Storage implements StorageInterface
{
    private S3Client $client;
    private string $bucket;
    private LoggerInterface $logger;
    private string $accountId;

    public function __construct(
        string $accountId,
        string $accessKey,
        string $secretKey,
        string $bucket,
        LoggerInterface $logger
    ) {
        $this->accountId = $accountId;
        $this->bucket = $bucket;
        $this->logger = $logger;

        $this->client = new S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => "https://{$accountId}.r2.cloudflarestorage.com",
            'credentials' => [
                'key' => $accessKey,
                'secret' => $secretKey,
            ],
            'use_path_style_endpoint' => true
        ]);
    }

    public function store(string $sourcePath, string $destinationPath): ?string
    {
        try {
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $destinationPath,
                'SourceFile' => $sourcePath,
                'ACL' => 'public-read'
            ]);

            return $destinationPath;
        } catch (\Exception $e) {
            $this->logger->error('R2 upload failed', [
                'error' => $e->getMessage(),
                'path' => $destinationPath
            ]);
            return null;
        }
    }

    public function getPublicUrl(string $path): string
    {
        return "https://{$this->accountId}.r2.cloudflarestorage.com/{$this->bucket}/{$path}";
    }

    public function getProviderName(): string
    {
        return 'r2';
    }

    public function delete(string $path): bool
    {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $path
            ]);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('R2 delete failed', [
                'error' => $e->getMessage(),
                'path' => $path
            ]);
            return false;
        }
    }
}
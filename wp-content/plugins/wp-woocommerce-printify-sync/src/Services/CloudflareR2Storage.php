<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\StorageInterface;
use Aws\S3\S3Client;

class CloudflareR2Storage extends AbstractService implements StorageInterface
{
    private ?S3Client $client = null;
    private string $bucket;
    private string $baseUrl;

    public function __construct(
        LoggerInterface $logger,
        ConfigService $config
    ) {
        parent::__construct($logger, $config);
        $this->bucket = $this->config->get('r2_bucket');
        $this->baseUrl = $this->config->get('r2_base_url');
    }

    public function put(string $path, string $contents): bool
    {
        try {
            $client = $this->getClient();
            $result = $client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $this->sanitizePath($path),
                'Body' => $contents,
                'ACL' => 'public-read',
                'ContentType' => $this->getMimeType($path)
            ]);

            return !empty($result['ObjectURL']);

        } catch (\Exception $e) {
            $this->logError('put', $e, ['path' => $path]);
            return false;
        }
    }

    public function get(string $path): ?string
    {
        try {
            $client = $this->getClient();
            $result = $client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $this->sanitizePath($path)
            ]);

            return (string)$result['Body'];

        } catch (\Exception $e) {
            $this->logError('get', $e, ['path' => $path]);
            return null;
        }
    }

    public function delete(string $path): bool
    {
        try {
            $client = $this->getClient();
            $client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $this->sanitizePath($path)
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logError('delete', $e, ['path' => $path]);
            return false;
        }
    }

    public function exists(string $path): bool
    {
        try {
            $client = $this->getClient();
            return $client->doesObjectExist(
                $this->bucket,
                $this->sanitizePath($path)
            );

        } catch (\Exception $e) {
            $this->logError('exists', $e, ['path' => $path]);
            return false;
        }
    }

    public function url(string $path): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($this->sanitizePath($path), '/');
    }

    private function getClient(): S3Client
    {
        if (!$this->client) {
            $this->client = new S3Client([
                'version' => 'latest',
                'region' => 'auto',
                'endpoint' => $this->config->get('r2_endpoint'),
                'credentials' => [
                    'key' => $this->config->get('r2_access_key'),
                    'secret' => $this->config->get('r2_secret_key')
                ],
                'use_path_style_endpoint' => true
            ]);
        }

        return $this->client;
    }

    private function sanitizePath(string $path): string
    {
        return trim(str_replace('\\', '/', $path), '/');
    }

    private function getMimeType(string $path): string
    {
        $mime = wp_check_filetype($path)['type'];
        return $mime ?: 'application/octet-stream';
    }
}
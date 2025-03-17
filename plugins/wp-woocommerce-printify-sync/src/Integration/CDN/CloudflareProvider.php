<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Integration\CDN;

class CloudflareProvider implements CDNProviderInterface
{
    private string $zoneId;
    private string $apiToken;
    private string $cdnDomain;

    public function __construct(string $zoneId, string $apiToken, string $cdnDomain)
    {
        $this->zoneId = $zoneId;
        $this->apiToken = $apiToken;
        $this->cdnDomain = $cdnDomain;
    }

    public function getUrl(string $originalUrl): string
    {
        // Replace the original domain with Cloudflare CDN domain
        $parsedUrl = parse_url($originalUrl);
        if (!$parsedUrl) {
            return $originalUrl;
        }

        $path = $parsedUrl['path'] ?? '';
        $query = isset($parsedUrl['query']) ? "?{$parsedUrl['query']}" : '';

        return "https://{$this->cdnDomain}{$path}{$query}";
    }

    public function purgeUrl(string $url): bool
    {
        try {
            $response = wp_remote_post(
                "https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/purge_cache",
                [
                    'headers' => [
                        'Authorization' => "Bearer {$this->apiToken}",
                        'Content-Type' => 'application/json'
                    ],
                    'body' => json_encode([
                        'files' => [$url]
                    ])
                ]
            );

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            return $body['success'] ?? false;

        } catch (\Exception $e) {
            error_log('Cloudflare purge failed: ' . $e->getMessage());
            return false;
        }
    }

    public function purgeAll(): bool
    {
        try {
            $response = wp_remote_post(
                "https://api.cloudflare.com/client/v4/zones/{$this->zoneId}/purge_cache",
                [
                    'headers' => [
                        'Authorization' => "Bearer {$this->apiToken}",
                        'Content-Type' => 'application/json'
                    ],
                    'body' => json_encode([
                        'purge_everything' => true
                    ])
                ]
            );

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            return $body['success'] ?? false;

        } catch (\Exception $e) {
            error_log('Cloudflare purge all failed: ' . $e->getMessage());
            return false;
        }
    }
}
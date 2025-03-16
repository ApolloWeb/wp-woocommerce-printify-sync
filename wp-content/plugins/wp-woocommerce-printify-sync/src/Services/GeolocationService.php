<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Exceptions\GeolocationException;

class GeolocationService
{
    private const API_URL = 'https://api.ipgeolocation.io/ipgeo';
    private const CACHE_GROUP = 'wpwps_geolocation';
    private ConfigService $config;
    private CacheService $cache;

    public function __construct(ConfigService $config, CacheService $cache)
    {
        $this->config = $config;
        $this->cache = $cache;
    }

    public function getLocation(string $ip = ''): array
    {
        if (empty($ip)) {
            $ip = $this->getClientIP();
        }

        $cacheKey = 'geo_' . md5($ip);
        $cached = $this->cache->get($cacheKey, self::CACHE_GROUP);

        if ($cached !== false) {
            return $cached;
        }

        try {
            $location = $this->fetchLocation($ip);
            $this->cache->set($cacheKey, $location, self::CACHE_GROUP, 24 * HOUR_IN_SECONDS);
            return $location;
        } catch (\Exception $e) {
            throw new GeolocationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function fetchLocation(string $ip): array
    {
        $apiKey = $this->config->get('ipgeolocation_api_key');
        if (empty($apiKey)) {
            throw new GeolocationException('Geolocation API key not configured');
        }

        $response = wp_remote_get(add_query_arg([
            'apiKey' => $apiKey,
            'ip' => $ip
        ], self::API_URL));

        if (is_wp_error($response)) {
            throw new GeolocationException($response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data) || isset($data['error'])) {
            throw new GeolocationException($data['error']['message'] ?? 'Invalid response from geolocation service');
        }

        return [
            'country_code' => $data['country_code2'],
            'country_name' => $data['country_name'],
            'city' => $data['city'],
            'currency' => $data['currency']['code'],
            'timezone' => $data['time_zone']['name'],
        ];
    }

    private function getClientIP(): string
    {
        $ipAddress = '';

        // Check for proxies
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $addresses = explode(',', $_SERVER[$header]);
                $ipAddress = trim($addresses[0]);
                break;
            }
        }

        return $ipAddress;
    }
}
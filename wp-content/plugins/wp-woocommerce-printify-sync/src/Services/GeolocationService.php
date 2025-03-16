<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class GeolocationService extends AbstractService
{
    private const GEOLOCATION_ENDPOINT = 'https://api.ipapi.com/api/';
    private const CACHE_GROUP = 'printify_geolocation';
    private const CACHE_DURATION = 86400; // 24 hours

    public function getUserLocation(): array
    {
        try {
            $ip = $this->getClientIP();
            $cacheKey = 'geo_' . md5($ip);

            // Check cache first
            $cached = wp_cache_get($cacheKey, self::CACHE_GROUP);
            if ($cached) {
                return $cached;
            }

            // Get location from API
            $location = $this->fetchLocation($ip);
            
            // Cache the result
            wp_cache_set($cacheKey, $location, self::CACHE_GROUP, self::CACHE_DURATION);

            return $location;

        } catch (\Exception $e) {
            $this->logError('getUserLocation', $e);
            return $this->getDefaultLocation();
        }
    }

    private function fetchLocation(string $ip): array
    {
        $apiKey = $this->config->get('ipapi_key');
        $response = wp_remote_get(self::GEOLOCATION_ENDPOINT . $ip . '?access_key=' . $apiKey);

        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data) || isset($data['error'])) {
            throw new \Exception('Invalid geolocation response');
        }

        return [
            'country_code' => $data['country_code'],
            'country' => $data['country_name'],
            'region' => $data['region_name'],
            'city' => $data['city'],
            'postal' => $data['zip'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'timezone' => $data['timezone']
        ];
    }

    private function getClientIP(): string
    {
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
                $ips = explode(',', $_SERVER[$header]);
                return trim($ips[0]);
            }
        }

        return '127.0.0.1';
    }

    private function getDefaultLocation(): array
    {
        return [
            'country_code' => 'US',
            'country' => 'United States',
            'region' => 'Unknown',
            'city' => 'Unknown',
            'postal' => '',
            'latitude' => 0,
            'longitude' => 0,
            'timezone' => 'UTC'
        ];
    }
}
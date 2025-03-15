<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Api;

class GeolocationApi extends AbstractApi
{
    protected function initializeHeaders(): void
    {
        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
    }

    public function getLocation(string $ip): array
    {
        return $this->request('GET', '', [
            'apiKey' => $this->apiKey,
            'ip' => $ip
        ]);
    }

    public function getCurrency(string $ip): string
    {
        $location = $this->getLocation($ip);
        return $location['currency']['code'] ?? 'USD';
    }

    public function getTimezone(string $ip): string
    {
        $location = $this->getLocation($ip);
        return $location['time_zone']['name'] ?? 'UTC';
    }
}
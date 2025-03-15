<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

use ApolloWeb\WPWooCommercePrintifySync\Abstracts\BaseAPI;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\DateTimeHelper;

class PrintifyAPI extends BaseAPI
{
    public function __construct(string $apiKey, DateTimeHelper $dateTimeHelper)
    {
        $this->baseUrl = 'https://api.printify.com/v1';
        parent::__construct($apiKey, $dateTimeHelper);
    }

    protected function initializeHeaders(): void
    {
        $this->headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
    }

    public function get(string $endpoint, array $params = []): array
    {
        $url = add_query_arg($params, $this->baseUrl . $endpoint);
        
        $response = wp_remote_get($url, [
            'headers' => $this->getRequestHeaders()
        ]);

        return $this->handleResponse($response);
    }

    public function post(string $endpoint, array $data = []): array
    {
        $response = wp_remote_post(
            $this->baseUrl . $endpoint,
            [
                'headers' => $this->getRequestHeaders(),
                'body' => json_encode($data)
            ]
        );

        return $this->handleResponse($response);
    }

    public function put(string $endpoint, array $data = []): array
    {
        $response = wp_remote_request(
            $this->baseUrl . $endpoint,
            [
                'method' => 'PUT',
                'headers' => $this->getRequestHeaders(),
                'body' => json_encode($data)
            ]
        );

        return $this->handleResponse($response);
    }

    public function delete(string $endpoint): array
    {
        $response = wp_remote_request(
            $this->baseUrl . $endpoint,
            [
                'method' => 'DELETE',
                'headers' => $this->getRequestHeaders()
            ]
        );

        return $this->handleResponse($response);
    }
}
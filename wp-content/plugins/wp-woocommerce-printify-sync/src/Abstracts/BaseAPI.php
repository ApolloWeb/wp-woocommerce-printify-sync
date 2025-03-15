<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Abstracts;

use ApolloWeb\WPWooCommercePrintifySync\Contracts\APIInterface;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\DateTimeHelper;

abstract class BaseAPI implements APIInterface
{
    protected string $apiKey;
    protected string $baseUrl;
    protected array $headers;
    protected DateTimeHelper $dateTimeHelper;

    public function __construct(string $apiKey, DateTimeHelper $dateTimeHelper)
    {
        $this->apiKey = $apiKey;
        $this->dateTimeHelper = $dateTimeHelper;
        $this->initializeHeaders();
    }

    abstract protected function initializeHeaders(): void;

    protected function handleResponse($response): array
    {
        if (is_wp_error($response)) {
            throw new \RuntimeException($response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $statusCode = wp_remote_retrieve_response_code($response);

        if ($statusCode >= 400) {
            throw new \RuntimeException("API request failed with status {$statusCode}");
        }

        return json_decode($body, true);
    }

    protected function getRequestHeaders(): array
    {
        return array_merge(
            $this->headers,
            ['X-Request-Timestamp' => $this->dateTimeHelper->getCurrentTimestamp()]
        );
    }
}
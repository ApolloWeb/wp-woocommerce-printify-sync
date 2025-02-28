/**
 * PrintifyAPI class for Printify Sync plugin
 *
 * Author: Rob Owen
 *
 * Date: 2025-02-28
 *
 * @package ApolloWeb\WooCommercePrintifySync
 */
<?php

declare(strict_types=1);

namespace WP_WooCommerce_Printify_Sync\Includes;

if (!defined('ABSPATH')) {
    exit;
}

class PrintifyAPI
{
    private const BASE_URL = 'https://api.printify.com/v1';
    private string $apiToken;

    public function __construct(string $apiToken)
    {
        $this->apiToken = $apiToken;
    }

    public function getProducts(int $page = 1): array
    {
        $url = self::BASE_URL . "/shops/{shop_id}/products.json?page=$page";

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
            ],
        ]);

        if (is_wp_error($response)) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return $data['data'] ?? [];
    }
}

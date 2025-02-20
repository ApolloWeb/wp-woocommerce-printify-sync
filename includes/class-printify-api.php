<?php
/**
 * User: ApolloWeb
 * Timestamp: 2025-02-20 03:53:44
 */

namespace ApolloWeb\WooCommercePrintifySync\Includes;

use ApolloWeb\WooCommercePrintifySync\Includes\APIClient;

class PrintifyAPI
{
    private $api_client;

    public function __construct($api_key)
    {
        $this->api_client = new APIClient($api_key);
    }

    public function get_shops()
    {
        return $this->api_client->get_shops();
    }

    public function get_products_by_shop($shop_id)
    {
        return $this->api_client->get_products($shop_id);
    }

    public function get_product_details($product_id)
    {
        return $this->api_client->get_product_details($product_id);
    }

    public function get_stock_levels($product_id)
    {
        return $this->api_client->get_stock_levels($product_id);
    }

    public function get_variants($product_id)
    {
        return $this->api_client->get_variants($product_id);
    }

    public function get_tags($product_id)
    {
        return $this->api_client->get_tags($product_id);
    }

    public function get_categories($product_id)
    {
        return $this->api_client->get_categories($product_id);
    }
}
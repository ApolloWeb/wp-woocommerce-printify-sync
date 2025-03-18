<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

// Login: ApolloWeb
// Timestamp: 2025-03-18 07:24:52

class WebhookManager implements ServiceProvider
{
    private $printify_api;

    public function boot()
    {
        $api_key = get_option('printify_api_key');
        $this->printify_api = new PrintifyAPI($api_key);
    }

    public function createWebhook($url, $event)
    {
        return $this->printify_api->createWebhook($url, $event);
    }

    public function updateWebhook($webhook_id, $url, $event)
    {
        return $this->printify_api->updateWebhook($webhook_id, $url, $event);
    }
}
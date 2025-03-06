<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Cron;

class CurrencyExchangeCron {
    public function __construct() {
        add_action('wp', [$this, 'scheduleCron']);
        add_action('currency_exchange_update', [$this, 'updateExchangeRates']);
    }

    public function scheduleCron() {
        if (!wp_next_scheduled('currency_exchange_update')) {
            wp_schedule_event(time(), 'daily', 'currency_exchange_update');
        }
    }

    public function updateExchangeRates() {
        // Code to update currency exchange rates
    }
}
<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class Settings {
    private const OPTION_KEY = 'wpps_settings';

    public function get(string $key, $default = null) {
        $settings = get_option(self::OPTION_KEY, []);
        return $settings[$key] ?? $default;
    }

    public function set(string $key, $value): void {
        $settings = get_option(self::OPTION_KEY, []);
        $settings[$key] = $value;
        update_option(self::OPTION_KEY, $settings);
    }

    public function delete(string $key): void {
        $settings = get_option(self::OPTION_KEY, []);
        unset($settings[$key]);
        update_option(self::OPTION_KEY, $settings);
    }
}

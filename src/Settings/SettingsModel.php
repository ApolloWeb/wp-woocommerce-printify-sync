<?php
/**
 * Settings Model
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Settings
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Settings;

class SettingsModel {
    /**
     * Option name in the database
     *
     * @var string
     */
    private const OPTION_NAME = 'wpwps_settings';
    
    /**
     * Default settings
     *
     * @var array
     */
    private $defaults = [
        'printify_api_key' => '',
        'printify_endpoint' => 'https://api.printify.com/v1/',
        'shop_id' => 0,
        'chatgpt_api_key' => '',
        'monthly_spend_cap' => 5,
        'temperature' => 0.7
    ];
    
    /**
     * Get all settings
     *
     * @return array Settings array
     */
    public function getSettings(): array {
        $settings = get_option(self::OPTION_NAME, []);
        return wp_parse_args($settings, $this->defaults);
    }
    
    /**
     * Save settings
     *
     * @param array $settings Settings to save
     * @return bool Whether the settings were saved successfully
     */
    public function saveSettings(array $settings): bool {
        $existingSettings = $this->getSettings();
        $newSettings = wp_parse_args($settings, $existingSettings);
        return update_option(self::OPTION_NAME, $newSettings);
    }
    
    /**
     * Get a specific setting
     *
     * @param string $key Setting key
     * @param mixed $default Default value if setting not found
     * @return mixed Setting value
     */
    public function getSetting(string $key, $default = null) {
        $settings = $this->getSettings();
        return $settings[$key] ?? $default;
    }
}

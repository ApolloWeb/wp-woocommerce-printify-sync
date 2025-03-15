<?php
/**
 * Main plugin class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Admin\AdminMenu;
use ApolloWeb\WPWooCommercePrintifySync\Core\ActivationHandler;
use ApolloWeb\WPWooCommercePrintifySync\Core\AjaxHandler;
use ApolloWeb\WPWooCommercePrintifySync\Core\AssetsHandler;
use ApolloWeb\WPWooCommercePrintifySync\Core\DeactivationHandler;
use ApolloWeb\WPWooCommercePrintifySync\Core\WebhookHandler;
use ApolloWeb\WPWooCommercePrintifySync\Core\CronHandler;

/**
 * Class Plugin
 */
class Plugin
{
    /**
     * Plugin instance
     *
     * @var Plugin|null
     */
    private static ?Plugin $instance = null;

    /**
     * Get plugin instance
     *
     * @return Plugin
     */
    public static function getInstance(): Plugin
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Private constructor to prevent direct object creation
     */
    private function __construct()
    {
    }

    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function init(): void
    {
        // Check if WooCommerce is active
        if (!$this->isWooCommerceActive()) {
            add_action('admin_notices', [$this, 'wooCommerceNotActiveNotice']);
            return;
        }

        // Register activation and deactivation hooks
        register_activation_hook(WPPS_PLUGIN_BASENAME, [ActivationHandler::class, 'activate']);
        register_deactivation_hook(WPPS_PLUGIN_BASENAME, [DeactivationHandler::class, 'deactivate']);

        // Initialize components
        $this->initCore();
        $this->initHooks();
    }
    
    /**
     * Check if WooCommerce is active
     *
     * @return bool
     */
    public function isWooCommerceActive(): bool
    {
        return in_array(
            'woocommerce/woocommerce.php', 
            apply_filters('active_plugins', get_option('active_plugins'))
        );
    }

    /**
     * Display WooCommerce not active notice
     *
     * @return void
     */
    public function wooCommerceNotActiveNotice(): void
    {
        $class = 'notice notice-error';
        $message = __('WP WooCommerce Printify Sync requires WooCommerce to be installed and activated!', 'wp-woocommerce-printify-sync');

        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }

    /**
     * Initialize core components
     *
     * @return void
     */
    private function initCore(): void
    {
        // Initialize classes
        new AdminMenu();
        new AssetsHandler();
        new AjaxHandler();
        new WebhookHandler();
        new CronHandler();
    }

    /**
     * Initialize WordPress hooks
     *
     * @return void
     */
    private function initHooks(): void
    {
        // Add textdomain
        add_action('plugins_loaded', [$this, 'loadTextdomain']);
        
        // Add plugin action links
        add_filter(
            'plugin_action_links_' . WPPS_PLUGIN_BASENAME,
            [$this, 'addPluginActionLinks']
        );
    }

    /**
     * Load plugin textdomain
     *
     * @return void
     */
    public function loadTextdomain(): void
    {
        load_plugin_textdomain(
            'wp-woocommerce-printify-sync',
            false,
            dirname(WPPS_PLUGIN_BASENAME) . '/languages/'
        );
    }

    /**
     * Add plugin action links
     *
     * @param array $links Existing action links
     * @return array Modified action links
     */
    public function addPluginActionLinks(array $links): array
    {
        $pluginLinks = [
            '<a href="' . admin_url('admin.php?page=wpps-settings') . '">' .
            __('Settings', 'wp-woocommerce-printify-sync') .
            '</a>',
        ];

        return array_merge($pluginLinks, $links);
    }
}
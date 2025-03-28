<?php
/**
 * Base Service Provider
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Abstract ServiceProvider class
 */
abstract class ServiceProvider
{
    /**
     * The plugin instance
     *
     * @var Plugin
     */
    protected $plugin;

    /**
     * Constructor
     *
     * @param Plugin $plugin The plugin instance
     */
    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Register the service provider
     *
     * @return void
     */
    abstract public function register();

    /**
     * Bootstrap any application services
     *
     * @return void
     */
    public function boot()
    {
        // This method may be implemented by child classes
    }

    /**
     * Add admin menu page
     *
     * @param string $page_title The page title
     * @param string $menu_title The menu title
     * @param string $capability The capability required
     * @param string $menu_slug  The menu slug
     * @param string $icon       The menu icon
     * @param int    $position   The menu position
     * @return string The hook suffix or false if the page was not added
     */
    protected function addMenuPage($page_title, $menu_title, $capability, $menu_slug, $icon = '', $position = null)
    {
        return add_menu_page(
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            [$this, 'renderPage'],
            $icon,
            $position
        );
    }

    /**
     * Add submenu page
     *
     * @param string   $parent_slug The parent menu slug
     * @param string   $page_title  The page title
     * @param string   $menu_title  The menu title
     * @param string   $capability  The capability required
     * @param string   $menu_slug   The menu slug
     * @param callable $callback    Optional. The callback function. Default null.
     * @param int|null $position    Optional. The menu position. Default null.
     * @return string The hook suffix or false if the page was not added
     */
    protected function addSubmenuPage($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback = null, $position = null)
    {
        if ($callback === null) {
            $callback = [$this, 'renderPage'];
        }
        
        return add_submenu_page(
            $parent_slug,
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            $callback,
            $position
        );
    }

    /**
     * Render the admin page
     *
     * @return void
     */
    public function renderPage()
    {
        // This method should be implemented by child classes
        echo '<div class="wrap"><h1>' . esc_html(get_admin_page_title()) . '</h1></div>';
    }

    /**
     * Get a service provider instance
     *
     * @param string $class Provider class name
     * @return object|null Provider instance or null if not found
     */
    protected function getProvider($class)
    {
        return $this->plugin->getProvider($class);
    }

    /**
     * Verify nonce for AJAX requests
     *
     * @param string $action The nonce action
     * @return bool True if nonce is valid, false otherwise
     */
    protected function verifyNonce($action = 'wpwps-ajax-nonce')
    {
        if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], $action)) {
            return false;
        }
        
        return true;
    }

    /**
     * Check user capabilities for AJAX requests
     *
     * @param string $capability The capability to check
     * @return bool True if user has capability, false otherwise
     */
    protected function checkCapability($capability = 'manage_woocommerce')
    {
        if (!current_user_can($capability)) {
            return false;
        }
        
        return true;
    }
}
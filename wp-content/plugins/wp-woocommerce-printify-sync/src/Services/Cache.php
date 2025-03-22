<?php
/**
 * Cache Service.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Services
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

/**
 * Cache service for handling temporary data storage.
 */
class Cache
{
    /**
     * Cache group prefix.
     *
     * @var string
     */
    private $prefix = 'wpwps_';

    /**
     * Set a value in the cache.
     *
     * @param string $key    Cache key.
     * @param mixed  $value  Value to cache.
     * @param int    $ttl    Time to live in seconds. Default is 1 hour.
     * @return bool Whether the cache was set successfully.
     */
    public function set($key, $value, $ttl = 3600)
    {
        return set_transient($this->prefix . $key, $value, $ttl);
    }

    /**
     * Get a value from the cache.
     *
     * @param string $key Cache key.
     * @return mixed|false Value or false if not found.
     */
    public function get($key)
    {
        return get_transient($this->prefix . $key);
    }

    /**
     * Delete a value from the cache.
     *
     * @param string $key Cache key.
     * @return bool Whether the cache was deleted successfully.
     */
    public function delete($key)
    {
        return delete_transient($this->prefix . $key);
    }

    /**
     * Clear all cache entries with a specific prefix.
     *
     * @param string $key_prefix Prefix for keys to clear.
     * @return void
     */
    public function clearByPrefix($key_prefix)
    {
        global $wpdb;

        $prefix = $this->prefix . $key_prefix;
        $options_table = $wpdb->options;
        $sql = "DELETE FROM $options_table WHERE option_name LIKE '_transient_%' AND option_name LIKE %s";
        $wpdb->query($wpdb->prepare($sql, '%' . $wpdb->esc_like($prefix) . '%'));

        $sql = "DELETE FROM $options_table WHERE option_name LIKE '_transient_timeout_%' AND option_name LIKE %s";
        $wpdb->query($wpdb->prepare($sql, '%' . $wpdb->esc_like($prefix) . '%'));
    }

    /**
     * Remember a value in the cache if it's not already set.
     *
     * @param string   $key     Cache key.
     * @param callable $callback Callback to generate value if not cached.
     * @param int      $ttl     Time to live in seconds. Default is 1 hour.
     * @return mixed The cached value.
     */
    public function remember($key, $callback, $ttl = 3600)
    {
        $cached = $this->get($key);

        if ($cached !== false) {
            return $cached;
        }

        $value = call_user_func($callback);
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Flush all cache entries.
     *
     * @return void
     */
    public function flush()
    {
        global $wpdb;

        $options_table = $wpdb->options;
        $sql = "DELETE FROM $options_table WHERE option_name LIKE '_transient_%' AND option_name LIKE %s";
        $wpdb->query($wpdb->prepare($sql, '%' . $wpdb->esc_like($this->prefix) . '%'));

        $sql = "DELETE FROM $options_table WHERE option_name LIKE '_transient_timeout_%' AND option_name LIKE %s";
        $wpdb->query($wpdb->prepare($sql, '%' . $wpdb->esc_like($this->prefix) . '%'));
    }
}

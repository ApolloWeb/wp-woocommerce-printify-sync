<?php
/**
 * Cache Interface
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Interfaces
 * @author ApolloWeb <hello@apollo-web.co.uk>
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Interfaces;

/**
 * CacheInterface Interface
 */
interface CacheInterface {
    /**
     * Get an item from the cache
     *
     * @param string $key Cache key
     * @param mixed  $default Default value if not found
     * @return mixed
     */
    public function get($key, $default = null);
    
    /**
     * Set an item in the cache
     *
     * @param string $key Cache key
     * @param mixed  $value Value to cache
     * @param int    $expiration Expiration time in seconds
     * @return bool
     */
    public function set($key, $value, $expiration = 0);
    
    /**
     * Check if an item exists in the cache
     *
     * @param string $key Cache key
     * @return bool
     */
    public function has($key);
    
    /**
     * Delete an item from the cache
     *
     * @param string $key Cache key
     * @return bool
     */
    public function delete($key);
    
    /**
     * Flush the cache
     *
     * @return bool
     */
    public function flush();
}
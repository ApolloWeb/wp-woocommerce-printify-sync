<?php
/**
 * Redis Cache Handler
 *
 * Provides Redis-based caching functionality.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Utils
 * @author ApolloWeb <hello@apollo-web.co.uk>
 * @since 1.0.0
 * @updated 2025-03-09 13:25:00
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Utils;

/**
 * RedisCache Class
 */
class RedisCache {
    /**
     * Redis client instance
     *
     * @var \Redis
     */
    private $redis;
    
    /**
     * Whether Redis is available
     *
     * @var bool
     */
    private $is_connected = false;
    
    /**
     * Cache prefix
     *
     * @var string
     */
    private $prefix = 'apolloweb_printify_';
    
    /**
     * Constructor
     *
     * @param string $host Redis host
     * @param int $port Redis port
     * @param string $password Redis password
     * @param int $database Redis database
     * @param string $prefix Cache key prefix
     */
    public function __construct($host = '127.0.0.1', $port = 6379, $password = '', $database = 0, $prefix = '') {
        if (!class_exists('Redis')) {
            return;
        }
        
        if (!empty($prefix)) {
            $this->prefix = $prefix;
        }
        
        try {
            $this->redis = new \Redis();
            $this->is_connected = $this->redis->connect($host, $port);
            
            if (!empty($password)) {
                $this->redis->auth($password);
            }
            
            if ($database !== 0) {
                $this->redis->select($database);
            }
        } catch (\Exception $e) {
            error_log('Redis connection error: ' . $e->getMessage());
            $this->is_connected = false;
        }
    }
    
    /**
     * Check if Redis is connected
     *
     * @return bool
     */
    public function isConnected() {
        return $this->is_connected;
    }
    
    /**
     * Get a value from cache
     *
     * @param string $key Cache key
     * @param mixed $default Default value
     * @return mixed
     */
    public function get($key, $default = null) {
        if (!$this->is_connected) {
            return $default;
        }
        
        $value = $this->redis->get($this->prefix . $key);
        
        if ($value === false) {
            return $default;
        }
        
        return unserialize($value);
    }
    
    /**
     * Set a value in cache
     *
     * @param string $key Cache key
     * @param mixed $value Cache value
     * @param int $ttl Time to live in seconds
     * @return bool
     */
    public function set($key, $value, $ttl = 3600) {
        if (!$this->is_connected) {
            return false;
        }
        
        return $this->redis->setex(
            $this->prefix . $key,
            $ttl,
            serialize($value)
        );
    }
    
    /**
     * Delete a value from cache
     *
     * @param string $key Cache key
     * @return bool
     */
    public function delete($key) {
        if (!$this->is_connected) {
            return false;
        }
        
        return $this->redis->del($this->prefix . $key) > 0;
    }
    
    /**
     * Flush all keys with the plugin's prefix
     *
     * @return bool
     */
    public function flushPlugin() {
        if (!$this->is_connected) {
            return false;
        }
        
        $keys = $this->redis->keys($this->prefix . '*');
        
        if (empty($keys)) {
            return true;
        }
        
        return $this->redis->del($keys) > 0;
    }
    
    /**
     * Check if a key exists in cache
     *
     * @param string $key Cache key
     * @return bool
     */
    public function exists($key) {
        if (!$this->is_connected) {
            return false;
        }
        
        return $this->redis->exists($this->prefix . $key) > 0;
    }
    
    /**
     * Increment a value in cache
     *
     * @param string $key Cache key
     * @param int $value Increment value
     * @return int|bool
     */
    public function increment($key, $value = 1) {
        if (!$this->is_connected) {
            return false;
        }
        
        return $this->redis->incrBy($this->prefix . $key, $value);
    }
    
    /**
     * Store data in hash
     *
     * @param string $key Hash key
     * @param string $field Hash field
     * @param mixed $value Hash value
     * @return bool
     */
    public function hashSet($key, $field, $value) {
        if (!$this->is_connected) {
            return false;
        }
        
        return $this->redis->hSet(
            $this->prefix . $key,
            $field,
            serialize($value)
        );
    }
    
    /**
     * Get data from hash
     *
     * @param string $key Hash key
     * @param string $field Hash field
     * @param mixed $default Default value
     * @return mixed
     */
    public function hashGet($key, $field, $default = null) {
        if (!$this->is_connected) {
            return $default;
        }
        
        $value = $this->redis->hGet($this->prefix . $key, $field);
        
        if ($value === false) {
            return $default;
        }
        
        return unserialize($value);
    }
}
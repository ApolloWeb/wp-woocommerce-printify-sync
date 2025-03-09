<?php
/**
 * Redis Cache Implementation
 *
 * Implements caching using Redis.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Utils
 * @author ApolloWeb <hello@apollo-web.co.uk>
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Utils;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\CacheInterface;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;

/**
 * RedisCache Class
 */
class RedisCache implements CacheInterface {
    /**
     * Redis instance
     *
     * @var \Redis
     */
    private $redis;
    
    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * Cache key prefix
     *
     * @var string
     */
    private $prefix = 'apolloweb_printify_';
    
    /**
     * Whether Redis is available
     *
     * @var bool
     */
    private $is_available = false;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger Logger instance
     */
    public function __construct(LoggerInterface $logger = null) {
        $this->logger = $logger;
        $this->initRedis();
    }
    
    /**
     * Initialize Redis connection
     *
     * @return void
     */
    private function initRedis() {
        // Check if Redis is already available via the Redis Object Cache plugin
        if (function_exists('wp_redis') && wp_redis()->redis instanceof \Redis) {
            $this->redis = wp_redis()->redis;
            $this->is_available = true;
            
            if ($this->logger) {
                $this->logger->info('Using existing Redis connection from Redis Object Cache plugin');
            }
            
            return;
        }
        
        // Otherwise try to establish our own connection
        if (class_exists('Redis')) {
            try {
                $this->redis = new \Redis();
                
                // Get Redis configuration from wp-config.php constants or use defaults
                $host = defined('WP_REDIS_HOST') ? WP_REDIS_HOST : '127.0.0.1';
                $port = defined('WP_REDIS_PORT') ? WP_REDIS_PORT : 6379;
                $timeout = defined('WP_REDIS_TIMEOUT') ? WP_REDIS_TIMEOUT : 1;
                $database = defined('WP_REDIS_DATABASE') ? WP_REDIS_DATABASE : 0;
                
                // Connect to Redis
                if ($this->redis->connect($host, $port, $timeout)) {
                    // Select database
                    $this->redis->select($database);
                    
                    // Check if authentication is required
                    if (defined('WP_REDIS_PASSWORD') && WP_REDIS_PASSWORD) {
                        $this->redis->auth(WP_REDIS_PASSWORD);
                    }
                    
                    $this->is_available = true;
                    
                    if ($this->logger) {
                        $this->logger->info('Successfully connected to Redis server');
                    }
                }
            } catch (\Exception $e) {
                $this->is_available = false;
                
                if ($this->logger) {
                    $this->logger->error('Failed to connect to Redis server: ' . $e->getMessage());
                }
            }
        } else {
            if ($this->logger) {
                $this->logger->warning('Redis PHP extension not installed');
            }
        }
    }
    
    /**
     * Generate full cache key with prefix
     *
     * @param string $key Cache key
     * @return string
     */
    private function getFullKey($key) {
        return $this->prefix . $key;
    }

    /**
     * Get an item from the cache
     *
     * @param string $key Cache key
     * @param mixed  $default Default value if not found
     * @return mixed
     */
    public function get($key, $default = null) {
        if (!$this->is_available) {
            return $default;
        }
        
        $full_key = $this->getFullKey($key);
        $result = $this->redis->get($full_key);
        
        if (false === $result) {
            return $default;
        }
        
        $data = unserialize($result);
        return false === $data ? $default : $data;
    }
    
    /**
     * Set an item in the cache
     *
     * @param string $key Cache key
     * @param mixed  $value Value to cache
     * @param int    $expiration Expiration time in seconds
     * @return bool
     */
    public function set($key, $value, $expiration = 0) {
        if (!$this->is_available) {
            return false;
        }
        
        $full_key = $this->getFullKey($key);
        $data = serialize($value);
        
        if ($expiration > 0) {
            return $this->redis->setex($full_key, $expiration, $data);
        }
        
        return $this->redis->set($full_key, $data);
    }
    
    /**
     * Check if an item exists in the cache
     *
     * @param string $key Cache key
     * @return bool
     */
    public function has($key) {
        if (!$this->is_available) {
            return false;
        }
        
        $full_key = $this->getFullKey($key);
        return (bool) $this->redis->exists($full_key);
    }
    
    /**
     * Delete an item from the cache
     *
     * @param string $key Cache key
     * @return bool
     */
    public function delete($key) {
        if (!$this->is_available) {
            return false;
        }
        
        $full_key = $this->getFullKey($key);
        return (bool) $this->redis->del($full_key);
    }
    
    /**
     * Flush the cache
     *
     * @return bool
     */
    public function flush() {
        if (!$this->is_available) {
            return false;
        }
        
        // Get all keys with our prefix
        $keys = $this->redis->keys($this->prefix . '*');
        
        if (!empty($keys)) {
            return (bool) $this->redis->del($keys);
        }
        
        return true;
    }
}
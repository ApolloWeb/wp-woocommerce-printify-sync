<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class CacheService {
    private const CACHE_GROUP = 'wpwps_cache';
    private const DEFAULT_TTL = 3600; // 1 hour

    public function get(string $key, $default = null) {
        $value = wp_cache_get($key, self::CACHE_GROUP);
        return $value === false ? $default : $value;
    }

    public function set(string $key, $value, int $ttl = self::DEFAULT_TTL): bool {
        return wp_cache_set($key, $value, self::CACHE_GROUP, $ttl);
    }

    public function delete(string $key): bool {
        return wp_cache_delete($key, self::CACHE_GROUP);
    }

    public function flush(): bool {
        return wp_cache_flush();
    }

    public function remember(string $key, callable $callback, int $ttl = self::DEFAULT_TTL) {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    public function rememberForever(string $key, callable $callback) {
        return $this->remember($key, $callback, 0);
    }

    public function tags(array $tags): self {
        // Initialize a new instance for tag-based caching
        $instance = new self();
        $instance->setTags($tags);
        return $instance;
    }

    public function clearTag(string $tag): void {
        $this->incrementTagVersion($tag);
    }

    private function setTags(array $tags): void {
        $this->tags = array_map(function($tag) {
            return $this->getTagKey($tag);
        }, $tags);
    }

    private function getTagKey(string $tag): string {
        $version = wp_cache_get("tag_$tag", self::CACHE_GROUP) ?: 1;
        return "tag_{$tag}_v{$version}";
    }

    private function incrementTagVersion(string $tag): void {
        $version = wp_cache_get("tag_$tag", self::CACHE_GROUP) ?: 1;
        wp_cache_set("tag_$tag", $version + 1, self::CACHE_GROUP);
    }

    public function getProductCache(int $product_id) {
        return $this->get("product_$product_id");
    }

    public function setProductCache(int $product_id, array $data): bool {
        return $this->set("product_$product_id", $data);
    }

    public function clearProductCache(int $product_id): bool {
        return $this->delete("product_$product_id");
    }

    public function getOrderCache(int $order_id) {
        return $this->get("order_$order_id");
    }

    public function setOrderCache(int $order_id, array $data): bool {
        return $this->set("order_$order_id", $data);
    }

    public function clearOrderCache(int $order_id): bool {
        return $this->delete("order_$order_id");
    }

    public function getShippingCache(string $zone_id) {
        return $this->get("shipping_$zone_id");
    }

    public function setShippingCache(string $zone_id, array $data): bool {
        return $this->set("shipping_$zone_id", $data);
    }

    public function clearShippingCache(string $zone_id): bool {
        return $this->delete("shipping_$zone_id");
    }

    public function getStatsCache(string $metric) {
        return $this->get("stats_$metric");
    }

    public function setStatsCache(string $metric, $data, int $ttl = 300): bool {
        return $this->set("stats_$metric", $data, $ttl);
    }

    public function clearStatsCache(string $metric): bool {
        return $this->delete("stats_$metric");
    }

    public function getApiCache(string $endpoint, array $params = []) {
        $key = 'api_' . md5($endpoint . serialize($params));
        return $this->get($key);
    }

    public function setApiCache(string $endpoint, array $params, $data, int $ttl = 300): bool {
        $key = 'api_' . md5($endpoint . serialize($params));
        return $this->set($key, $data, $ttl);
    }

    public function clearApiCache(string $endpoint = '', array $params = []): void {
        if (empty($endpoint)) {
            $this->clearByPrefix('api_');
            return;
        }

        $key = 'api_' . md5($endpoint . serialize($params));
        $this->delete($key);
    }

    private function clearByPrefix(string $prefix): void {
        global $wpdb;

        $cache_keys = $wpdb->get_col($wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options} 
            WHERE option_name LIKE %s",
            $wpdb->esc_like("_transient_" . self::CACHE_GROUP . "_" . $prefix) . '%'
        ));

        foreach ($cache_keys as $key) {
            $cache_key = str_replace("_transient_" . self::CACHE_GROUP . "_", '', $key);
            $this->delete($cache_key);
        }
    }

    public function warmupCache(): void {
        global $wpdb;

        // Cache frequently accessed products
        $popular_products = $wpdb->get_col("
            SELECT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_product_id'
            ORDER BY post_id DESC 
            LIMIT 100
        ");

        foreach ($popular_products as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $this->setProductCache($product_id, [
                    'title' => $product->get_name(),
                    'price' => $product->get_price(),
                    'stock' => $product->get_stock_quantity(),
                    'printify_id' => get_post_meta($product_id, '_printify_product_id', true)
                ]);
            }
        }

        // Cache shipping zones
        $shipping_zones = \WC_Shipping_Zones::get_zones();
        foreach ($shipping_zones as $zone_id => $zone) {
            $this->setShippingCache((string) $zone_id, [
                'name' => $zone['zone_name'],
                'locations' => $zone['zone_locations'],
                'methods' => array_map(function($method) {
                    return [
                        'id' => $method->id,
                        'title' => $method->get_title(),
                        'enabled' => $method->is_enabled()
                    ];
                }, $zone['shipping_methods'])
            ]);
        }

        // Cache basic stats
        $stats_service = new StatsService();
        $this->setStatsCache('dashboard', $stats_service->getDashboardStats());
    }
}
<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Repositories;

/**
 * Shipping Repository
 * 
 * Handles data storage and retrieval for shipping profiles
 */
class ShippingRepository {
    /**
     * @var string Table name
     */
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wpwps_shipping_profiles';
    }
    
    /**
     * Save shipping profiles to database
     *
     * @param array $profiles Shipping profiles
     * @return bool Success
     */
    public function saveShippingProfiles(array $profiles): bool {
        global $wpdb;
        
        // Truncate the table to ensure clean data
        $wpdb->query("TRUNCATE TABLE {$this->table_name}");
        
        // Insert new profiles
        foreach ($profiles as $profile) {
            $wpdb->insert(
                $this->table_name,
                [
                    'provider_id' => $profile['provider_id'],
                    'provider_name' => $profile['provider_name'],
                    'profile_id' => $profile['id'],
                    'name' => $profile['name'],
                    'countries' => maybe_serialize($profile['countries']),
                    'pricing' => maybe_serialize($profile['pricing']),
                    'created_at' => current_time('mysql')
                ],
                ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
            );
        }
        
        return true;
    }
    
    /**
     * Get all shipping profiles from database
     *
     * @return array Shipping profiles
     */
    public function getAllShippingProfiles(): array {
        global $wpdb;
        
        $profiles = $wpdb->get_results(
            "SELECT * FROM {$this->table_name}",
            ARRAY_A
        );
        
        if (empty($profiles)) {
            return [];
        }
        
        // Unserialize countries and pricing
        foreach ($profiles as &$profile) {
            $profile['countries'] = maybe_unserialize($profile['countries']);
            $profile['pricing'] = maybe_unserialize($profile['pricing']);
        }
        
        return $profiles;
    }
    
    /**
     * Get shipping profile for a provider and country
     *
     * @param string $provider_id Provider ID
     * @param string $country_code Country code
     * @return array|null Shipping profile or null if not found
     */
    public function getShippingProfileForProvider(string $provider_id, string $country_code): ?array {
        global $wpdb;
        
        $profiles = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE provider_id = %s",
                $provider_id
            ),
            ARRAY_A
        );
        
        if (empty($profiles)) {
            return null;
        }
        
        foreach ($profiles as $profile) {
            $countries = maybe_unserialize($profile['countries']);
            $pricing = maybe_unserialize($profile['pricing']);
            
            // Check if this profile applies to the country
            foreach ($countries as $country) {
                if ($country['code'] === $country_code) {
                    // Find the pricing for this country
                    foreach ($pricing as $price) {
                        if (in_array($country_code, $price['countries'])) {
                            return [
                                'id' => $profile['profile_id'],
                                'name' => $profile['name'],
                                'provider_id' => $profile['provider_id'],
                                'provider_name' => $profile['provider_name'],
                                'first_item' => $price['first_item'],
                                'additional_item' => $price['additional_item'],
                                'shipping_time' => $price['shipping_time'] ?? '',
                                'carrier' => $price['carrier'] ?? ''
                            ];
                        }
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get provider name by ID
     *
     * @param string $provider_id Provider ID
     * @return string Provider name
     */
    public function getProviderName(string $provider_id): string {
        global $wpdb;
        
        $name = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT provider_name FROM {$this->table_name} WHERE provider_id = %s LIMIT 1",
                $provider_id
            )
        );
        
        return $name ?: __('Printify Provider', 'wp-woocommerce-printify-sync');
    }
    
    /**
     * Get shipping zone by name
     *
     * @param string $name Zone name
     * @return \WC_Shipping_Zone|null Zone or null if not found
     */
    public function getShippingZoneByName(string $name): ?\WC_Shipping_Zone {
        $zones = \WC_Shipping_Zones::get_zones();
        
        foreach ($zones as $zone) {
            if ($zone['zone_name'] === $name) {
                return new \WC_Shipping_Zone($zone['id']);
            }
        }
        
        return null;
    }
    
    /**
     * Create table if it doesn't exist
     */
    public function createTable(): void {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            provider_id varchar(255) NOT NULL,
            provider_name varchar(255) NOT NULL,
            profile_id varchar(255) NOT NULL,
            name varchar(255) NOT NULL,
            countries longtext NOT NULL,
            pricing longtext NOT NULL,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            KEY provider_id (provider_id),
            KEY profile_id (profile_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

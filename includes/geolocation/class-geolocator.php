<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Geolocation;

class Geolocator {
    /**
     * Singleton instance
     *
     * @var Geolocator
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return Geolocator
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Initialization code
    }

    /**
     * Get user's timezone
     *
     * @return string Timezone string
     */
    public function get_user_timezone() {
        $country = $this->get_user_country();
        
        // Map major countries to timezones (simplified)
        $timezone_map = array(
            'US' => 'America/New_York',
            'CA' => 'America/Toronto',
            'GB' => 'Europe/London',
            'AU' => 'Australia/Sydney',
            'DE' => 'Europe/Berlin',
            'FR' => 'Europe/Paris',
            'JP' => 'Asia/Tokyo',
            'CN' => 'Asia/Shanghai',
            'IN' => 'Asia/Kolkata',
            'BR' => 'America/Sao_Paulo'
        );
        
        if (isset($timezone_map[$country])) {
            return $timezone_map[$country];
        }
        
        // Default to UTC if not found
        return 'UTC';
    }
    
    /**
     * Get local time for user
     *
     * @param string $format Date format string
     * @return string Formatted local time
     */
    public function get_user_local_time($format = 'Y-m-d H:i:s') {
        $timezone = new \DateTimeZone($this->get_user_timezone());
        $datetime = new \DateTime('now', $timezone);
        return $datetime->format($format);
    }
    
    /**
     * Calculate shipping costs based on location
     *
     * @param float $base_cost Base shipping cost
     * @param string $destination_country Destination country (if different from user's country)
     * @return float Adjusted shipping cost
     */
    public function calculate_shipping_cost($base_cost, $destination_country = '') {
        $country = !empty($destination_country) ? $destination_country : $this->get_user_country();
        $continent = $this->get_continent_for_country($country);
        
        // Example shipping adjustment factors
        $shipping_factors = array(
            'NA' => 1.0,    // Base rate for North America
            'EU' => 1.2,    // 20% higher for Europe
            'AS' => 1.5,    // 50% higher for Asia
            'OC' => 1.7,    // 70% higher for Oceania
            'SA' => 1.3,    // 30% higher for South America
            'AF' => 1.8     // 80% higher for Africa
        );
        
        $factor = isset($shipping_factors[$continent]) ? $shipping_factors[$continent] : 1.5;
        
        return round($base_cost * $factor, 2);
    }
    
    /**
     * Get continent for a given country
     *
     * @param string $country Country code
     * @return string Continent code
     */
    private function get_continent_for_country($country) {
        // Map countries to continents
        $continents_map = [
            'AF' => ['DZ', 'AO', 'BJ', 'BW', 'BF', 'BI', 'CM', 'CV', 'CF', 'TD', 'KM', 'CG', 'CD', 'DJ', 'EG', 'GQ', 'ER', 'ET', 'GA', 'GM', 'GH', 'GN', 'GW', 'CI', 'KE', 'LS', 'LR', 'LY', 'MG', 'MW', 'ML', 'MR', 'MU', 'YT', 'MA', 'MZ', 'NA', 'NE', 'NG', 'RE', 'RW', 'SH', 'ST', 'SN', 'SC', 'SL', 'SO', 'ZA', 'SS', 'SD', 'SZ', 'TZ', 'TG', 'TN', 'UG', 'EH', 'ZM', 'ZW'],
            'AS' => ['AF', 'AM', 'AZ', 'BH', 'BD', 'BT', 'BN', 'KH', 'CN', 'CY', 'GE', 'HK', 'IN', 'ID', 'IR', 'IQ', 'IL', 'JP', 'JO', 'KZ', 'KW', 'KG', 'LA', 'LB', 'MO', 'MY', 'MV', 'MN', 'MM', 'NP', 'KP', 'OM', 'PK', 'PS', 'PH', 'QA', 'SA', 'SG', 'KR', 'LK', 'SY', 'TW', 'TJ', 'TH', 'TL', 'TM', 'AE', 'UZ', 'VN', 'YE'],
            'EU' => ['AX', 'AL', 'AD', 'AT', 'BY', 'BE', 'BA', 'BG', 'HR', 'CZ', 'DK', 'EE', 'FO', 'FI', 'FR', 'DE', 'GI', 'GR', 'GG', 'VA', 'HU', 'IS', 'IE', 'IM', 'IT', 'JE', 'LV', 'LI', 'LT', 'LU', 'MT', 'MD', 'MC', 'ME', 'NL', 'MK', 'NO', 'PL', 'PT', 'RO', 'RU', 'SM', 'RS', 'SK', 'SI', 'ES', 'SJ', 'SE', 'CH', 'UA', 'GB'],
            'NA' => ['AI', 'AG', 'AW', 'BS', 'BB', 'BZ', 'BM', 'BQ', 'VG', 'CA', 'KY', 'CR', 'CU', 'CW', 'DM', 'DO', 'SV', 'GL', 'GD', 'GP', 'GT', 'HT', 'HN', 'JM', 'MQ', 'MX', 'MS', 'NI', 'PA', 'PR', 'BL', 'KN', 'LC', 'MF', 'VC', 'SX', 'TT', 'TC', 'US', 'VI'],
            'OC' => ['AS', 'AU', 'CK', 'FJ', 'PF', 'GU', 'KI', 'MH', 'FM', 'NR', 'NC', 'NZ', 'NU', 'NF', 'MP', 'PW', 'PG', 'PN', 'WS', 'SB', 'TK', 'TO', 'TV', 'VU', 'WF'],
            'SA' => ['AR', 'BO', 'BR', 'CL', 'CO', 'EC', 'FK', 'GF', 'GY', 'PY', 'PE', 'SR', 'UY', 'VE']
        ];
        
        foreach ($continents_map as $continent => $countries) {
            if (in_array($country, $countries, true)) {
                return $continent;
            }
        }
        
        return 'NA'; // Default to North America if not found
    }
}
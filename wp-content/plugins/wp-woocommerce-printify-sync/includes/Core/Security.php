<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class Security {
    private static $encryption_key = null;

    public static function init() {
        self::$encryption_key = defined('SECURE_AUTH_KEY') ? SECURE_AUTH_KEY : wp_salt('auth');
    }

    public static function encrypt($value) {
        if (empty($value)) return '';
        $method = 'aes-256-cbc';
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($value, $method, self::$encryption_key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    public static function decrypt($value) {
        if (empty($value)) return '';
        $value = base64_decode($value);
        $iv = substr($value, 0, 16);
        $encrypted = substr($value, 16);
        return openssl_decrypt($encrypted, 'aes-256-cbc', self::$encryption_key, 0, $iv);
    }
}

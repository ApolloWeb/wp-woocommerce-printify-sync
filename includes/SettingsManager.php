<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class SettingsManager {

    private static $encryptionKey = 'your-encryption-key';

    public static function encrypt($data) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', self::$encryptionKey, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    public static function decrypt($data) {
        list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', self::$encryptionKey, 0, $iv);
    }

    public static function saveApiKey($key, $value) {
        $encrypted_value = self::encrypt($value);
        update_option($key, $encrypted_value);
    }

    public static function getApiKey($key) {
        $encrypted_value = get_option($key);
        return self::decrypt($encrypted_value);
    }
}
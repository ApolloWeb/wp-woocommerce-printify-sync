<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class EncryptionHelper {
    private $method = 'AES-256-CBC';
    private $key;

    public function __construct($key) {
        $this->key = hash('sha256', $key);
    }

    public function encrypt($data) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->method));
        $encrypted = openssl_encrypt($data, $this->method, $this->key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    public function decrypt($data) {
        list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
        return openssl_decrypt($encrypted_data, $this->method, $this->key, 0, $iv);
    }
}
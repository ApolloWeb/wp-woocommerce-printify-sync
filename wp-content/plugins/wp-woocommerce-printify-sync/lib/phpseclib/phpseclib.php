<?php
/**
 * Minimal phpseclib implementation for encryption
 */

namespace phpseclib3\Crypt;

class AES {
    private $key;
    private $iv;
    
    public function __construct($mode = 'cbc') {
        $this->iv = openssl_random_pseudo_bytes(16);
    }

    public function setKey($key) {
        $this->key = $key;
    }

    public function setIV($iv) {
        $this->iv = $iv;
    }

    public function encrypt($data) {
        return openssl_encrypt(
            $data,
            'AES-256-CBC',
            $this->key,
            OPENSSL_RAW_DATA,
            $this->iv
        );
    }

    public function decrypt($data) {
        return openssl_decrypt(
            $data,
            'AES-256-CBC',
            $this->key,
            OPENSSL_RAW_DATA,
            $this->iv
        );
    }
}

class Random {
    public static function string($length) {
        return bin2hex(random_bytes($length / 2));
    }
}
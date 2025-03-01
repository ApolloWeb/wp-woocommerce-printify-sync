<?php
/**
 * Encrypts and decrypts data.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Utilities;

class Encryption {

    private $key = 'your-encryption-key';

    public function encrypt($data) {
        return openssl_encrypt($data, 'AES-128-CBC', $this->key, 0, substr($this->key, 0, 16));
    }

    public function decrypt($data) {
        return openssl_decrypt($data, 'AES-128-CBC', $this->key, 0, substr($this->key, 0, 16));
    }
}
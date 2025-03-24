<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Traits;

use ApolloWeb\WPWooCommercePrintifySync\Services\EncryptionService;

trait HandlesEncryptedOptions {
    private ?EncryptionService $encryptionService = null;

    private function getEncryptionService(): EncryptionService {
        if ($this->encryptionService === null) {
            $this->encryptionService = new EncryptionService();
        }
        return $this->encryptionService;
    }

    protected function getDecryptedOption(string $key, string $default = ''): string {
        $value = get_option($key, $default);
        return $value ? $this->getEncryptionService()->decrypt($value) : $default;
    }
}

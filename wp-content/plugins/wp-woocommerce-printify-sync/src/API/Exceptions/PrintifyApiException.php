<?php

namespace ApolloWeb\WPWooCommercePrintifySync\API\Exceptions;

class PrintifyApiException extends \Exception {
    private array $errors;

    public function __construct(string $message, int $code = 0, array $errors = []) {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function getErrors(): array {
        return $this->errors;
    }
}

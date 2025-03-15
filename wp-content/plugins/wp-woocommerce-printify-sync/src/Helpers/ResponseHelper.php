<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class ResponseHelper
{
    public static function success(array $data = [], string $message = ''): array
    {
        return [
            'success' => true,
            'data' => $data,
            'message' => $message,
            'timestamp' => (new DateTimeHelper())->getCurrentTimestamp()
        ];
    }

    public static function error(string $message, int $code = 400): array
    {
        return [
            'success' => false,
            'message' => $message,
            'code' => $code,
            'timestamp' => (new DateTimeHelper())->getCurrentTimestamp()
        ];
    }
}
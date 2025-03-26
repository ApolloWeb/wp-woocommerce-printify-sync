<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

abstract class BaseAPIService {
    protected function checkRateLimit(string $key, int $window, int $max_requests): bool {
        $current_time = time();
        $rate_limit_data = get_transient($key) ?: [
            'window_start' => $current_time,
            'requests' => 0,
        ];

        // Reset window if expired
        if ($current_time - $rate_limit_data['window_start'] >= $window) {
            $rate_limit_data = [
                'window_start' => $current_time,
                'requests' => 0,
            ];
        }

        // Check if limit exceeded
        if ($rate_limit_data['requests'] >= $max_requests) {
            $wait_time = $window - ($current_time - $rate_limit_data['window_start']);
            throw new \Exception(
                sprintf(
                    __('Rate limit exceeded. Please wait %d seconds.', 'wp-woocommerce-printify-sync'),
                    $wait_time
                )
            );
        }

        // Update counter
        $rate_limit_data['requests']++;
        set_transient($key, $rate_limit_data, $window);

        return true;
    }

    protected function handleAPIError(\Exception $e, string $context): void {
        $message = $e->getMessage();
        $code = $e->getCode();
        
        // Log the error
        error_log(sprintf(
            '[%s] %s API Error: %s (Code: %s)',
            date('Y-m-d H:i:s'),
            $context,
            $message,
            $code
        ));

        // Sanitize error message for display
        $user_message = $this->sanitizeErrorMessage($message);
        
        throw new \Exception($user_message, $code);
    }

    protected function sanitizeErrorMessage(string $message): string {
        // Remove any sensitive data from error messages
        $message = preg_replace('/Bearer\s+[a-zA-Z0-9._\-]+/', 'Bearer [REDACTED]', $message);
        $message = preg_replace('/sk-[a-zA-Z0-9]{32,}/', '[REDACTED]', $message);
        return $message;
    }
}
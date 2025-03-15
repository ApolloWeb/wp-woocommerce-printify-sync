<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ResponseFormatter
{
    private string $currentTime;
    private string $currentUser;

    public function __construct()
    {
        $this->currentTime = '2025-03-15 18:20:18';
        $this->currentUser = 'ApolloWeb';
    }

    public function formatResponse(array $data): array
    {
        return [
            'data' => $this->flattenArray($data),
            'meta' => [
                'timestamp' => $this->currentTime,
                'user' => $this->currentUser,
                'version' => WPWPS_VERSION
            ]
        ];
    }

    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}_{$key}" : $key;

            if (is_array($value)) {
                // Handle nested arrays
                if ($this->isSequentialArray($value)) {
                    // For sequential arrays, append index to key
                    foreach ($value as $index => $item) {
                        if (is_array($item)) {
                            $result = array_merge(
                                $result,
                                $this->flattenArray($item, "{$newKey}_{$index}")
                            );
                        } else {
                            $result["{$newKey}_{$index}"] = $item;
                        }
                    }
                } else {
                    // For associative arrays, merge flattened results
                    $result = array_merge(
                        $result,
                        $this->flattenArray($value, $newKey)
                    );
                }
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    private function isSequentialArray(array $array): bool
    {
        return array_keys($array) === range(0, count($array) - 1);
    }
}
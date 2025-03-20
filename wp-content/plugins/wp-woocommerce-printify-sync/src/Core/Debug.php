<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

/**
 * Helper class for debugging
 */
class Debug
{
    /**
     * Dump information to the error log
     *
     * @param mixed $data Data to log
     * @param string $prefix Optional prefix for the log entry
     * @return void
     */
    public static function log($data, string $prefix = 'Debug: '): void
    {
        if (is_array($data) || is_object($data)) {
            error_log($prefix . print_r($data, true));
        } else {
            error_log($prefix . $data);
        }
    }
    
    /**
     * Log a request including headers and body
     * 
     * @param array $request WP HTTP API request
     * @return void
     */
    public static function logRequest(array $request): void
    {
        $info = [
            'url' => $request['url'] ?? 'Unknown URL',
            'method' => $request['method'] ?? 'GET',
            'headers' => $request['headers'] ?? [],
            'body' => isset($request['body']) ? substr($request['body'], 0, 500) . '...' : null
        ];
        
        self::log($info, 'HTTP Request: ');
    }
    
    /**
     * Add debug info to the AJAX response
     * 
     * @param array $response The response array
     * @param mixed $debugInfo Debug information to add
     * @return array Updated response array
     */
    public static function addDebugInfo(array $response, $debugInfo): array
    {
        if (!isset($response['debug_info'])) {
            $response['debug_info'] = [];
        }
        
        $response['debug_info'] = array_merge($response['debug_info'], 
            is_array($debugInfo) ? $debugInfo : ['info' => $debugInfo]
        );
        
        return $response;
    }
}

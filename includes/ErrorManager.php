<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class ErrorManager {

    public static function categorizeError($error) {
        // Logic to categorize error
    }

    public static function alertAdmin($error) {
        // Logic to alert admin
    }

    public static function handleError($error) {
        self::categorizeError($error);
        self::logError($error);
        self::alertAdmin($error);
    }

    public static function logError($error) {
        Logger::log($error, 'error');
    }
}
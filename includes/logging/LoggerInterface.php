<?php
/**
 * Logger Interface
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Logging
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Logging;

interface LoggerInterface {
    public function debug($message, array $context = []);
    public function info($message, array $context = []);
    public function notice($message, array $context = []);
    public function warning($message, array $context = []);
    public function error($message, array $context = []);
    public function critical($message, array $context = []);
    public function alert($message, array $context = []);
    public function emergency($message, array $context = []);
}
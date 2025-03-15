<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Logging;

class FileLogger extends AbstractLogger
{
    private string $logDir;
    private string $logFile;

    public function __construct()
    {
        $uploadDir = wp_upload_dir();
        $this->logDir = $uploadDir['basedir'] . '/wpwps-logs';
        $this->logFile = $this->logDir . '/wpwps-' . date('Y-m-d') . '.log';
        
        $this->ensureLogDirectoryExists();
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log('EMERGENCY', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log('ALERT', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('NOTICE', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    private function log(string $level, string $message, array $context = []): void
    {
        $logMessage = $this->formatMessage($level, $message, $context) . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    private function ensureLogDirectoryExists(): void
    {
        if (!file_exists($this->logDir)) {
            wp_mkdir_p($this->logDir);
            file_put_contents($this->logDir . '/.htaccess', 'Deny from all');
            file_put_contents($this->logDir . '/index.php', '<?php // Silence is golden');
        }
    }
}
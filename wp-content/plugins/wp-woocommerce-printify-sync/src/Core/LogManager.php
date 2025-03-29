<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class LogManager
{
    private string $logDir;
    private string $currentLogFile;
    private int $maxLogFiles;
    private int $maxLogSize;

    public function __construct()
    {
        $this->logDir = WPWPS_PATH . 'logs';
        $this->currentLogFile = $this->logDir . '/wpwps-' . date('Y-m-d') . '.log';
        $this->maxLogFiles = 30; // Keep logs for 30 days
        $this->maxLogSize = 10 * 1024 * 1024; // 10MB

        if (!is_dir($this->logDir)) {
            wp_mkdir_p($this->logDir);
        }

        $this->cleanOldLogs();
    }

    public function log(string $message, string $level = 'info', array $context = []): void
    {
        if (!is_writable($this->logDir)) {
            return;
        }

        $this->rotateLogIfNeeded();

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;

        file_put_contents($this->currentLogFile, $logMessage, FILE_APPEND);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log($message, 'INFO', $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log($message, 'ERROR', $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log($message, 'WARNING', $context);
    }

    public function debug(string $message, array $context = []): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->log($message, 'DEBUG', $context);
        }
    }

    public function getLogFiles(): array
    {
        if (!is_dir($this->logDir)) {
            return [];
        }

        $files = glob($this->logDir . '/wpwps-*.log');
        if (!$files) {
            return [];
        }

        $logFiles = [];
        foreach ($files as $file) {
            $logFiles[] = [
                'name' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'modified' => filemtime($file)
            ];
        }

        usort($logFiles, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });

        return $logFiles;
    }

    public function getLogContent(string $filename): ?string
    {
        $filePath = $this->logDir . '/' . basename($filename);
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }

        return file_get_contents($filePath);
    }

    private function rotateLogIfNeeded(): void
    {
        if (!file_exists($this->currentLogFile)) {
            return;
        }

        if (filesize($this->currentLogFile) < $this->maxLogSize) {
            return;
        }

        $rotatedFile = $this->currentLogFile . '.' . time();
        rename($this->currentLogFile, $rotatedFile);
    }

    private function cleanOldLogs(): void
    {
        $files = $this->getLogFiles();
        $count = count($files);

        if ($count <= $this->maxLogFiles) {
            return;
        }

        for ($i = $this->maxLogFiles; $i < $count; $i++) {
            if (isset($files[$i]['path'])) {
                unlink($files[$i]['path']);
            }
        }
    }
}
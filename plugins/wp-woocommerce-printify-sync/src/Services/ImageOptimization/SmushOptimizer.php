<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\ImageOptimization;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;

class SmushOptimizer implements ImageOptimizerInterface
{
    private LoggerInterface $logger;
    private bool $smushActive;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->smushActive = $this->isSmushActive();
    }

    private function isSmushActive(): bool
    {
        return class_exists('\WP_Smush') && class_exists('\Smush\Core\Core');
    }

    public function optimize(string $sourcePath): ?string
    {
        if (!$this->smushActive) {
            $this->logger->warning('Smush is not active, skipping optimization');
            return $sourcePath;
        }

        try {
            global $WP_Smush;
            
            // Get Smush core instance
            $smushCore = \Smush\Core\Core::get_instance();
            
            // Optimize image
            $status = $smushCore->mod->smush->optimize($sourcePath);
            
            if (is_wp_error($status)) {
                throw new \Exception($status->get_error_message());
            }

            return $sourcePath;
        } catch (\Exception $e) {
            $this->logger->error('Smush optimization failed', [
                'error' => $e->getMessage(),
                'path' => $sourcePath
            ]);
            return null;
        }
    }

    public function generateWebP(string $sourcePath): ?string
    {
        if (!$this->smushActive || !$this->supportsWebP()) {
            $this->logger->warning('WebP conversion not available');
            return null;
        }

        try {
            global $WP_Smush;
            
            // Get WebP instance
            $webp = $WP_Smush->core()->mod->webp;
            
            // Generate WebP
            $webpPath = $webp->convert_to_webp($sourcePath);
            
            if (is_wp_error($webpPath)) {
                throw new \Exception($webpPath->get_error_message());
            }

            return $webpPath;
        } catch (\Exception $e) {
            $this->logger->error('WebP conversion failed', [
                'error' => $e->getMessage(),
                'path' => $sourcePath
            ]);
            return null;
        }
    }

    public function supportsWebP(): bool
    {
        if (!$this->smushActive) {
            return false;
        }

        global $WP_Smush;
        return isset($WP_Smush->core()->mod->webp);
    }

    public function getOptimizedSize(string $path): ?int
    {
        if (!file_exists($path)) {
            return null;
        }
        return filesize($path);
    }
}
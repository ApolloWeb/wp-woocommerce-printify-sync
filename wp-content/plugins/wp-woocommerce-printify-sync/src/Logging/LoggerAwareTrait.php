<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Logging;

trait LoggerAwareTrait
{
    protected LoggerInterface $logger;

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    protected function getLogger(): LoggerInterface
    {
        if (!isset($this->logger)) {
            $this->logger = LoggerFactory::create();
        }
        return $this->logger;
    }

    protected function log(string $level, string $message, array $context = []): void
    {
        $context['component'] = $context['component'] ?? get_class($this);
        $this->getLogger()->$level($message, $context);
    }
}
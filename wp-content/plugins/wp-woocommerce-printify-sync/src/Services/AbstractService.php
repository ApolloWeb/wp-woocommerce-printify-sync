<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

abstract class AbstractService
{
    use TimeStampTrait;

    protected LoggerInterface $logger;
    protected ConfigService $config;

    public function __construct(LoggerInterface $logger, ConfigService $config)
    {
        $this->logger = $logger;
        $this->config = $config;
    }

    protected function logOperation(string $operation, array $context = []): void
    {
        $this->logger->info(
            sprintf('%s::%s - %s', static::class, $operation, $context['message'] ?? ''),
            $this->addTimeStampData($context)
        );
    }

    protected function logError(string $operation, \Throwable $error, array $context = []): void
    {
        $this->logger->error(
            sprintf('%s::%s - %s', static::class, $operation, $error->getMessage()),
            $this->addTimeStampData(array_merge($context, [
                'error_code' => $error->getCode(),
                'error_file' => $error->getFile(),
                'error_line' => $error->getLine(),
                'error_trace' => $error->getTraceAsString()
            ]))
        );
    }
}
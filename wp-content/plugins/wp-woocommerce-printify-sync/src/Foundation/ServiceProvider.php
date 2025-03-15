<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Foundation;

use ApolloWeb\WPWooCommercePrintifySync\Contracts\{
    ImageHandlerInterface,
    ProductValidatorInterface,
    ChangeTrackerInterface
};
use ApolloWeb\WPWooCommercePrintifySync\Services\{
    ImageHandler,
    ProductValidator,
    ChangeTracker
};
use ApolloWeb\WPWooCommercePrintifySync\Logging\{
    LoggerInterface,
    DatabaseLogger
};

class ServiceProvider
{
    public function __construct(private Container $container)
    {
        $this->registerServices();
    }

    private function registerServices(): void
    {
        // Register time and user providers as singletons
        $this->container->singleton(TimeProvider::class);
        $this->container->singleton(UserProvider::class);

        // Register logger
        $this->container->singleton(LoggerInterface::class, function ($container) {
            return new DatabaseLogger(
                $container->make(TimeProvider::class),
                $container->make(UserProvider::class)
            );
        });

        // Register services
        $this->container->bind(ImageHandlerInterface::class, ImageHandler::class);
        $this->container->bind(ProductValidatorInterface::class, ProductValidator::class);
        $this->container->bind(ChangeTrackerInterface::class, ChangeTracker::class);

        // Register other services that might need TimeProvider and UserProvider
        $this->container->singleton(WebhookHandler::class);
        $this->container->singleton(ProductImporter::class);
    }
}
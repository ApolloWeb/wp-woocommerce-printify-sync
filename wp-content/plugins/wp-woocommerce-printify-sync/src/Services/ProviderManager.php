<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Entities\PrintProvider;
use ApolloWeb\WPWooCommercePrintifySync\Foundation\{TimeProvider, UserProvider};
use ApolloWeb\WPWooCommercePrintifySync\Logging\LoggerAwareTrait;

class ProviderManager extends BaseService
{
    use LoggerAwareTrait;

    public function __construct(
        TimeProvider $timeProvider,
        UserProvider $userProvider
    ) {
        parent::__construct($timeProvider, $userProvider);
    }

    public function getProvider(int $providerId): ?PrintProvider
    {
        $providerData = get_option("wpwps_provider_{$providerId}");
        
        if (!$providerData) {
            $this->log('warning', 'Provider not found', [
                'provider_id' => $providerId
            ]);
            return null;
        }

        return new PrintProvider($providerData);
    }

    public function updateProvider(PrintProvider $provider): void
    {
        update_option(
            "wpwps_provider_{$provider->getId()}", 
            [
                'id' => $provider->getId(),
                'name' => $provider->getName(),
                'location' => $provider->getLocation(),
                'supported_products' => $provider->getSupportedProducts(),
                'shipping_info' => $provider->getShippingInfo(),
                'is_active' => $provider->isActive(),
                'last_updated' => $this->getCurrentTime(),
                'updated_by' => $this->getCurrentUser()
            ]
        );

        $this->log('info', 'Provider updated', [
            'provider_id' => $provider->getId(),
            'provider_name' => $provider->getName()
        ]);
    }

    public function validateProviderForProduct(int $providerId, string $blueprintId): bool
    {
        $provider = $this->getProvider($providerId);
        
        if (!$provider) {
            return false;
        }

        return in_array($blueprintId, $provider->getSupportedProducts());
    }
}
// Add to class properties
private GeolocationService $geolocation;
private CurrencyConverter $currency;

// Add to constructor
public function __construct(
    PrintifyAPI $api,
    GeolocationService $geolocation,
    CurrencyConverter $currency,
    LoggerInterface $logger,
    ConfigService $config
) {
    parent::__construct($logger, $config);
    $this->api = $api;
    $this->geolocation = $geolocation;
    $this->currency = $currency;
}

// Update calculateShipping method
public function calculateShipping(
    array $package,
    string $providerId,
    string $methodId
): ?array {
    try {
        $items = $this->groupItemsByProvider($package['contents']);
        
        if (!isset($items[$providerId])) {
            return null;
        }

        $settings = $this->getMethodSettings($methodId);
        if (!$settings) {
            return null;
        }

        // Get user's location
        $location = $this->geolocation->getUserLocation();
        
        // Calculate base costs in USD
        $itemCount = array_sum(array_column($items[$providerId], 'quantity'));
        $costUSD = $settings['first_item_cost'];
        
        if ($itemCount > 1) {
            $costUSD += ($itemCount - 1) * $settings['additional_item_cost'];
        }

        // Convert to store currency
        $cost = $this->currency->convert($costUSD);

        return [
            'cost' => $cost,
            'delivery_time' => $settings['delivery_time'],
            'provider_name' => $this->getProviderName($providerId),
            'formatted_cost' => $this->currency->formatPrice($cost)
        ];

    } catch (\Exception $e) {
        $this->logError('calculateShipping', $e, [
            'provider_id' => $providerId,
            'method_id' => $methodId
        ]);
        return null;
    }
}
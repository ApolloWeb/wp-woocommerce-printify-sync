// ...existing code...

public function getOrders(string $shopId, int $page = 1, int $perPage = 10): array
{
    // Ensure we don't exceed API maximum limit
    $perPage = min($perPage, 50); // Maximum allowed by Printify API is 50
    
    $queryParams = [
        'limit' => $perPage,
        'page' => $page
    ];
    
    // ...existing code...
}

public function getCachedOrders(string $shopId, bool $useCache = true, int $cacheExpiration = 3600): array
{
    // ...existing code...

    $allOrders = [];
    $page = 1;
    $perPage = 50; // Changed from 10 to 50 to maximize API efficiency

    // ...existing code...
}

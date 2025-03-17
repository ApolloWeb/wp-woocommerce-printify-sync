<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ProductSyncService {
    private PrintifyAPIClient $api;
    private ImageHandler $imageHandler;
    private CacheManager $cache;
    private LogManager $logger;
    
    public function syncProduct(int $printifyId): void {
        try {
            $product = $this->api->getProduct($printifyId);
            
            if ($existing = $this->findExistingProduct($printifyId)) {
                $this->updateProduct($existing, $product);
            } else {
                $this->createProduct($product);
            }
            
            $this->syncImages($product);
            $this->updateVariants($product);
            
        } catch (\Exception $e) {
            $this->logger->error('Product sync failed', [
                'printify_id' => $printifyId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    private function syncImages(array $product): void {
        foreach ($product['images'] as $image) {
            $this->imageHandler->processAndAttach($image, $product['id']);
        }
    }
}
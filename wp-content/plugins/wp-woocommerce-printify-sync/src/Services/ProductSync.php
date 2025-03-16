<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\ApiClientInterface;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Repositories\ProductRepository;

class ProductSync
{
    private ApiClientInterface $apiClient;
    private LoggerInterface $logger;
    private ProductRepository $productRepo;
    private ImageProcessor $imageProcessor;

    public function __construct(
        ApiClientInterface $apiClient,
        LoggerInterface $logger,
        ProductRepository $productRepo,
        ImageProcessor $imageProcessor
    ) {
        $this->apiClient = $apiClient;
        $this->logger = $logger;
        $this->productRepo = $productRepo;
        $this->imageProcessor = $imageProcessor;
    }

    public function importProduct(string $printifyId): int
    {
        try {
            // Fetch product data from Printify
            $printifyData = $this->apiClient->getProduct($printifyId);
            if (is_wp_error($printifyData)) {
                throw new \Exception($printifyData->get_error_message());
            }

            // Check if product already exists
            $product = $this->productRepo->getProductByPrintifyId($printifyId);
            $isNew = !$product;

            if ($isNew) {
                $product = new \WC_Product();
            }

            // Update basic product data
            $product->set_name($printifyData['title']);
            $product->set_description($printifyData['description']);
            $product->set_short_description($printifyData['description']);
            $product->set_status('publish');
            $product->set_catalog_visibility('visible');
            $product->set_regular_price((string)$printifyData['price']);
            $product->set_sku($printifyData['sku']);

            // Save product to get ID if new
            $product->save();

            // Process and attach images
            if (!empty($printifyData['images'])) {
                $attachmentIds = $this->imageProcessor->processProductImages(
                    $printifyData['images'],
                    $product->get_id()
                );

                if (!empty($attachmentIds)) {
                    $product->set_image_id($attachmentIds[0]);
                    if (count($attachmentIds) > 1) {
                        $product->set_gallery_image_ids(array_slice($attachmentIds, 1));
                    }
                }
            }

            // Update Printify metadata
            $this->productRepo->updateProductMeta($product, $printifyData);
            
            // Save all changes
            $product->save();

            // Log the sync
            $this->productRepo->logProductSync(
                $product->get_id(),
                $printifyData,
                $isNew ? 'import' : 'update'
            );

            return $product->get_id();

        } catch (\Exception $e) {
            $this->logger->error('Product import failed', [
                'printify_id' => $printifyId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
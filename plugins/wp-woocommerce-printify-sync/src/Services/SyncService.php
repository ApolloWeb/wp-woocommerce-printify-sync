<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\PrintifyClientInterface;
use ApolloWeb\WPWooCommercePrintifySync\DataTransferObjects\PrintifyProductData;
use ApolloWeb\WPWooCommercePrintifySync\Repositories\{
    ProductRepository,
    VariantRepository,
    ImageRepository
};
use ApolloWeb\WPWooCommercePrintifySync\Services\Helpers\{
    CategoryHelper,
    TagHelper,
    AttributeHelper,
    ImageHelper
};

class SyncService
{
    private const BATCH_SIZE = 10;

    private PrintifyClientInterface $client;
    private LoggerInterface $logger;
    private ProductRepository $productRepo;
    private VariantRepository $variantRepo;
    private ImageRepository $imageRepo;
    private CategoryHelper $categoryHelper;
    private TagHelper $tagHelper;
    private AttributeHelper $attributeHelper;
    private ImageHelper $imageHelper;
    private string $currentTime;
    private string $currentUser;

    public function __construct(
        PrintifyClientInterface $client,
        LoggerInterface $logger,
        ProductRepository $productRepo,
        VariantRepository $variantRepo,
        ImageRepository $imageRepo,
        CategoryHelper $categoryHelper,
        TagHelper $tagHelper,
        AttributeHelper $attributeHelper,
        ImageHelper $imageHelper,
        string $currentTime = '2025-03-15 21:25:19',
        string $currentUser = 'ApolloWeb'
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->productRepo = $productRepo;
        $this->variantRepo = $variantRepo;
        $this->imageRepo = $imageRepo;
        $this->categoryHelper = $categoryHelper;
        $this->tagHelper = $tagHelper;
        $this->attributeHelper = $attributeHelper;
        $this->imageHelper = $imageHelper;
        $this->currentTime = $currentTime;
        $this->currentUser = $currentUser;
    }

    public function scheduleFullSync(string $shopId): void
    {
        try {
            $products = $this->client->getAllProducts($shopId);
            $chunks = array_chunk($products, self::BATCH_SIZE);

            foreach ($chunks as $index => $chunk) {
                as_schedule_single_action(
                    strtotime("+{$index} minutes", strtotime($this->currentTime)),
                    'wpwps_process_product_chunk',
                    [
                        'products' => array_column($chunk, 'id'),
                        'shop_id' => $shopId,
                        'sync_id' => uniqid('sync_', true),
                        'timestamp' => $this->currentTime,
                        'user' => $this->currentUser
                    ],
                    'wpwps_product_sync'
                );
            }

            $this->logger->info('Full sync scheduled', [
                'shop_id' => $shopId,
                'total_products' => count($products),
                'chunks' => count($chunks)
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to schedule full sync', [
                'shop_id' => $shopId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function processProductChunk(array $productIds, string $shopId, string $syncId): void
    {
        foreach ($productIds as $printifyId) {
            try {
                $this->syncProduct($printifyId, $shopId, $syncId);
            } catch (\Exception $e) {
                $this->logger->error('Failed to sync product', [
                    'printify_id' => $printifyId,
                    'sync_id' => $syncId,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    public function syncProduct(string $printifyId, string $shopId, string $syncId): void
    {
        // Get product data from Printify
        $printifyData = $this->client->getProduct($printifyId);
        $dto = PrintifyProductData::fromArray($printifyData);

        // Get or create WooCommerce product
        $product = $this->productRepo->getByPrintifyId($printifyId) 
            ?? new \WC_Product_Variable();

        $isNew = $product->get_id() === 0;

        // Basic product data
        $product->set_name($dto->title);
        $product->set_description($dto->description);
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_regular_price((string)$dto->retailPrice);

        // Save to get ID if new
        if ($isNew) {
            $product->save();
        }

        // Process categories and tags
        $this->categoryHelper->syncCategories($product, $dto->categories);
        $this->tagHelper->syncTags($product, $dto->tags);

        // Process attributes and variations
        $this->attributeHelper->syncAttributes($product, $dto->variants);
        $this->variantRepo->syncVariants($product, $dto);

        // Process images only if changed
        $attachmentIds = $this->imageHelper->syncImages($product, $dto->images);
        if (!empty($attachmentIds)) {
            $product->set_image_id($attachmentIds[0]);
            if (count($attachmentIds) > 1) {
                $product->set_gallery_image_ids(array_slice($attachmentIds, 1));
            }
        }

        // Update all metadata
        $this->productRepo->updateMetadata($product, $dto);

        // Final save
        $product->save();

        // Log sync
        $this->productRepo->logSync($product->get_id(), $dto, $syncId, $isNew ? 'import' : 'update');

        $this->logger->info('Product synced', [
            'product_id' => $product->get_id(),
            'printify_id' => $printifyId,
            'sync_id' => $syncId,
            'is_new' => $isNew
        ]);
    }

    public function handleWebhook(array $payload): void
    {
        $printifyId = $payload['product_id'] ?? null;
        $shopId = $payload['shop_id'] ?? null;
        $event = $payload['event'] ?? '';

        if (!$printifyId || !$shopId) {
            throw new \InvalidArgumentException('Invalid webhook payload');
        }

        as_schedule_single_action(
            strtotime($this->currentTime),
            'wpwps_process_webhook_update',
            [
                'printify_id' => $printifyId,
                'shop_id' => $shopId,
                'event' => $event,
                'sync_id' => uniqid('webhook_', true),
                'timestamp' => $this->currentTime,
                'user' => $this->currentUser
            ],
            'wpwps_webhook_updates'
        );
    }
}
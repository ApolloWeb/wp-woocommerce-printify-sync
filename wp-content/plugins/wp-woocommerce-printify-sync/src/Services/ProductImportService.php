<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ProductImportService
{
    private string $currentTime = '2025-03-15 19:13:19';
    private string $currentUser = 'ApolloWeb';
    private PrintifyApi $printifyApi;
    private MediaLibraryService $mediaLibraryService;

    public function __construct()
    {
        $this->printifyApi = new PrintifyApi(
            get_option('wpwps_printify_api_key'),
            get_option('wpwps_printify_endpoint')
        );
        $this->mediaLibraryService = new MediaLibraryService();
    }

    // ... other methods remain the same ...

    private function handleProductImages(int $productId, array $printifyImages): void
    {
        try {
            // Import images through Media Library
            $importedImages = $this->mediaLibraryService->importPrintifyImages($printifyImages, $productId);

            if (!empty($importedImages)) {
                // First image is already set as featured by MediaLibraryService
                // Set remaining images as gallery
                if (count($importedImages) > 1) {
                    $product = wc_get_product($productId);
                    $product->set_gallery_image_ids(array_slice($importedImages, 1));
                    $product->save();
                }
            }

        } catch (\Exception $e) {
            error_log("Failed to handle product images: " . $e->getMessage());
        }
    }
}
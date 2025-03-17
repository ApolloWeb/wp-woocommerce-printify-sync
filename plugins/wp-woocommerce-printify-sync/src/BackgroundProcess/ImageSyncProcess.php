<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\BackgroundProcess;

use ApolloWeb\WPWooCommercePrintifySync\Service\ImageService;

class ImageSyncProcess extends AbstractBackgroundProcess
{
    protected $action = 'wpwps_image_sync';
    private ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        parent::__construct();
        $this->imageService = $imageService;
    }

    protected function process_item($item)
    {
        try {
            $this->imageService->syncProductImages($item['product_id']);
            return false;
        } catch (\Exception $e) {
            if ($item['attempts'] < 3) {
                $item['attempts']++;
                return $item;
            }
            return false;
        }
    }
}
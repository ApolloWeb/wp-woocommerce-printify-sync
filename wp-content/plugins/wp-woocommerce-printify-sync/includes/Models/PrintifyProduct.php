<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Models;

class PrintifyProduct {
    private $data;
    private $wc_product_id;

    public function __construct(array $data) {
        $this->data = $data;
    }

    public function getId(): string {
        return $this->data['id'];
    }

    public function getTitle(): string {
        return $this->data['title'];
    }

    public function getVariants(): array {
        return $this->data['variants'] ?? [];
    }

    public function mapToWooCommerce(): array {
        return [
            'post_title' => $this->getTitle(),
            'post_content' => $this->data['description'],
            'post_status' => 'publish',
            'post_type' => 'product',
            'meta_input' => [
                '_printify_product_id' => $this->getId(),
                '_printify_provider_id' => $this->data['provider_id'],
                '_printify_last_synced' => current_time('mysql')
            ]
        ];
    }
}

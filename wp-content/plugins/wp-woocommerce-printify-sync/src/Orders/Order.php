<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Orders;

class Order
{
    private int $id;
    private string $printifyId;
    private string $status;
    private array $lineItems;
    private array $shipping;
    private string $trackingNumber;
    private string $trackingUrl;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? 0;
        $this->printifyId = $data['printify_id'] ?? '';
        $this->status = $data['status'] ?? 'pending';
        $this->lineItems = $data['line_items'] ?? [];
        $this->shipping = $data['shipping'] ?? [];
        $this->trackingNumber = $data['tracking_number'] ?? '';
        $this->trackingUrl = $data['tracking_url'] ?? '';
    }

    public function toPrintify(): array
    {
        return [
            'external_id' => $this->id,
            'line_items' => array_map(function($item) {
                return [
                    'product_id' => get_post_meta($item['product_id'], '_printify_id', true),
                    'variant_id' => get_post_meta($item['variation_id'] ?: $item['product_id'], '_printify_variant_id', true),
                    'quantity' => $item['quantity']
                ];
            }, $this->lineItems),
            'shipping_address' => [
                'first_name' => $this->shipping['first_name'] ?? '',
                'last_name' => $this->shipping['last_name'] ?? '',
                'address1' => $this->shipping['address_1'] ?? '',
                'address2' => $this->shipping['address_2'] ?? '',
                'city' => $this->shipping['city'] ?? '',
                'state' => $this->shipping['state'] ?? '',
                'zip' => $this->shipping['postcode'] ?? '',
                'country' => $this->shipping['country'] ?? '',
                'phone' => $this->shipping['phone'] ?? '',
                'email' => $this->shipping['email'] ?? ''
            ]
        ];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPrintifyId(): string
    {
        return $this->printifyId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    public function getShipping(): array
    {
        return $this->shipping;
    }

    public function getTrackingNumber(): string
    {
        return $this->trackingNumber;
    }

    public function getTrackingUrl(): string
    {
        return $this->trackingUrl;
    }
}
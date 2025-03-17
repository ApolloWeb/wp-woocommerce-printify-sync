<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;

class OrderStatusManager extends AbstractService
{
    // Printify order statuses
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PRODUCTION = 'in_production';
    public const STATUS_ON_HOLD = 'on_hold';
    public const STATUS_SHIPPING = 'shipping';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_CANCELLED = 'cancelled';

    // Custom WooCommerce order statuses
    private const WC_STATUS_PREFIX = 'wc-';
    private const CUSTOM_STATUSES = [
        'printify-draft' => 'Draft at Printify',
        'printify-pending' => 'Pending at Printify',
        'printify-production' => 'In Production',
        'printify-on-hold' => 'On Hold at Printify',
        'printify-shipping' => 'Shipping from Printify',
        'printify-shipped' => 'Shipped from Printify'
    ];

    public function __construct(LoggerInterface $logger, ConfigService $config)
    {
        parent::__construct($logger, $config);
        $this->registerCustomOrderStatuses();
    }

    public function registerCustomOrderStatuses(): void
    {
        foreach (self::CUSTOM_STATUSES as $status => $label) {
            register_post_status(self::WC_STATUS_PREFIX . $status, [
                'label' => $label,
                'public' => true,
                'show_in_admin_status_list' => true,
                'show_in_admin_all_list' => true,
                'exclude_from_search' => false,
                'label_count' => _n_noop(
                    $label . ' <span class="count">(%s)</span>',
                    $label . ' <span class="count">(%s)</span>'
                )
            ]);
        }

        add_filter('wc_order_statuses', [$this, 'addCustomOrderStatuses']);
    }

    public function addCustomOrderStatuses(array $orderStatuses): array
    {
        foreach (self::CUSTOM_STATUSES as $status => $label) {
            $orderStatuses[self::WC_STATUS_PREFIX . $status] = $label;
        }
        return $orderStatuses;
    }

    public function updateOrderStatus(\WC_Order $order, string $printifyStatus): void
    {
        try {
            $newStatus = $this->mapPrintifyToWooCommerceStatus($printifyStatus);
            $currentStatus = $order->get_status();

            if ($newStatus !== $currentStatus) {
                $order->update_status(
                    $newStatus,
                    sprintf(
                        __('Order status updated from Printify: %s', 'wp-woocommerce-printify-sync'),
                        self::CUSTOM_STATUSES[$newStatus] ?? $printifyStatus
                    )
                );

                // Update last sync timestamp
                $order->update_meta_data('_printify_last_sync', $this->getCurrentTime());
                $order->save();

                $this->logOperation('updateOrderStatus', [
                    'order_id' => $order->get_id(),
                    'old_status' => $currentStatus,
                    'new_status' => $newStatus,
                    'printify_status' => $printifyStatus
                ]);
            }
        } catch (\Exception $e) {
            $this->logError('updateOrderStatus', $e, [
                'order_id' => $order->get_id(),
                'printify_status' => $printifyStatus
            ]);
        }
    }

    public function mapPrintifyToWooCommerceStatus(string $printifyStatus): string
    {
        return match ($printifyStatus) {
            self::STATUS_DRAFT => 'printify-draft',
            self::STATUS_PENDING => 'printify-pending',
            self::STATUS_IN_PRODUCTION => 'printify-production',
            self::STATUS_ON_HOLD => 'printify-on-hold',
            self::STATUS_SHIPPING => 'printify-shipping',
            self::STATUS_SHIPPED => 'printify-shipped',
            self::STATUS_CANCELLED => 'cancelled',
            default => 'pending'
        };
    }

    public function mapWooCommerceToPrintifyStatus(string $wcStatus): string
    {
        return match ($wcStatus) {
            'printify-draft' => self::STATUS_DRAFT,
            'printify-pending' => self::STATUS_PENDING,
            'printify-production' => self::STATUS_IN_PRODUCTION,
            'printify-on-hold' => self::STATUS_ON_HOLD,
            'printify-shipping' => self::STATUS_SHIPPING,
            'printify-shipped' => self::STATUS_SHIPPED,
            'cancelled', 'refunded', 'failed' => self::STATUS_CANCELLED,
            default => self::STATUS_PENDING
        };
    }

    public function addOrderStatusColumn($columns): array
    {
        $new_columns = [];
        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
            if ($key === 'order_status') {
                $new_columns['printify_status'] = __('Printify Status', 'wp-woocommerce-printify-sync');
            }
        }
        return $new_columns;
    }

    public function displayPrintifyStatus($column, $post_id): void
    {
        if ($column === 'printify_status') {
            $order = wc_get_order($post_id);
            if (!$order) return;

            $printifyId = $order->get_meta('_printify_order_id');
            if (!$printifyId) {
                echo '<span class="printify-status na">' . 
                     __('Not a Printify order', 'wp-woocommerce-printify-sync') . 
                     '</span>';
                return;
            }

            $status = $order->get_meta('_printify_status');
            $lastSync = $order->get_meta('_printify_last_sync');

            echo '<div class="printify-status-wrapper">';
            echo '<span class="printify-status ' . esc_attr($status) . '">' . 
                 esc_html(self::CUSTOM_STATUSES["printify-$status"] ?? $status) . 
                 '</span>';
            if ($lastSync) {
                echo '<br><small>' . 
                     sprintf(
                         __('Last synced: %s', 'wp-woocommerce-printify-sync'),
                         human_time_diff(strtotime($lastSync), time()) . ' ago'
                     ) . 
                     '</small>';
            }
            echo '</div>';
        }
    }
}
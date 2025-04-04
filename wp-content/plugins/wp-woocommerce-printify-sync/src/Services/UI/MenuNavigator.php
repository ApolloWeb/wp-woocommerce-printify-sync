<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services\UI;

use ApolloWeb\WPWooCommercePrintifySync\Contracts\NavigatorInterface;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\TicketManagerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Contracts\EmailQueueInterface;

class MenuNavigator implements NavigatorInterface {
    
    private TicketManagerInterface $ticketManager;
    private EmailQueueInterface $emailQueue;
    
    public function __construct(
        TicketManagerInterface $ticketManager,
        EmailQueueInterface $emailQueue
    ) {
        $this->ticketManager = $ticketManager;
        $this->emailQueue = $emailQueue;
    }

    public function getNavigation(): array {
        return [
            'main' => [
                $this->getDashboardItem(),
                $this->getProductsItem(),
                $this->getOrdersItem(),
                $this->getTicketsItem(),
                $this->getEmailQueueItem()
            ],
            'secondary' => $this->getSecondaryItems()
        ];
    }

    private function getDashboardItem(): array {
        return [
            'title' => __('Dashboard', 'wp-woocommerce-printify-sync'),
            'icon' => 'fa-home',
            'url' => admin_url('admin.php?page=wpwps-dashboard')
        ];
    }

    private function getProductsItem(): array {
        return [
            'title' => __('Products', 'wp-woocommerce-printify-sync'),
            'icon' => 'fa-box',
            'url' => admin_url('admin.php?page=wpwps-products')
        ];
    }

    private function getOrdersItem(): array {
        return [
            'title' => __('Orders', 'wp-woocommerce-printify-sync'),
            'icon' => 'fa-shopping-cart',
            'url' => admin_url('admin.php?page=wpwps-orders')
        ];
    }

    private function getTicketsItem(): array {
        return [
            'title' => __('Support Tickets', 'wp-woocommerce-printify-sync'),
            'icon' => 'fa-ticket-alt', 
            'url' => admin_url('edit.php?post_type=wpwps_support_ticket'),
            'badge' => $this->ticketManager->getPendingCount()
        ];
    }

    private function getEmailQueueItem(): array {
        return [
            'title' => __('Email Queue', 'wp-woocommerce-printify-sync'),
            'icon' => 'fa-envelope',
            'url' => admin_url('admin.php?page=wpwps-email-queue'),
            'badge' => $this->emailQueue->getQueuedCount()
        ];
    }

    private function getSecondaryItems(): array {
        return [
            [
                'title' => __('Settings', 'wp-woocommerce-printify-sync'),
                'icon' => 'fa-cogs',
                'url' => admin_url('admin.php?page=wpwps-settings')
            ],
            [
                'title' => __('Help', 'wp-woocommerce-printify-sync'),
                'icon' => 'fa-question-circle',
                'url' => admin_url('admin.php?page=wpwps-help')
            ]
        ];
    }
}

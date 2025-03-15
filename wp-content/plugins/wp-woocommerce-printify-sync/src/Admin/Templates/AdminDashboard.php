<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Templates;

class AdminDashboard
{
    private string $currentTime = '2025-03-15 20:27:27';
    private string $currentUser = 'ApolloWeb';

    public function render(): string
    {
        return <<<HTML
        <div class="wrap wpwps-dashboard">
            <h1 class="wp-heading-inline">
                <i class="fas fa-tachometer-alt"></i> 
                Printify Sync Dashboard
            </h1>
            
            <div class="row">
                <div class="col-md-3">
                    {$this->renderStatCard('Orders Today', $this->getOrdersCount(), 'fas fa-shopping-cart')}
                </div>
                <div class="col-md-3">
                    {$this->renderStatCard('Pending Tickets', $this->getTicketsCount(), 'fas fa-ticket-alt')}
                </div>
                <div class="col-md-3">
                    {$this->renderStatCard('Products Synced', $this->getProductsCount(), 'fas fa-sync')}
                </div>
                <div class="col-md-3">
                    {$this->renderAPIStatus()}
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    {$this->renderRecentOrders()}
                </div>
                <div class="col-md-6">
                    {$this->renderRecentTickets()}
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    {$this->renderSystemStatus()}
                </div>
            </div>
        </div>
        HTML;
    }

    private function renderStatCard(string $title, string $value, string $icon): string
    {
        return <<<HTML
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3 class="card-title">$title</h3>
                        <p class="card-text">$value</p>
                    </div>
                    <div class="card-icon">
                        <i class="$icon"></i>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }
}
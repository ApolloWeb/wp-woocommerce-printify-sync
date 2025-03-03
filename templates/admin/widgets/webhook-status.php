<?php
/**
 * Webhook Status Widget
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets
 */
defined('ABSPATH') || exit;
?>

<div class="data-card">
    <div class="card-header">
        <h3>Webhook Status</h3>
        <div class="card-actions">
            <button class="btn-transparent"><i class="fas fa-redo-alt"></i></button>
        </div>
    </div>
    <div class="card-body">
        <div class="webhook-status">
            <div class="webhook-item">
                <div class="webhook-name">Product Updates</div>
                <div class="webhook-indicator active"></div>
            </div>
            <div class="webhook-item">
                <div class="webhook-name">Order Status</div>
                <div class="webhook-indicator active"></div>
            </div>
            <div class="webhook-item">
                <div class="webhook-name">Inventory Changes</div>
                <div class="webhook-indicator inactive"></div>
            </div>
            <div class="webhook-item">
                <div class="webhook-name">Price Updates</div>
                <div class="webhook-indicator active"></div>
            </div>
        </div>
    </div>
</div>
<?php
/**
 * Recent Activity Widget
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Widgets
 */
defined('ABSPATH') || exit;
?>

<div class="data-card">
    <div class="card-header">
        <h3>Recent Activity</h3>
        <div class="card-actions">
            <button class="btn-transparent"><i class="fas fa-filter"></i></button>
        </div>
    </div>
    <div class="card-body">
        <div class="activity-stream">
            <div class="activity-item">
                <div class="activity-icon purple">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <div class="activity-details">
                    <p class="activity-text">Product sync completed</p>
                    <span class="activity-time">10 minutes ago</span>
                </div>
            </div>
            <div class="activity-item">
                <div class="activity-icon blue">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="activity-details">
                    <p class="activity-text">New order received - #ORD-7832</p>
                    <span class="activity-time">32 minutes ago</span>
                </div>
            </div>
            <div class="activity-item">
                <div class="activity-icon green">
                    <i class="fas fa-check"></i>
                </div>
                <div class="activity-details">
                    <p class="activity-text">Stock levels updated</p>
                    <span class="activity-time">1 hour ago</span>
                </div>
            </div>
            <div class="activity-item">
                <div class="activity-icon orange">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="activity-details">
                    <p class="activity-text">API rate limit warning</p>
                    <span class="activity-time">2 hours ago</span>
                </div>
            </div>
        </div>
    </div>
</div>
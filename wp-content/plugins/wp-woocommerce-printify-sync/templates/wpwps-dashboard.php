<?php
/**
 * Dashboard template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @var bool $apiConfigured
 * @var string $shopId
 * @var string $shopName
 * @var string $settingsUrl
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap wpwps-dashboard">
    <?php 
    // Include header and alert
    $this->section('dashboard-header', [
        'apiConfigured' => $apiConfigured,
        'shopId' => $shopId,
        'shopName' => $shopName,
        'settingsUrl' => $settingsUrl
    ]); 
    ?>
    
    <!-- Toast container for notifications -->
    <div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3"></div>
    
    <?php 
    // System health widgets
    $this->section('system-health-widgets', [
        'apiConfigured' => $apiConfigured
    ]); 
    ?>
    
    <?php 
    // Product stats cards
    $this->section('product-stats-cards'); 
    ?>
    
    <?php 
    // Charts section
    $this->section('charts-section'); 
    ?>
    
    <?php 
    // Activity table
    $this->section('activity-table', [
        'apiConfigured' => $apiConfigured
    ]); 
    ?>
    
    <?php 
    // Modals
    $this->section('dashboard-modals'); 
    ?>
</div>

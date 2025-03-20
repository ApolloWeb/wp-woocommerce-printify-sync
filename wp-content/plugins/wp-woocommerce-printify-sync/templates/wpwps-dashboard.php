<?php
/**
 * Dashboard template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @var bool $apiConfigured
 * @var string $shopId
 * @var string $shopName
 * @var string $chatGptApiKey
 * @var string $chatGptApiModel
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
    
    <?php 
    // System health widgets
    $this->section('system-health-widgets', [
        'apiConfigured' => $apiConfigured
    ]); 
    ?>
    
    <!-- AI Integration Row -->
    <div class="row">
        <div class="col-lg-6">
            <?php 
            // ChatGPT API card
            $this->section('chatgpt-api-card', [
                'apiConfigured' => $apiConfigured,
                'chatGptApiKey' => $chatGptApiKey,
                'chatGptApiModel' => $chatGptApiModel
            ]); 
            ?>
        </div>
    </div>
    
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

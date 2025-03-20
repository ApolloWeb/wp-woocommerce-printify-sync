<?php
/**
 * API Settings template
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 * @var string $apiKey
 * @var string $apiEndpoint
 * @var string $shopId
 * @var string $shopName
 * @var bool $apiConfigured
 * @var string $chatGptApiKey
 * @var string $chatGptApiModel
 * @var int $chatGptMaxTokens
 * @var float $chatGptTemperature
 * @var bool $chatGptEnableUsageLimit
 * @var float $chatGptMonthlyLimit
 * @var float $chatGptCurrentUsage
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <?php 
    // Include settings header
    $this->section('settings-header', [
        'apiConfigured' => $apiConfigured,
        'shopId' => $shopId,
        'shopName' => $shopName,
        'dashboardUrl' => admin_url('admin.php?page=printify-sync')
    ]); 
    ?>
    
    <div class="container-fluid p-0">
        <div class="row">
            <div class="col-lg-6">
                <?php 
                // API Configuration
                $this->section('settings-api-config', [
                    'apiKey' => $apiKey,
                    'apiEndpoint' => $apiEndpoint
                ]); 
                
                // Shop Selection
                $this->section('settings-shop-selection', [
                    'apiKey' => $apiKey,
                    'shopId' => $shopId,
                    'shopName' => $shopName
                ]); 
                
                // ChatGPT API Configuration
                $this->section('settings-chatgpt-config', [
                    'chatGptApiKey' => $chatGptApiKey,
                    'chatGptApiModel' => $chatGptApiModel,
                    'chatGptMaxTokens' => $chatGptMaxTokens,
                    'chatGptTemperature' => $chatGptTemperature,
                    'chatGptEnableUsageLimit' => $chatGptEnableUsageLimit,
                    'chatGptMonthlyLimit' => $chatGptMonthlyLimit,
                    'chatGptCurrentUsage' => $chatGptCurrentUsage
                ]); 
                ?>
            </div>
            
            <div class="col-lg-6">
                <?php 
                // Documentation
                $this->section('settings-documentation'); 
                
                // ChatGPT Cost Guide
                $this->section('settings-chatgpt-costguide'); 
                ?>
            </div>
        </div>
    </div>
</div>

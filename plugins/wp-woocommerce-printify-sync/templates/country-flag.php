<?php
/**
 * Template for displaying country flag
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wpwps-country-flag">
    <?php echo $countryManager->getCountryFlag($countryCode); ?>
    <span class="country-name">
        <?php echo WC()->countries->countries[$countryCode] ?? $countryCode; ?>
    </span>
</div>
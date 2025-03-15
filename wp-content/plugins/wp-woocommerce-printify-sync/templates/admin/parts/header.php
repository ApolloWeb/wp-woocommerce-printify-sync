<?php defined('ABSPATH') || exit; ?>

<div class="wpwps-header">
    <h1>
        <i class="fa-solid fa-tshirt"></i>
        <?php echo esc_html($title); ?>
    </h1>
    <div class="wpwps-header-actions">
        <span class="wpwps-timestamp">
            <i class="fa-regular fa-clock"></i>
            <?php echo esc_html($this->currentTime); // 2025-03-15 18:05:08 ?>
        </span>
        <span class="wpwps-user">
            <i class="fa-regular fa-user"></i>
            <?php echo esc_html($this->currentUser); // ApolloWeb ?>
        </span>
    </div>
</div>
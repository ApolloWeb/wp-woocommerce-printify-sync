        ?>
        <div class="card info-card h-100 <?php echo esc_attr($card_class); ?> <?php echo esc_attr($text_class); ?>">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="info-icon-wrapper me-3">
                        <i class="fas <?php echo esc_attr($icon); ?> fa-2x"></i>
                    </div>
                    <h5 class="card-title mb-0"><?php echo esc_html($title); ?></h5>
                </div>
                <p class="card-text"><?php echo wp_kses_post($description); ?></p>
            </div>
        </div>
        <?php
    }
}
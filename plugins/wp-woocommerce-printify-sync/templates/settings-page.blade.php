<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<title><?php _e('Printify Sync Settings', 'wp-woocommerce-printify-sync'); ?></title>
	<link rel="stylesheet" href="<?php echo plugins_url('assets/css/wpwps-settings.css', dirname(__FILE__, 1)); ?>">
	<!-- ...existing head elements... -->
</head>
<body>
	<div class="wpwps-settings">
		<h1><?php _e('Printify Sync Settings', 'wp-woocommerce-printify-sync'); ?></h1>
		<form id="settings-form" method="post" action="">
			<div class="form-group">
				<label for="api_key"><?php _e('Printify API Key', 'wp-woocommerce-printify-sync'); ?></label>
				<input type="text" id="api_key" name="api_key" class="form-control" required>
			</div>
			<div class="form-group">
				<label for="endpoint"><?php _e('Printify API Endpoint', 'wp-woocommerce-printify-sync'); ?></label>
				<input type="text" id="endpoint" name="endpoint" class="form-control" value="https://api.printify.com/v1">
			</div>
			<div class="form-group">
				<label for="shop-selection"><?php _e('Select Shop', 'wp-woocommerce-printify-sync'); ?></label>
				<select id="shop-selection" name="shop" class="form-control">
					<option value=""><?php _e('Select a shop', 'wp-woocommerce-printify-sync'); ?></option>
					<?php
					// Example: Loop through shops array passed from backend.
					if ( isset($shops) && is_array($shops) ) :
						foreach ( $shops as $shop ) : ?>
							<option value="<?php echo esc_attr($shop['id']); ?>">
								<?php echo esc_html($shop['name']); ?>
							</option>
						<?php endforeach;
					endif;
					?>
				</select>
			</div>
			<button type="submit" class="btn btn-primary"><?php _e('Save Settings', 'wp-woocommerce-printify-sync'); ?></button>
		</form>
	</div>
	<script src="<?php echo plugins_url('assets/js/wpwps-settings.js', dirname(__FILE__, 1)); ?>"></script>
	<!-- ...existing footer elements... -->
</body>
</html>

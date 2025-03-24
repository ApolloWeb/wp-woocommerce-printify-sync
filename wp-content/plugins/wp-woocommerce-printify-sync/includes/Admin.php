<?php
namespace ApolloWeb\WPWooCommercePrintifySync;

class Admin {

	public function init(): void {
		// Register admin menu page and assets
		add_action( 'admin_menu', [ $this, 'registerAdminMenu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAdminAssets' ] );
	}

	public function registerAdminMenu(): void {
		add_menu_page(
			__( 'Printify Sync', 'wp-woocommerce-printify-sync' ),
			__( 'Printify Sync', 'wp-woocommerce-printify-sync' ),
			'manage_options',
			'wp-woocommerce-printify-sync',
			[ $this, 'renderAdminPage' ],
			'dashicons-tag', // Use a simple dashicon first to verify menu appears
			59
		);

		add_submenu_page(
			'wp-woocommerce-printify-sync',
			__( 'Dashboard', 'wp-woocommerce-printify-sync' ),
			__( 'Dashboard', 'wp-woocommerce-printify-sync' ),
			'manage_options',
			'wp-woocommerce-printify-sync',
			[ $this, 'renderAdminPage' ]
		);

		add_submenu_page(
			'wp-woocommerce-printify-sync',
			__( 'Settings', 'wp-woocommerce-printify-sync' ),
			__( 'Settings', 'wp-woocommerce-printify-sync' ),
			'manage_options',
			'wpwpps-settings',
			[ $this, 'renderSettingsPage' ]
		);
	}

	public function enqueueAdminAssets( string $hook ): void {
		// Common assets for all plugin pages
		if ( !in_array($hook, ['toplevel_page_wp-woocommerce-printify-sync', 'printify-sync_page_wpwpps-settings']) ) {
			return;
		}

		// Base CSS (order matters)
		wp_enqueue_style('wpwpps-admin', plugins_url('../assets/css/wpwpps-admin.css', __FILE__));
		wp_enqueue_style('wpwpps-navbar', plugins_url('../assets/css/wpwpps-navbar.css', __FILE__));
		wp_enqueue_style('wpwpps-dashboard', plugins_url('../assets/css/wpwpps-dashboard.css', __FILE__));
		
		// Common JS must be loaded first
		wp_enqueue_script('wpwpps-common', plugins_url('../assets/js/wpwpps-common.js', __FILE__), ['jquery'], null, true);
		wp_localize_script('wpwpps-common', 'wpwpps_ajax', ['ajax_url' => admin_url('admin-ajax.php')]);
		
		// Page specific assets
		if ($hook === 'toplevel_page_wp-woocommerce-printify-sync') {
			wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
			wp_enqueue_script('wpwpps-dashboard', plugins_url('../assets/js/wpwpps-dashboard.js', __FILE__), ['jquery', 'chart-js', 'wpwpps-common'], null, true);
		} elseif ($hook === 'printify-sync_page_wpwpps-settings') {
			wp_enqueue_script('wpwpps-settings', plugins_url('../assets/js/wpwpps-settings.js', __FILE__), ['jquery', 'wpwpps-common'], null, true);
		}

		// Third party assets
		wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
		wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', [], null, true);
		wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
	}

	public function renderAdminPage(): void {
		$template = new Template();
		
		// Get screen object for help tabs
		$screen = get_current_screen();
		
		// Add help tabs if needed
		if ($screen) {
			$screen->add_help_tab([
				'id'      => 'wpwpps_overview',
				'title'   => __('Overview'),
				'content' => '<p>' . __('Manage your Printify products and sync settings.') . '</p>'
			]);
		}
		
		// Render template without wrap/footer
		echo $template->render(plugin_dir_path(__FILE__) . '../templates/wpwps-admin-dashboard.php', [
			'title' => __('Printify Sync Dashboard', 'wp-woocommerce-printify-sync'),
			'stats' => [
				'queued' => 5,
				'progress' => 30,
				'last_sync' => 'Success (3 products)'
			],
			'chart_data' => [
				'labels' => ['Jan', 'Feb', 'Mar', 'Apr'],
				'values' => [150, 200, 180, 220]
			]
		]);
	}

	public function renderSettingsPage(): void {
		$template = new Template();
		echo $template->render( plugin_dir_path( __FILE__, 2 ) . 'templates/wpwps-admin-settings.php', [
			'title'             => __( 'Printify Sync Settings', 'wp-woocommerce-printify-sync' ),
			'printify_api_key'  => get_option( 'wpwpps_printify_api_key' ),
			'api_endpoint'      => get_option( 'wpwpps_api_endpoint', 'https://api.printify.com/v1' ),
			'shop_id'           => get_option( 'wpwpps_shop_id' ),
			'monthly_spend_cap' => get_option( 'wpwpps_monthly_spend_cap' ),
			'tokens'            => get_option( 'wpwpps_tokens' ),
			'temperature'       => get_option( 'wpwpps_temperature' ),
		] );
	}
}

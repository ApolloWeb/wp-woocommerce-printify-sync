<?php
namespace ApolloWeb\WPWooCommercePrintifySync;

class AjaxHandler {

	public function init(): void {
		// Register Ajax actions for both logged in users
		add_action( 'wp_ajax_sync_printify_products', [ $this, 'syncPrintifyProducts' ] );
		// And for non-logged in if needed; use wp_ajax_nopriv_ prefix carefully
		// add_action( 'wp_ajax_nopriv_sync_printify_products', [ $this, 'syncPrintifyProducts' ] );
		// New AJAX actions for settings functionality
		add_action( 'wp_ajax_wpwpps_test_connection', [ $this, 'testPrintifyConnection' ] );
		add_action( 'wp_ajax_wpwpps_save_settings',   [ $this, 'saveSettings' ] );
		add_action( 'wp_ajax_wpwpps_test_monthly_estimate', [ $this, 'testMonthlyEstimate' ] );
	}

	/**
	 * Example AJAX callback to sync Printify products.
	 */
	public function syncPrintifyProducts(): void {
		// ...existing code for syncing from Printify API...
		wp_send_json_success( [ 'message' => __( 'Sync completed', 'wp-woocommerce-printify-sync' ) ] );
	}

	public function testPrintifyConnection(): void {
		$printify_api_key = sanitize_text_field( $_POST['printify_api_key'] ?? '' );
		$api_endpoint     = esc_url_raw( $_POST['api_endpoint'] ?? 'https://api.printify.com/v1' );
		$url              = trailingslashit( $api_endpoint ) . 'shops.json';

		$response = wp_remote_get( $url, [
			'headers' => [
				'Authorization' => 'Bearer ' . $printify_api_key
			],
		] );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( [ 'message' => $response->get_error_message() ] );
		}
		$body  = wp_remote_retrieve_body( $response );
		$shops = json_decode( $body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error( [ 'message' => 'Invalid JSON response' ] );
		}
		wp_send_json_success( [ 'message' => __( 'Connection successful', 'wp-woocommerce-printify-sync' ), 'shops' => $shops ] );
	}

	public function saveSettings(): void {
		$printify_api_key  = sanitize_text_field( $_POST['printify_api_key'] ?? '' );
		$api_endpoint      = esc_url_raw( $_POST['api_endpoint'] ?? 'https://api.printify.com/v1' );
		$shop_id           = sanitize_text_field( $_POST['shop_id'] ?? '' );
		$monthly_spend_cap = floatval( $_POST['monthly_spend_cap'] ?? 0 );
		$tokens            = intval( $_POST['tokens'] ?? 0 );
		$temperature       = floatval( $_POST['temperature'] ?? 0 );

		// For demo purposes, store plain; in production encrypt API key as needed
		update_option( 'wpwpps_printify_api_key', $printify_api_key );
		update_option( 'wpwpps_api_endpoint', $api_endpoint );
		update_option( 'wpwpps_shop_id', $shop_id );
		update_option( 'wpwpps_monthly_spend_cap', $monthly_spend_cap );
		update_option( 'wpwpps_tokens', $tokens );
		update_option( 'wpwpps_temperature', $temperature );
		wp_send_json_success( [ 'message' => __( 'Settings saved successfully', 'wp-woocommerce-printify-sync' ) ] );
	}

	public function testMonthlyEstimate(): void {
		$monthly_spend_cap = floatval( $_POST['monthly_spend_cap'] ?? 0 );
		$tokens            = intval( $_POST['tokens'] ?? 0 );
		$temperature       = floatval( $_POST['temperature'] ?? 0 );
		// Dummy calculation for monthly estimate cost – adjust as needed
		$estimate = $tokens * 0.01;
		wp_send_json_success( [ 'estimate' => $estimate ] );
	}
}

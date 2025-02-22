<?php
namespace ApolloWeb\WooCommercePrintifySync;

class OrderSync {
	public function __construct() {
		add_action( 'woocommerce_thankyou', [ $this, 'syncOrderToPrintify' ] );
	}

	public function syncOrderToPrintify( $orderId ) {
		$order = wc_get_order( $orderId );
		// Insert logic to sync order details with Printify here.
	}
}
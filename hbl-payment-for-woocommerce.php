<?php
/**
 * Plugin Name: Himalayan Bank Payment For WooCommerce
 * Description: Payment Gateway for Himalayan Bank.
 * Author: Manjil
 * Author URI: https://github.com/lijnam/
 * Version: 1.0.0
 * Text Domain: hbl-payment-for-woocommerce
 * Domain Path: /languages/
 *
 * @license GPL-3.0+
 */

// Check If Request is Comming From WordPress Or Not.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin constants.
 */
define( 'HBL_PAYMENT_FOR_WOOCOMMERCE_PLUGIN_FILE', __FILE__ );
define( 'HBL_PAYMENT_FOR_WOOCOMMERCE_PLUGIN_PATH', dirname( __FILE__ ) );
define( 'HBL_PAYMENT_FOR_WOOCOMMERCE_VERSION', '1.0.0' );

require_once __DIR__ . '/src/Plugin.php';

add_action(
	'plugins_loaded',
	function() {

		// Return if WooCommerce is not installed.
		if ( ! defined( 'WC_VERSION' ) ) {
			return;
		}

		require_once __DIR__ . '/includes/class-wc-hbl-payment-gateway.php';
	}
);

/**
 * Return the main instance of Plugin class.
 *
 * @since 1.0.0
 *
 * @return \HBLPaymentForWooCommerce\Plugin
 */
function hbl_payment_for_woocommerce() {

	$instance = \HBLPaymentForWooCommerce\Plugin::get_instance();
	$instance->init();

	return $instance;
}

hbl_payment_for_woocommerce();

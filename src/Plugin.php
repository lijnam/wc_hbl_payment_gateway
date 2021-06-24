<?php

namespace HBLPaymentForWooCommerce;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Plugin Class.
 *
 * @since 1.0.0
 */
final class Plugin {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize.
	 */
	public function init() {

		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_wc_hbl_gateway_class' ) );
		add_action( 'template_redirect', array( $this, 'response_handler' ) );
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/hbl-payment-for-woocommerce/hbl-payment-for-woocommerce-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/hbl-payment-for-woocommerce-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'hbl-payment-for-woocommerce' );

		load_textdomain( 'hbl-payment-for-woocommerce', WP_LANG_DIR . '/hbl-payment-for-woocommerce/hbl-payment-for-woocommerce-' . $locale . '.mo' );
		load_plugin_textdomain( 'hbl-payment-for-woocommerce', false, plugin_basename( dirname( HBL_PAYMENT_FOR_WOOCOMMERCE_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Add the gateway to WC Available Gateways
	 *
	 * @param array $methods Methods.
	 *
	 * @since 1.0.0
	 *
	 * @return array Methods including WC_HBL_Gateway.
	 */
	public function add_wc_hbl_gateway_class( $methods ) {
		$methods[] = 'WC_HBL_Gateway';

		return $methods;
	}

	/**
	 * Use to redirect the page after payment process is completed.
	 *
	 * @since 1.0.0
	 */
	public function response_handler() { //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		global $woocommerce, $wp;
		$url = home_url( $wp->request );

		if ( get_site_url() . '/checkout/order-received' === $url ) {

			if ( isset( $_REQUEST['paymentGatewayID'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended

				$logger = wc_get_logger();
				$logger->debug( 'Response From Bank ', json_encode( $_REQUEST ) ); //phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode, WordPress.Security.NonceVerification.Recommended
				$order = wc_get_order( $_REQUEST['invoiceNo'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				if ( null !== $order ) {

					if ( 'AP' === $_REQUEST['Status'] || 'RS' === $_REQUEST['Status'] ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						$order->payment_complete();
						$order->reduce_order_stock();
						$woocommerce->cart->empty_cart();
						$order->add_order_note( 'Hey, your order is paid! Thank you!', true );
						$url = $order->get_checkout_order_received_url();
						wp_safe_redirect( $url );
						exit;

					} elseif ( 'VO' === $_REQUEST['Status'] ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						$status = $this->status_verification( $_REQUEST['Status'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						$order->add_order_note( 'Transaction has been canceled by the user', false );
						$order->update_status( 'cancelled' );
						add_filter( 'template_include', array( $this, 'redirect_html_to_plugin_page' ) );

					} else {
						$status = $this->status_verification( $_REQUEST['Status'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						$order->add_order_note( 'Oops! Your transaction has failed. Due to : ' . $status, false );
						$order->update_status( 'failed' );
						add_filter( 'template_include', array( $this, 'redirect_html_to_plugin_page' ) );
					}//end if
				}//end if
			} else {
				status_header( 404 );
				nocache_headers();
				include get_query_template( '404' );
				exit;
			}//end if
		}//end if
	}

	/**
	 * Status Verification
	 *
	 * @param string Status.
	 *
	 * @since 1.0.0
	 *
	 * @return string verification status.
	 */
	public function status_verification( $status = '' ) { //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded

		switch ( $status ) {
			case 'AP':
				$status_text = 'Approved(Paid)';
				break;

			case 'SE':
				$status_text = 'Settled';
				break;

			case 'VO':
				$status_text = 'Voided (Canceled)';
				break;

			case 'DE':
				$status_text = 'Declined by the issuer Host';
				break;

			case 'FA':
				$status_text = 'Failed';
				break;

			case 'PE':
				$status_text = 'Pending';
				break;

			case 'EX':
				$status_text = 'Expired';
				break;

			case 'RE':
				$status_text = 'Refunded';
				break;

			case 'RS':
				$status_text = 'Ready to Settle';
				break;

			case 'AU':
				$status_text = 'Authenticated';
				break;

			case 'IN':
				$status_text = 'Initiated';
				break;

			case 'FP':
				$status_text = 'Fraud Passed';
				break;

			case 'PA':
				$status_text = 'Paid (Cash)';
				break;

			case 'MA':
				$status_text = 'Matched (Cash)';
				break;

			default:
				$status_text = 'No Data From HBL';
				break;
		}//end switch

		return $status_text;
	}

	/**
	 * Redirect HTML to plugin page.
	 *
	 * @param  string $template Template path.
	 *
	 * @return string Template Path.
	 */
	public function redirect_html_to_plugin_page( $template ) {
		global $wp;

		if ( 'VO' === $_REQUEST['Status'] ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$new_template = HBL_PAYMENT_FOR_WOOCOMMERCE_PLUGIN_PATH . 'templates/canceled.php';
			return $new_template;
		} else {
			$new_template = HBL_PAYMENT_FOR_WOOCOMMERCE_PLUGIN_PATH . 'templates/declined.php';
			return $new_template;
		}

		return $template;
	}
}

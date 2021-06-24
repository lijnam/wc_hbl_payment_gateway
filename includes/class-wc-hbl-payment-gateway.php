<?php

/**
 * HBL Payment Gateway Class
 *
 * @since 1.0.0
 */
class WC_HBL_Gateway extends WC_Payment_Gateway {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id                        = 'hbl_gateway';
		$this->has_fields                = false;
		$this->method_title              = 'Himalayan Bank';
		$this->method_description        = esc_html__( 'Accept payments via Himalyan Bank Ltd..', 'hbl-payment-for-woocommerce' );
		$mastercard_img                  = plugins_url( 'assets/img/mastercard_secure.png', HBL_PAYMENT_FOR_WOOCOMMERCE_PLUGIN_FILE );
		$visa_img                        = plugins_url( 'assets/img/verified_by_visa.png', HBL_PAYMENT_FOR_WOOCOMMERCE_PLUGIN_FILE );
		$americanexpress_img             = plugins_url( 'assets/img/american_express_safekey.png', HBL_PAYMENT_FOR_WOOCOMMERCE_PLUGIN_FILE );
		$unioinpay_img                   = plugins_url( 'assets/img/UnionPay_logo.png', HBL_PAYMENT_FOR_WOOCOMMERCE_PLUGIN_FILE );
		$jcb_img                         = plugins_url( 'assets/img/jcb_secure.png', HBL_PAYMENT_FOR_WOOCOMMERCE_PLUGIN_FILE );
		$hbl_img                         = plugins_url( 'assets/img/hbl_logo.png', HBL_PAYMENT_FOR_WOOCOMMERCE_PLUGIN_FILE );
		$payment_brands_img_description  = '<div style="text-align: center;">';
		$payment_brands_img_description .= '<img src="' . $mastercard_img . ' " alt="mastercard" style="height: 40px;margin: 10px;float: none;max-height: 60px;display: inline-block;" >';
		$payment_brands_img_description .= '<img src="' . $visa_img . ' " alt="visa" style="height: 50px;margin: 10px;float: none;max-height: 60px;display: inline-block;">';
		$payment_brands_img_description .= '<img src="' . $americanexpress_img . ' " alt="safekey" style="height: 60px;margin: 10px;float: none;max-height: 60px;display: inline-block;" >';
		$payment_brands_img_description .= '<img src="' . $unioinpay_img . ' " alt="unionpay"  style="height: 60px;margin: 10px;float: none;max-height: 60px;display: inline-block;" >';
		$payment_brands_img_description .= '<img src="' . $jcb_img . ' " alt="jcb"  style="height: 60px;margin: 10px;float: none;max-height: 60px;display: inline-block;" >';
		$payment_brands_img_description .= '</div>';
		$payment_brands_img_description .= '<div  style="height: 50px">';
		$payment_brands_img_description .= '<p style="margin: 5px auto;font-size: 14px;display: block;width: fit-content;">POWERED BY :</p> <img src="' . $hbl_img . ' " alt="hbl" style="height: 36px;margin: 10px auto 10px auto; float:none; max-height: 36px;" >';
		$payment_brands_img_description .= '</div>';

		$this->description               = $this->get_option( 'description' ) . $payment_brands_img_description;
		$this->init_form_fields();
		$this->init_settings();
		$this->title = $this->get_option( 'title' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( &$this, 'send_request_to_bank' ), 10, 1 );
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 *
	 * @since 1.0.0
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'         => array(
				'title'   => esc_html__( 'Enable/Disable', 'hbl-payment-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => esc_html__( 'Enable Himalayan Bank Payment', 'hbl-payment-for-woocommerce' ),
				'default' => 'yes',
			),

			'title'           => array(
				'title'       => esc_html__( 'Title', 'hbl-payment-for-woocommerce' ),
				'type'        => 'text',
				'description' => esc_html__( 'This controls the title which the user sees during checkout.', 'hbl-payment-for-woocommerce' ),
				'default'     => esc_html__( 'Himalayan Pay', 'hbl-payment-for-woocommerce' ),
			),

			'description'     => array(
				'title'       => esc_html__( 'Description', 'hbl-payment-for-woocommerce' ),
				'type'        => 'textarea',
				'description' => esc_html__( 'This controls the description which the user sees during checkout.', 'hbl-payment-for-woocommerce' ),
				'default'     => esc_html__( 'Pay with your credit card via our super-cool payment gateway', 'hbl-payment-for-woocommerce' ),
			),

			'testmode'        => array(
				'title'       => esc_html__( 'Test mode', 'hbl-payment-for-woocommerce' ),
				'label'       => esc_html__( 'Enable Test Mode', 'hbl-payment-for-woocommerce' ),
				'type'        => 'checkbox',
				'description' => esc_html__( 'Place the payment gateway in test mode using test API keys.', 'hbl-payment-for-woocommerce' ),
				'default'     => 'yes',
				'desc_tip'    => true,
			),

			'test_api_key'    => array(
				'title'       => esc_html__( 'Test Merchent Id', 'hbl-payment-for-woocommerce' ),
				'type'        => 'text',
				'description' => esc_html__( 'Place The Pagment Gateway Test API Key.', 'hbl-payment-for-woocommerce' ),
				'desc_tip'    => true,
			),

			'test_secret_key' => array(
				'title'       => esc_html__( 'Test Secret Key', 'hbl-payment-for-woocommerce' ),
				'type'        => 'text',
				'description' => esc_html__( 'Place The Pagment Gateway Test Secret Key to encrypt and decrypt then response.', 'hbl-payment-for-woocommerce' ),
				'desc_tip'    => true,
			),

			'live_api_key'    => array(
				'title'       => esc_html__( 'Live Merchent Id', 'hbl-payment-for-woocommerce' ),
				'type'        => 'text',
				'description' => esc_html__( 'Place The Pagment Gateway Live API Key.', 'hbl-payment-for-woocommerce' ),
				'desc_tip'    => true,
			),

			'live_secret_key' => array(
				'title'       => esc_html__( 'Live Secret Key', 'hbl-payment-for-woocommerce' ),
				'type'        => 'text',
				'description' => esc_html__( 'Place The Pagment Gateway Live Secret Key to encrypt and decrypt then response.', 'hbl-payment-for-woocommerce' ),
				'desc_tip'    => true,
			),

		);
	}

	/**
	 * Display the description.
	 *
	 * @since 1.0.0
	 */
	public function payment_fields() {
		if ( $this->description ) {
			echo wpautop( wptexturize( $this->description ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Process payment.
	 *
	 * @param  int $order_id  Order ID.
	 *
	 * @since 1.0.0
	 *
	 * @return array Result.
	 */
	public function process_payment( $order_id ) {
		global $woocommerce;
		$order = new WC_Order( $order_id );
		$order->add_order_note( 'Awaiting Card payment' );
		$order->update_status( 'pending-payment', __( 'Awaiting Card payment', 'hbl-payment-for-woocommerce' ) );
		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);
	}

	/**
	 * Display message about redirection after the checkout.
	 *
	 * @param  array $order Order.
	 *
	 * @since 1.0.0
	 */
	public function send_request_to_bank( $order ) {
		echo '<p>' . esc_html__( 'Please Wait you have been redirected to bank payment site.', 'hbl-payment-for-woocommerce' ) . '</p>';
		echo $this->generate_hbl_bank_payment_form( $order ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Generate HBL Bank Payment form.
	 *
	 * @param  int $order_id Order ID.
	 *
	 * @since 1.0.0
	 *
	 * @return string a form.
	 */
	public function generate_hbl_bank_payment_form( $order_id ) {
		global $woocommerce, $wp;
		$order   = new WC_Order( $order_id );
		$api_url = null;
		$token   = null;
		$name    = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

		if ( $this->get_option( 'testmode' ) === 'no' ) {
			$token   = $this->get_option( 'live_api_key' );
			$api_url = 'https://hblpgw.2c2p.com/HBLPGW/Payment/Payment/Payment';
		} else {
			$token   = $this->get_option( 'test_api_key' );
			$api_url = 'https://uat3ds.2c2p.com/HBLPGW/Payment/Payment/Payment';
		}
		$html  = '<form id="hbl-payment-form" method="post" action ="' . esc_url( $api_url ) . '" >';
		$html .= '<input type="text" id="paymentGatewayID" name="paymentGatewayID" value="' . esc_attr( $token ) . '"/>';
		$html .= '<input type="text" id="invoiceNo" name="invoiceNo" value="' . absint( $order->id ) . '"/>';
		$html .= '<input type="text" id="productDesc" name="productDesc" value="' . absint( $order->id ) . '"/>';
		$html .= '<input type="text" id="amount" name="amount" value="' . esc_attr( $this->convert_amount( $order->get_total() ) ) . '"/>';
		$html .= '<input type="text" id="currencyCode" name="currencyCode" value="' . esc_attr( $this->currency_to_code_convertor( $order->get_currency() ) ) . '"/>';
		$html .= '<input type="text" id="userDefined1" name="userDefined1" value="Customer Name: ' . esc_html( $name ) . '"/>';
		$html .= '<input type="text" id="userDefined2" name="userDefined2" value="Customer email: ' . esc_html( $order->get_billing_email() ) . '"/>';
		$html .= '<input type="text" id="userDefined3" name="userDefined3" value="Customer Contact: ' . esc_html( $order->get_billing_phone() ) . '"/>';
		$html .= '<input type="text" id="userDefined5" name="userDefined5" value="UPOP" />';
		$html .= '<input type="text" id="nonSecure" name="nonSecure" value="N"/>';
		$html .= '<input type="text" id="hashValue" name="hashValue" value="' . esc_attr( $this->hash_generator( $order->id, $this->convert_amount( $order->get_total() ), $this->currency_to_code_convertor( $order->get_currency() ) ) ) . '"/>';
		$html .= '</form>';
		$html .= '<script type="text/javascript">';
		$html .= "document.getElementById('hbl-payment-form').submit();";
		$html .= '</script>';

		return $html;
	}

	/**
	 * Convert Given Amount to the Bank standard amount format.
	 *
	 * @param  string $amount Amount.
	 *
	 * @return string
	 */
	public function convert_amount( $amount = '' ) {
		if ( ! empty( $amount ) ) {
				$amount     = $amount * 100;
				$new_amount = str_pad( $amount, 12, 0, STR_PAD_LEFT );
				return $new_amount;
		}

		return esc_html__( 'Amount Field Was Empty', 'hbl-payment-for-woocommerce' );
	}

	/**
	 * Convert Given Currency to Currency Code
	 *
	 * @param  string $currency Currency.
	 *
	 * @return string
	 */
	public function currency_to_code_convertor( $currency = '' ) {

		if ( ! empty( $currency ) ) {
			switch ( strtoupper( $currency ) ) {
				case 'USD':
					return 840;
				break; //phpcs:ignore Squiz.PHP.NonExecutableCode.Unreachable

				default:
					return 524;
				break; //phpcs:ignore Squiz.PHP.NonExecutableCode.Unreachable
			}
		}

		return esc_html__( 'Currency was empty', 'hbl-payment-for-woocommerce' );
	}

	/**
	 * Generate Hash.
	 *
	 * @param  string $order_id      Order ID.
	 * @param  string $amount        Amount.
	 * @param  string $currency_code Currency Code.
	 *
	 * @since 1.0.0
	 *
	 * @return string.
	 */
	public function hash_generator( $order_id = '', $amount = '', $currency_code = '' ) {

		if ( ! empty( $order_id ) && ! empty( $amount ) && ! empty( $currency_code ) ) {

			$secret_key       = $this->get_option( 'testmode' ) === 'no' ? $this->live_secret_key : $this->test_secret_key;
			$merchent_id      = $this->get_option( 'testmode' ) === 'no' ? $this->live_api_key : $this->test_api_key;
			$signature_string = $merchent_id + $order_id + $amount + $currency_code + 'N';
			return urlencode( strtoupper( hash_hmac( 'SHA256', $signature_string, $secret_key, false ) ) ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode
		}

		return esc_html__( 'Value Field Was Empty', 'hbl-payment-for-woocommerce' );
	}
}

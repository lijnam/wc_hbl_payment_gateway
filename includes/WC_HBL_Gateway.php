<?php
/**
 * @package Babinr_HBL_Wocommerce_Gateway
 * @version 1.0.0
 */

class WC_HBL_Gateway extends WC_Payment_Gateway
{

	public function __construct() {
		$this->id = "hbl_gateway";
		$this->has_fields = false;
		$this->method_title = "Payment Gateway From Himalayan Bank";
		$this->method_description = "Create Your Own WoCommerce Payment Gateway Plugin";
        $mastercard_img                 = plugin_dir_url( __FILE__ ) . 'assets/img/mastercard_secure.png';
        $visa_img                       = plugin_dir_url( __FILE__ ) . 'assets/img/verified_by_visa.png';
        $americanexpress_img                    = plugin_dir_url( __FILE__ ) . 'assets/img/american_express_safekey.png';
        $unioinpay_img                    = plugin_dir_url( __FILE__ ) . 'assets/img/UnionPay_logo.png';
        $jcb_img                    = plugin_dir_url( __FILE__ ) . 'assets/img/jcb_secure.png';
        $hbl_img                        = plugin_dir_url( __FILE__ ) . 'assets/img/hbl_logo.png';
        $payment_brands_img_description = '<div style="text-align: center;">';
        $payment_brands_img_description .= '<img src="' . $mastercard_img . ' " alt="mastercard" style="height: 40px;margin: 10px;float: none;max-height: 60px;display: inline-block;" >';
        $payment_brands_img_description .= '<img src="' . $visa_img . ' " alt="visa" style="height: 50px;margin: 10px;float: none;max-height: 60px;display: inline-block;">';
        $payment_brands_img_description .= '<img src="' . $americanexpress_img . ' " alt="safekey" style="height: 60px;margin: 10px;float: none;max-height: 60px;display: inline-block;" >';
        $payment_brands_img_description .= '<img src="' . $unioinpay_img . ' " alt="unionpay"  style="height: 60px;margin: 10px;float: none;max-height: 60px;display: inline-block;" >';
        $payment_brands_img_description .= '<img src="' . $jcb_img . ' " alt="jcb"  style="height: 60px;margin: 10px;float: none;max-height: 60px;display: inline-block;" >';
        $payment_brands_img_description .= '</div>';
        $payment_brands_img_description .= '<div  style="height: 50px">';
        $payment_brands_img_description .= '<p style="margin: 5px auto;font-size: 14px;display: block;width: fit-content;">POWERED BY :</p> <img src="' . $hbl_img . ' " alt="hbl" style="height: 36px;margin: 10px auto 10px auto; float:none; max-height: 36px;" >';
        $payment_brands_img_description .= '</div>';
		$this->description = $this->get_option( 'description' ).$payment_brands_img_description;
		$this->init_form_fields();
		$this->init_settings();
		$this->title = $this->get_option( 'title' );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( &$this, 'send_request_to_bank' ), 10, 1);
	}

	// Initialise Gateway Settings Form Fields
	public function init_form_fields() {
	     $this->form_fields = array(
	     	'enabled' => array(
		        'title' => __( 'Enable/Disable', 'woocommerce' ),
		        'type' => 'checkbox',
		        'label' => __( 'Enable Himalayan Bank Payment', 'woocommerce' ),
		        'default' => 'yes'
		    ),

		     'title' => array(
		          'title' => __( 'Title', 'woocommerce' ),
		          'type' => 'text',
		          'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
		          'default' => __( 'Himalayan Pay', 'woocommerce' )
		    ),

		     'description' => array(
		          'title' => __( 'Description', 'woocommerce' ),
		          'type' => 'textarea',
		          'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
		          'default' => __("Pay with your credit card via our super-cool payment gateway", 'woocommerce')
		    ),

		    'testmode' => array(
				'title'       => 'Test mode',
				'label'       => 'Enable Test Mode',
				'type'        => 'checkbox',
				'description' => 'Place the payment gateway in test mode using test API keys.',
				'default'     => 'yes',
				'desc_tip'    => true,
			),

			'test_api_key' => array(
				'title'       => 'Test Merchent Id',
				'type'        => 'text',
				'description' => 'Place The Pagment Gateway Test API Key.',
				'desc_tip'    =>  true,
			),

			'test_secret_key' => array(
				'title'       => 'Test Secret Key',
				'type'        => 'text',
				'description' => 'Place The Pagment Gateway Test Secret Key to encrypt and decrypt then response.',
				'desc_tip'    =>  true,
			),

			'live_api_key' => array(
				'title'       => 'Live Merchent Id',
				'type'        => 'text',
				'description' => 'Place The Pagment Gateway Live API Key.',
				'desc_tip'    =>  true,
			),

			'live_secret_key' => array(
				'title'       => 'Live Secret Key',
				'type'        => 'text',
				'description' => 'Place The Pagment Gateway Live Secret Key to encrypt and decrypt then response.',
				'desc_tip'    =>  true,
			)

	    );
	}

	public function payment_fields(){
	    if ( $this->description )
	       echo wpautop( wptexturize( $this->description ) );
     }

	public function process_payment( $order_id ) {
	    global $woocommerce;
	    $order = new WC_Order( $order_id );
	    $order->add_order_note( 'Awaiting Card payment' );
	    $order->update_status('pending-payment', __( 'Awaiting Card payment', 'woocommerce' ) );
	    return array(
				'result' 	=> 'success',
				'redirect'	=> $order->get_checkout_payment_url( true )
			);

	}

	public function send_request_to_bank( $order ) {
		echo "<p>" . __('Please Wait you have been redirected to bank payment site.') . '</p>';
		echo $this->generate_hbl_bank_payment_form( $order );
	}

	public function generate_hbl_bank_payment_form( $order_id ) {
		global $woocommerce, $wp;
		$order = new WC_Order( $order_id );
		$api_url = null;
		$token = null;
		$name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

		if( $this->get_option( 'testmode' ) == "no" ) {
			$token = $this->get_option( 'live_api_key' );
	 		$api_url = "https://hblpgw.2c2p.com/HBLPGW/Payment/Payment/Payment";
		} else {
			$token = $this->get_option( 'test_api_key' );
    		$api_url = "https://uat3ds.2c2p.com/HBLPGW/Payment/Payment/Payment";
		}
	    $html = '<form id="hbl-payment-form" method="post" action ="' . $api_url . '" >';
	    $html .= '<input type="text" id="paymentGatewayID" name="paymentGatewayID" value="' . $token .'"/>';
	    $html .= '<input type="text" id="invoiceNo" name="invoiceNo" value="'. $order->id .'"/>';
	    $html .= '<input type="text" id="productDesc" name="productDesc" value="' . $order->id . '"/>';
	    $html .= '<input type="text" id="amount" name="amount" value="' . $this->convert_amount( $order->get_total() ) . '"/>';
	    $html .= '<input type="text" id="currencyCode" name="currencyCode" value="' . $this->currency_to_code_convertor( $order->get_currency() ) . '"/>';
	    $html .= '<input type="text" id="userDefined1" name="userDefined1" value="Customer Name: ' . $name . '"/>';
	    $html .= '<input type="text" id="userDefined2" name="userDefined2" value="Customer email: ' . $order->get_billing_email() .'"/>';
	    $html .= '<input type="text" id="userDefined3" name="userDefined3" value="Customer Contact: ' . $order->get_billing_phone() .'"/>';
	     $html .= '<input type="text" id="userDefined5" name="userDefined5" value="UPOP" />';
	    $html .= '<input type="text" id="nonSecure" name="nonSecure" value="N"/>';
	    $html .= '<input type="text" id="hashValue" name="hashValue" value="' . $this->hash_generator( $order->id, $this->convert_amount( $order->get_total() ), $this->currency_to_code_convertor( $order->get_currency() ) ) . '"/>';
	    $html .= "</form>";
	    $html .= '<script type="text/javascript">';
	    $html .= "document.getElementById('hbl-payment-form').submit();";
	    $html .= "</script>";
	    return $html;
	    exit;
	}

	// Convert Given Amount to the Bank standard amount format
	public function convert_amount( $amount = "" ){
		if( !empty( $amount ) ){
				$amount = $amount * 100;
				$new_amount = str_pad($amount, 12, 0, STR_PAD_LEFT);
				return $new_amount;
				exit;
			}
		return "Amount Field Was Empty";
		exit;
	}

	// Convert Given Currency to Currency Code
	public function currency_to_code_convertor( $currency = '') {
		if( !empty( $currency ) ){
			switch ( strtoupper( $currency ) ) {
				case 'USD':
					return 840;
				break;

				default:
					return 524;
				break;
			}
		}
		return "Currency was empty";
		exit;
	}

	public function hash_generator( $order_id = '', $amount = '', $currency_code = '' ){
		if( !empty( $order_id ) && !empty( $amount ) && !empty( $currency_code ) ){
			$secretKey = ($this->get_option( 'testmode' ) == "no") ? $this->live_secret_key : $this->test_secret_key;
			$merchent_id = ($this->get_option( 'testmode' ) == "no") ? $this->live_api_key : $this->test_api_key;
			$signatureString =  $merchent_id + $order_id + $amount + $currency_code + 'N';
			return urlencode( strtoupper( hash_hmac('SHA256', $signatureString, $secretKey, false) ) );
			exit;
		}
		return "Value Field Was Empty";
		exit;
	}
}

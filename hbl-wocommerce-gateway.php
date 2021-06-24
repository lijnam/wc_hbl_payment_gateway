<?php

/*
 * Plugin Name: Himalayan Bank Payment Gateway for Woocommerce 
 * Plugin URI: https://github.com/lijnam/wc_hbl_payment_gateway
 * Description: Payment Gateway for Himalayan Bank.
 * Author: Manjil
 * Author URI: 
 * Version: 1.0.0
 * 
 * Copyright: © 2009-2015 WooCommerce.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

 // Check If Request is Comming From Wordpress Or Not.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Make sure WooCommerce is Installed And Active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}

// Add the gateway to WC Available Gateways
function add_wc_hbl_gateway_class( $methods ) {
	$methods[] = 'WC_HBL_Gateway';
	return $methods;
}

add_action( 'template_redirect', 'hbl_response_handler' );
add_filter( 'woocommerce_payment_gateways', 'add_wc_hbl_gateway_class' );

add_action( 'plugins_loaded', 'init_hbl_wc_gateway_class' );

// Plugin load and Ask for Payment Class
function init_hbl_wc_gateway_class() {
    require_once dirname( __FILE__ ) . '/includes/WC_HBL_Gateway.php';
}

// Use to redirect the page after payment process is completed.
function hbl_response_handler(){
	global $woocommerce, $wp;
	$url = home_url($wp->request);
	if( $url == get_site_url() . '/checkout/order-received') {

		if( isset( $_REQUEST['paymentGatewayID'] ) ) {

            $logger = wc_get_logger();
            $logger->debug( 'Response From Bank ', json_encode( $_REQUEST ) );
			$order = wc_get_order( $_REQUEST['invoiceNo'] );

            if( $order != null ) {

                if( $_REQUEST['Status'] == 'AP' || $_REQUEST['Status'] == 'RS' ) {
                    $order->payment_complete();
                    $order->reduce_order_stock();
                    $woocommerce->cart->empty_cart();
                    $order->add_order_note( 'Hey, your order is paid! Thank you!', true );
                    $url = $order->get_checkout_order_received_url();
                    wp_safe_redirect( $url );
                    exit;

                } elseif( $_REQUEST['Status'] == 'VO' ) {
                    $status = status_verification( $_REQUEST['Status'] );
                    $order->add_order_note( 'Transaction has been canceled by the user', false );
                    $order->update_status( 'cancelled' );
                    add_filter( 'template_include', 'redirect_html_to_plugin_page');

                } else {
                    $status = status_verification( $_REQUEST['Status'] );
                    $order->add_order_note( 'Oops! Your transaction has failed. Due to : ' . $status, false );
                    $order->update_status( 'failed' );
                    add_filter( 'template_include', 'redirect_html_to_plugin_page');
                }

            }

		} else {
			status_header( 404 );
	        nocache_headers();
	        include( get_query_template( '404' ) );
	        exit;
		}
	}
}

// Check the Status
function status_verification( $status ){
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
	}

	return $status_text;
}

// Show the custom templete
function redirect_html_to_plugin_page( $template ) {
	global $wp;
	if( $_REQUEST['Status'] == 'VO' ) {
		$new_template =  plugin_dir_path( __FILE__ ) . 'templates/canceled.php';
        return $new_template;
	} else {
		$new_template =  plugin_dir_path( __FILE__ ) . 'templates/declined.php';
        return $new_template;
	}
	return;
}

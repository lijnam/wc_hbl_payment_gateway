<?php
/*
Plugin Name:Himalayan Bank 2C2P Redirect API for WooCommerce
Description: Accept Payment (Credit/Debit Cards, Alipay, Alternative/Cash Payments) on your WooCommerce webstore.
Version: 7.0.1(2c2p)
Author: Manjil
*/

add_action('plugins_loaded', 'fun2c2p_init', 0);
add_action('admin_head', 'fun2c2p_backorder_font_icon');
add_action('init', 'fun2c2p_register_awaiting_payment_order_status');
add_filter('wc_order_statuses', 'fun2c2p_add_awaiting_payment_to_order_statuses');
register_activation_hook(__FILE__, 'fun2c2p_register_activation_hook');
register_deactivation_hook(__FILE__, 'fun2c2p_register_deactivation_hook');
add_action('template_redirect', 'before_template_loads');
add_shortcode('hbl_2c2p_custom_payment', 'custom_payment_shortcode');
function custom_payment_shortcode()
{
    ob_start();
    $objWC_Gateway_2c2p_Hbl = new WC_Gateway_2c2p_Hbl();
    $pg_2c2p_setting_values = $objWC_Gateway_2c2p_Hbl->wc_2c2p_get_setting();
    if (is_page($pg_2c2p_setting_values['wc_2c2p_payment_page']) && $pg_2c2p_setting_values['wc_2c2p_payment_page'] != 'none') {
        html_payment_form_code($pg_2c2p_setting_values);
    }
    return ob_get_clean();
}

function html_payment_form_code($pg_2c2p_setting_values)
{
    $order_id = wc_sanitize_order_id(sanitize_text_field($_GET['order']));
    // $liveUrl = get_permalink($pg_2c2p_setting_values['wc_2c2p_payment_page']);
    $liveUrl = 'https://hblpgw.2c2p.com/HBLPGW/Payment/Payment/Payment';
    //echo $liveUrl;
    if (!empty(wc_get_order($order_id))) {
        echo generate_payment_form($order_id, $liveUrl);
    } elseif (!empty($order_id)) {
        echo 'Invalid order id';
    } else {
        echo 'Order not found';
    }
}

function generate_payment_form($order_id = null, $liveUrl)
{
    $liveurl = $liveUrl;

    $order = new WC_Order($order_id);
    //var_dump($order);exit;
    if (is_user_logged_in()) { // Customer is loggedin.
        $loggedin_user_data = wp_get_current_user();
        $cust_email = $loggedin_user_data->data->user_email;
    } else {
        $cust_email = $order->data['billing']['email']; //Gust customer.
    }
    $product_name = '';
    foreach ($order->get_items() as $item) {
        $product_name .= $item['name'] . ', ';
    }

    $product_name = (strlen($product_name) > 0) ? substr($product_name, 0, strlen($product_name) - 2) : "";
    $product_name .= '.';
    $product_name = filter_var($product_name, FILTER_SANITIZE_STRING);

    $fun2c2p_args = array(
        'payment_description' => $product_name,
        'order_id' => $order_id,
        'invoice_no' => $order_id,
        'amount' => $order->order_total,
        'customer_email' => sanitize_email($cust_email),
        // 'stored_card_unique_id' => $wc_2c2p_stored_card_token_id != 0 ? $wc_2c2p_stored_card_token_id : "",
        // 'default_lang' => $default_lang
    );

    $objWC_2C2P_Validation_Helper = new WC_2C2P_Validation_Helper();
    $isValid = $objWC_2C2P_Validation_Helper->wc_2c2p_is_valid_merchant_request($fun2c2p_args);

    if (!$isValid) {
        foreach ($objWC_2C2P_Validation_Helper->wc_2c2p_error as $key => $value) {
            echo esc_html__($value, 'woo_2c2p');
        }

        echo '</br>';
        echo '&nbsp;&nbsp;&nbsp;&nbsp<a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . esc_html__('Cancel order &amp; restore cart', 'woo_2c2p') . '</a>';
        return;
    } else {
        echo '<ul class="order_details">';
        echo '<li class="order">' . __('Order number:', 'woocommerce') . '<strong>' . $order->get_order_number() . '</strong></li>';
        echo '<li class="date">' . __('Date:', 'woocommerce') . '<strong>' . wc_format_datetime($order->get_date_created()) . '</strong></li>';

        if (is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email()) {
            echo '<li class="email">' . __('Email:', 'woocommerce') . '<strong>' . $order->get_billing_email() . '</strong></li>';
        }
        echo '<li class="total">' . __('Total:', 'woocommerce') . '<strong>' . $order->get_formatted_order_total() . '</strong></li>';
        if ($order->get_payment_method_title()) {

            echo '<li class="method">' . __('Payment method:', 'woocommerce') . '<strong>' . wp_kses_post($order->get_payment_method_title()) . '</strong></li>';
        }
        echo '</ul>';
        echo '<p><strong>' . esc_html__('Thank you for your order.', 'woo_2c2p') . '</strong> <br/>' . esc_html__('The payment page will open if you click on button "Pay".', 'woo_2c2p') . '</p>';
    }

    $fun2c2p_args['amount'] = $objWC_2C2P_Validation_Helper->wc_2c2p_validate_currency_exponent($fun2c2p_args['amount']);

    $objwc_2c2p_construct_request = new wc_2c2p_construct_request_helper();

    $wc_2c2p_form_field = $objwc_2c2p_construct_request->wc_2c2p_construct_request(is_user_logged_in(), $fun2c2p_args, true);

    $strHtml = '';
    $strHtml .= '<form action="' . esc_url($liveurl) . '" method="post" id="2c2p_payment_form">';
    $strHtml .= $wc_2c2p_form_field;
    $strHtml .= '<input type="submit" class="button-alt" id="submit_2c2p_payment_form" value="' . esc_html__('Pay', 'woo_2c2p') . '" />';
    $strHtml .= '&nbsp;&nbsp;&nbsp;&nbsp<a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . esc_html__('Cancel order &amp; restore cart', 'woo_2c2p') . '</a>';
    $strHtml .= '</form>';

    return $strHtml;
}

function before_template_loads()
{
    //message on order received page
    if (is_wc_endpoint_url('order-received')) {
        //add filter
        add_filter('woocommerce_thankyou_order_received_text', function () {
            $order_id = sanitize_text_field($_REQUEST['invoiceNo']);
            $hash = sanitize_text_field($_REQUEST['hashValue']);
            $transaction_ref = isset($_REQUEST['tranRef']) ? sanitize_text_field($_REQUEST['tranRef']) : "";
            $payment_status = isset($_REQUEST['status']) ? sanitize_text_field($_REQUEST['status']) : "";
            $payment_amount = isset($_REQUEST['amount']) ? sanitize_text_field($_REQUEST['amount']) : "";
            $respCode = isset($_REQUEST['respCode']) ? sanitize_text_field($_REQUEST['respCode']) : "";
            $fraudCode = isset($_REQUEST['fraudCode']) ? sanitize_text_field($_REQUEST['fraudCode']) : "";
            $approvalCode = isset($_REQUEST['approvalCode']) ? sanitize_text_field($_REQUEST['approvalCode']) : "";
            $eci = isset($_REQUEST['eci']) ? sanitize_text_field($_REQUEST['eci']) : "";
            $transaction_datetime = isset($_REQUEST['dateTime']) ? sanitize_text_field($_REQUEST['dateTime']) : "";
            $pan = isset($_REQUEST['pan']) ? sanitize_text_field($_REQUEST['pan']) : "";
            //$message = 'Thank you for shopping with us. Your account has been charged and your transaction is successful.';
            $message = '$payment_amount: ' . $payment_amount;
            $message .= '<br>respCode:' . $respCode;
            $message .= '<br>status:' . $payment_status;
            $message .= '<br>fraudCode:' . $fraudCode;
            $message .= '<br>$approvalCode:' . $approvalCode;
            $message .= '<br>$eci:' . $eci;
            $message .= '<br>$transaction_datetime:' . $transaction_datetime;
            $message .= '<br>$pan:' . $pan;
            $message .= '<br>$transaction_ref:' . $transaction_ref;
            $message .= '<br>$order_id:' . $order_id;
            $message .= '<br>all response:' . var_dump($_REQUEST);

            return $message;
        });
    }

}


function fun2c2p_register_activation_hook()
{

    global $woocommerce;

    $args = array(
        'post_type' => 'shop_order',
        'post_status' => 'wc-pending',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_payment_method',
                'value' => '2c2p',
                'compare' => 'LIKE',
            )
        )
    );

    $loop = new WP_Query($args);

    try {
        while ($loop->have_posts()) : $loop->the_post();
            $order = new WC_Order($loop->post->ID);
            $order->update_status('awaiting-payment');
        endwhile;
    } catch (Exception $e) {

    }
}

function fun2c2p_register_deactivation_hook()
{

    global $woocommerce;

    $args = array(
        'post_type' => 'shop_order',
        'post_status' => 'wc-awaiting-payment',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_payment_method',
                'value' => '2c2p',
                'compare' => 'LIKE',
            )
        )
    );

    $loop = new WP_Query($args);

    try {
        while ($loop->have_posts()) : $loop->the_post();
            $order = new WC_Order($loop->post->ID);
            $order->update_status('pending');
        endwhile;
    } catch (Exception $e) {

    }
}


/* This function is set the 2c2p icon in admin panel */
function fun2c2p_backorder_font_icon()
{
    echo '<style>
            .widefat .column-order_status mark.awaiting-payment:after{
                font-family:WooCommerce;
                speak:none;
                font-weight:400;
                font-variant:normal;
                text-transform:none;
                line-height:1;
                -webkit-font-smoothing:antialiased;
                margin:0;
                text-indent:0;
                position:absolute;
                top:0;
                left:0;
                width:100%;
                height:100%;
                text-align:center;
            }
            .widefat .column-order_status mark.awaiting-payment:after{
                content:"\e012";
                color:#0496c9;
            }
  </style>';
}

/* This function is used to add custom order status into post */
function fun2c2p_register_awaiting_payment_order_status()
{

    register_post_status('wc-awaiting-payment', array(
        'label' => 'Awaiting Payment',
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Awaiting Payment <span class="count">(%s)</span>', 'Awaiting Payment <span class="count">(%s)</span>')
    ));
}

// Add to list of WC Order statuses
function fun2c2p_add_awaiting_payment_to_order_statuses($order_statuses)
{

    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {

        $new_order_statuses[$key] = $status;
        if ('wc-processing' === $key) {
            $new_order_statuses['wc-awaiting-payment'] = 'Awaiting Payment';
        }
    }

    return $new_order_statuses;
}


function fun2c2p_init()
{
    if (!class_exists('WC_Payment_Gateway')) return;

    //Included required files.
    require_once(dirname(__FILE__) . '/Includes/wc-2c2p-constant.php');
    require_once(dirname(__FILE__) . '/Includes/wc-2c2p-request-helper.php');
    require_once(dirname(__FILE__) . '/Includes/wc-2c2p-hash-helper.php');
    require_once(dirname(__FILE__) . '/Includes/wc-2c2p-validation-helper.php');
    require_once(dirname(__FILE__) . '/Includes/wc-2c2p-meta-data-helper.php');

    //Gateway class    
    class WC_Gateway_2c2p_Hbl extends WC_Payment_Gateway
    {
        //Make __construct()
        public function __construct()
        {

            $this->id = '2c2p'; // ID for WC to associate the gateway values
            $this->method_title = '2C2P HBL'; // Gateway Title as seen in Admin Dashboad
            $this->method_description = '2C2P HBL - Redefining Payments, Simplifying Lives'; // Gateway Description as seen in Admin Dashboad
            $this->has_fields = false; // Inform WC if any fileds have to be displayed to the visitor in Frontend

            $this->init_form_fields(); // defines your settings to WC
            $this->init_settings(); // loads the Gateway settings into variables for WC

            // Special settigns if gateway is on Test Mode
            $test_title = '';
            $test_description = '';
            if (strcasecmp($this->settings['test_mode'], 'demo2') == 0) {
                $demo = '2C2PFrontEnd/';
                $test_description = '<br/><br/> Test Mode is <strong> ACTIVE </strong> Do not use personal detail to pay. Use only test detail for payment. <br/>';
            } else {
                $demo = '';
            }
            //END--test_mode=yes
            $mastercard_img = plugin_dir_url(__FILE__) . 'assets/img/mc_acc.svg';
            $visa_img = plugin_dir_url(__FILE__) . 'assets/img/visa_pos_fc.png';
            $safekey_img = plugin_dir_url(__FILE__) . 'assets/img/amx_sk_bbl_rm_rgb_45px_pref.png';
            $payment_brands_img_description = '<div style="height: 50px">';
            $payment_brands_img_description .= '<img src="' . $mastercard_img . ' " alt="mastercard" style="height: 30px;margin: 10px; float: left; max-height: 30px;" >';
            $payment_brands_img_description .= '<img src="' . $visa_img . ' " alt="visa" style="height: 30px;margin: 10px; float: left; max-height: 30px;" >';
            $payment_brands_img_description .= '<img src="' . $safekey_img . ' " alt="safekey" style="height: 30px;margin: 10px; float: left; max-height: 30px;" >';
            $payment_brands_img_description .= '</div>';

            $this->title = $this->settings['title'] . $test_title; // Title as displayed on Frontend
            $this->description = $this->settings['description'] . $test_description . $payment_brands_img_description; // Description as displayed on Frontend
            //$this->liveurl          = 'https://' . $this->settings['test_mode'] . '.2c2p.com/' . $demo . 'RedirectV3/payment';
            $this->liveurl = 'https://hblpgw.2c2p.com/HBLPGW/Payment/Payment/Payment';
            $this->service_provider = array_key_exists('service_provider', $this->settings) ? $this->settings['service_provider'] : "";
            $this->msg['message'] = '';
            $this->msg['class'] = '';

            add_action('init', array(&$this, 'check_2c2p_response'));
            add_action('woocommerce_api_wchblgateway', array($this, 'check_2c2p_response')); //update for woocommerce >2.0
            add_action('woocommerce_receipt_2c2p', array(&$this, 'receipt_page'));
            add_action('woocommerce_checkout_update_order_meta', array(&$this, 'wc_2c2p_custom_checkout_field_update_order_meta'));

            //Load script's
            //  add_action('wp_enqueue_scripts', array(&$this, 'wc_2c2p_load_scripts'));

            if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options')); //update for woocommerce >2.0
            } else {
                add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options')); // WC-1.6.6
            }

        } //END-__construct    

        /* Validating 123 payment expiry textbox */
        public function validate_wc_2c2p_123_payment_expiry_field($key, $value)
        {
            if (empty($value)) {
                WC_Admin_Settings::add_error(esc_html__('Please enter 123 payment expiry like (8 - 720)', 'woo_2c2p'));
                return $value = 0;
            } else if (!is_numeric($value)) {
                WC_Admin_Settings::add_error(esc_html__('Please enter 123 payment expiry in numeric like (8 - 720)', 'woo_2c2p'));
                return $value = 0;
            } else if (!($value >= 8 && $value <= 720)) {
                WC_Admin_Settings::add_error(esc_html__('Please enter 123 payment expiry in between 8 - 720 hours only', 'woo_2c2p'));
                return $value = 0;
            }

            return $value;
        }

        public function validate_key_id_field($key, $value)
        {
            if (empty($value)) {
                WC_Admin_Settings::add_error(esc_html__('Please Enter Merchant Id', 'woo_2c2p'));
                return $value;
            }
            return $value;
        }

        public function validate_key_secret_field($key, $value)
        {
            if (empty($value)) {
                WC_Admin_Settings::add_error(esc_html__('Please Enter Secret Key', 'woo_2c2p'));
                return $value;
            }
            return $value;
        }

        function wc_2c2p_custom_checkout_field_update_order_meta($order_id)
        {
            if (!isset($_POST['wc_2c2p_stored_card']) && empty($_POST['wc_2c2p_stored_card']))
                return;

            if (!intval($_POST['wc_2c2p_stored_card']))
                return;

            if ($_POST['wc_2c2p_stored_card'] > 0) {
                update_post_meta($order_id, 'wc_2c2p_stored_card_token_id', intval($_POST['wc_2c2p_stored_card']));
            }
        }

        /* load script for 2c2p payment. */
        function wc_2c2p_load_scripts()
        {
            wp_enqueue_script('wc-2c2p-scripts', plugin_dir_url(__FILE__) . 'Includes/wc-2c2p-script.js', array('jquery'), NULL, false);
        }

        public function wc_2c2p_get_setting()
        {
            return $this->settings;
        }

        /* Get the plugin response URL */
        public function wc_2c2p_response_url($order_Id)
        {
            $order = new WC_Order($order_Id);

            $redirect_url = $this->get_return_url($order);
            if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                $redirect_url = add_query_arg('wc-api', get_class($this), $redirect_url);
            }

            return $redirect_url;
        }

        /* Initiate Form Fields in the Admin Backend */
        function init_form_fields()
        {
            $this->form_fields = include(dirname(__FILE__) . '/Includes/wc-2c2p-setting.php');
        }

        /* Admin Panel Options.Show info on Admin Backend */
        public function admin_options()
        {
            echo '<h3>' . esc_html__('2C2P', 'woo_2c2p') . '</h3>';
            echo '<p>' . esc_html__('2C2P provides a wide range of payment. you just save your account detail in it and enjoy shopping just in one click on 2C2P', 'woo_2c2p') . '</p>';
            echo '<p><small><strong>' . esc_html__('Confirm your Mode: Is it LIVE or TEST.', 'woo_2c2p') . '</strong></small></p>';
            echo '<table class="form-table">';
            // Generate the HTML For the settings form.
            $this->generate_settings_html();
            echo '</table>';
        }

        /* There are no payment fields, but we want to show the description if set. */
        function payment_fields()
        {
            if (!empty($this->description)) {
                echo wpautop(wptexturize($this->description));

                if (is_user_logged_in()) {
                    if (strcasecmp($this->settings['wc_2c2p_stored_card_payment'], "yes") == 0) {
                        $strHtml = "";
                        $wc_2c2p_stored_card = get_user_meta(get_current_user_id(), "wc_2c2p_stored_card");

                        foreach ($wc_2c2p_stored_card as $key => $value) {
                            foreach ($value as $innerKey => $innerValue) {
                                $strHtml .= "<option value='" . esc_attr(key($value)) . "'>" . esc_attr(key($innerValue)) . "</option>";
                            }
                        }

                        if (!empty($strHtml)) {
                            echo "<table id='tblToken'>";
                            echo "<tr>";
                            echo "<th style='width:140px;'>Select my card</th>";
                            echo "<td> <select id='wc_2c2p_stored_card' name='wc_2c2p_stored_card' >";
                            echo "<option value='0'>I'll use a new card</option>";
                            echo $strHtml;
                            echo "</select></td>";
                            echo "<td><input type='button' id='btn_2c2p_remove' name='btn_2c2p_remove' value='Remove Card' style='display:none;' ></td>";
                            echo "</tr>";
                            echo "</table>";
                        }
                        echo "<input type='hidden' value='" . esc_url(admin_url('admin-ajax.php')) . "' id='ajax_url' />";
                    }
                }
            }
        }

        /* Receipt Page */
        function receipt_page($order)
        {
            echo $this->generate_2c2p_form($order);
        }

        /* Generate button link */
        function generate_2c2p_form($order_id)
        {

            global $woocommerce;
            $order = new WC_Order($order_id);

            if (is_user_logged_in()) { // Customer is loggedin.
                $loggedin_user_data = wp_get_current_user();
                $cust_email = $loggedin_user_data->data->user_email;
            } else {
                $cust_email = $order->data['billing']['email']; //Gust customer.
            }
            $product_name = '';
            foreach ($order->get_items() as $item) {
                $product_name .= $item['name'] . ', ';
            }

            $product_name = (strlen($product_name) > 0) ? substr($product_name, 0, strlen($product_name) - 2) : "";
            $product_name .= '.';
            $product_name = filter_var($product_name, FILTER_SANITIZE_STRING);

            $fun2c2p_args = array(
                'payment_description' => $product_name,
                'order_id' => $order_id,
                'invoice_no' => $order_id,
                'amount' => $order->order_total,
                'customer_email' => sanitize_email($cust_email),
                // 'stored_card_unique_id' => $wc_2c2p_stored_card_token_id != 0 ? $wc_2c2p_stored_card_token_id : "",
                // 'default_lang' => $default_lang
            );

            $objWC_2C2P_Validation_Helper = new WC_2C2P_Validation_Helper();
            $isValid = $objWC_2C2P_Validation_Helper->wc_2c2p_is_valid_merchant_request($fun2c2p_args);

            if (!$isValid) {
                foreach ($objWC_2C2P_Validation_Helper->wc_2c2p_error as $key => $value) {
                    echo esc_html__($value, 'woo_2c2p');
                }

                echo '</br>';
                echo '&nbsp;&nbsp;&nbsp;&nbsp<a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . esc_html__('Cancel order &amp; restore cart', 'woo_2c2p') . '</a>';
                return;
            } else {

                echo '<p><strong>' . esc_html__('Thank you for your order.', 'woo_2c2p') . '</strong> <br/>' . esc_html__('The payment page will open if you click on button "Pay".', 'woo_2c2p') . '</p>';
            }

            $fun2c2p_args['amount'] = $objWC_2C2P_Validation_Helper->wc_2c2p_validate_currency_exponent($fun2c2p_args['amount']);

            $objwc_2c2p_construct_request = new wc_2c2p_construct_request_helper();
            $wc_2c2p_form_field = $objwc_2c2p_construct_request->wc_2c2p_construct_request(is_user_logged_in(), $fun2c2p_args);

            $strHtml = '';
            $strHtml .= '<form action="' . esc_url($this->liveurl) . '" method="post" id="2c2p_payment_form">';
            $strHtml .= $wc_2c2p_form_field;
            $strHtml .= '<input type="submit" class="button-alt" id="submit_2c2p_payment_form" value="' . esc_html__('Pay', 'woo_2c2p') . '" />';
            $strHtml .= '&nbsp;&nbsp;&nbsp;&nbsp<a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . esc_html__('Cancel order &amp; restore cart', 'woo_2c2p') . '</a>';
            $strHtml .= '</form>';

            return $strHtml;
        }

        //Process the payment and return the result         
        function process_payment($order_id)
        {

            global $woocommerce;
            $order = new WC_Order($order_id);

            if (version_compare(WOOCOMMERCE_VERSION, '2.1.0', '>=')) { // For WC 2.1.0
                $checkout_payment_url = $order->get_checkout_payment_url(true);
            } else {
                $checkout_payment_url = get_permalink(get_option('woocommerce_pay_page_id'));
            }
            return array(
                'result' => 'success',
                // 'redirect' =>esc_url($this->liveurl)
                'redirect' => add_query_arg('order', $order->get_, add_query_arg('key', $order->order_key, $checkout_payment_url))
            );
        }

        /* handle the PG response */
        function check_2c2p_response()
        {
            global $woocommerce;
            $logger = wc_get_logger();
            $logger->debug(json_encode($_REQUEST));
            $hash = sanitize_text_field($_REQUEST['hashValue']);
            $transaction_ref = isset($_REQUEST['tranRef']) ? sanitize_text_field($_REQUEST['tranRef']) : "";
            $payment_status = isset($_REQUEST['status']) ? sanitize_text_field($_REQUEST['status']) : "";
            $payment_amount = isset($_REQUEST['amount']) ? sanitize_text_field($_REQUEST['amount']) : "";
            $respCode = isset($_REQUEST['respCode']) ? sanitize_text_field($_REQUEST['respCode']) : "";
            $fraudCode = isset($_REQUEST['fraudCode']) ? sanitize_text_field($_REQUEST['fraudCode']) : "";
            $approvalCode = isset($_REQUEST['approvalCode']) ? sanitize_text_field($_REQUEST['approvalCode']) : "";
            $eci = isset($_REQUEST['eci']) ? sanitize_text_field($_REQUEST['eci']) : "";
            $transaction_datetime = isset($_REQUEST['dateTime']) ? sanitize_text_field($_REQUEST['dateTime']) : "";
            $pan = isset($_REQUEST['pan']) ? sanitize_text_field($_REQUEST['pan']) : "";


            if (isset($_REQUEST['invoiceNo']) && isset($_REQUEST['paymentGatewayID'])) {

                $order_id = wc_sanitize_order_id(sanitize_text_field($_REQUEST['invoiceNo']));

                if (!empty($order_id)) {
                    try {
                        $order = new WC_Order($order_id);
                        $order->add_order_note(json_encode($_REQUEST));
                        exit;

                        /*Sanitize fields*/
                        $hash = sanitize_text_field($_REQUEST['hashValue']);
                        $transaction_ref = isset($_REQUEST['tranRef']) ? sanitize_text_field($_REQUEST['tranRef']) : "";
                        $payment_status = isset($_REQUEST['status']) ? sanitize_text_field($_REQUEST['status']) : "";
                        $payment_amount = isset($_REQUEST['amount']) ? sanitize_text_field($_REQUEST['amount']) : "";
                        $respCode = isset($_REQUEST['respCode']) ? sanitize_text_field($_REQUEST['respCode']) : "";
                        $fraudCode = isset($_REQUEST['fraudCode']) ? sanitize_text_field($_REQUEST['fraudCode']) : "";
                        $approvalCode = isset($_REQUEST['approvalCode']) ? sanitize_text_field($_REQUEST['approvalCode']) : "";
                        $eci = isset($_REQUEST['eci']) ? sanitize_text_field($_REQUEST['eci']) : "";
                        $transaction_datetime = isset($_REQUEST['dateTime']) ? sanitize_text_field($_REQUEST['dateTime']) : "";
                        $pan = isset($_REQUEST['pan']) ? sanitize_text_field($_REQUEST['pan']) : "";
                        //$stored_card_unique_id = isset($_REQUEST['stored_card_unique_id']) ? sanitize_text_field($_REQUEST['stored_card_unique_id']) : "" ;

                        $objwc_2c2p_is_valid_hash = new wc_2c2p_hash_helper();
                        $isValidHash = $objwc_2c2p_is_valid_hash->wc_2c2p_is_valid_hash($_REQUEST);

                        $trans_authorised = false;

                        if ($order->status !== 'completed') {

                            if ($isValidHash) {
                                //Save data to meta data table.
                                $objWC_2C2P_Meta_Data_Helper = new WC_2C2P_Meta_Data_Helper();
                                $objWC_2C2P_Meta_Data_Helper->wc_2c2p_store_response_meta($_REQUEST);

                                if (strcasecmp(strtoupper($payment_status), "AP") == 0) { //Success using credit/debit card (Authorized) or Success when paid with cash channel (Paid)
                                    $isFounded = false;

                                    //Stored stored card toek into user meta table with loggedin users only.
                                    /*if (is_user_logged_in() || $order->user_id > 0) {
                                        $stored_card = get_user_meta($order->user_id,"wc_2c2p_stored_card");

                                        $stored_card_data = array($order_id => array($masked_pan  => $stored_card_unique_id));

                                         if (empty($stored_card)) {
                                             if (!empty($_REQUEST['stored_card_unique_id'])) {
                                                 add_user_meta($order->user_id, "wc_2c2p_stored_card", $stored_card_data);
                                             }
                                         }
                                         else {
                                             foreach ($stored_card as $key => $value) {
                                                 foreach ($value as $innerKey => $innerValue) {
                                                     if (array_key_exists('masked_pan', $_REQUEST) && array_key_exists('stored_card_unique_id', $_REQUEST)) {
                                                         if ((strcasecmp(Key($innerValue), $_REQUEST['masked_pan']) == 0 && strcasecmp($innerValue[key($innerValue)], $_REQUEST['stored_card_unique_id']) == 0)) {
                                                             $isFounded = true;
                                                             break;
                                                         }
                                                     }
                                                 }
                                             }

                                             if (!$isFounded) {
                                                 if (!empty($_REQUEST['masked_pan']) && !empty($_REQUEST['stored_card_unique_id'])) {
                                                     add_user_meta($order->user_id, "wc_2c2p_stored_card", $stored_card_data);
                                                 }
                                             }
                                         }
                                    }*/
                                    $trans_authorised = true;

                                    if ((int)$order->order_total > (int )$payment_amount) {
                                        $remaining_total = (int)$order->total - (int)$payment_amount;
                                        $order->add_order_note('HBL 2C2P payment transaction successful but full payment not done! <br/>order_id: ' . $order_id . '<br/>transaction_ref: ' . $transaction_ref . '<br/>eci: ' . $eci . '<br/>transaction_datetime: ' . $transaction_datetime . '<br/>approval_code: ' . $approvalCode . '<br/>fraud code:' . $fraudCode . '<br/>approval Code' . $approvalCode . '<br/>pan' . $pan . '<br/>response Code' . $respCode);

                                        $order->set_total((string)$remaining_total);
                                        $order->save();
                                        $woocommerce->cart->empty_cart();

                                        // $this->redirect_to_checkout($order);
                                    } else {
                                        $this->msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful.";
                                        $this->msg['class'] = 'woocommerce-message';

                                        if (strcasecmp($order->status, 'processing') == 0) {
                                            $order->add_order_note('order_id: ' . $order_id . '<br/>transaction_ref: ' . $transaction_ref . '<br/>payment status: ' . $payment_status . '<br/>amount: ' . $payment_amount . '<br/>eci: ' . $eci . '<br/>transaction_datetime: ' . $transaction_datetime . '<br/>approval_code: ' . $approvalCode . '<br/>fraud code:' . $fraudCode . '<br/>approval Code' . $approvalCode . '<br/>pan' . $pan . '<br/>response Code' . $respCode);
                                            $order->update_status('processing');
                                        } else {
                                            $order->update_status('processing');
                                            $order->payment_complete();

                                            $order->add_order_note('HBL 2C2P payment transaction successful.<br/>order_id: ' . $order_id . '<br/>transaction_ref: ' . $transaction_ref . '<br/>eci: ' . $eci . '<br/>transaction_datetime: ' . $transaction_datetime . '<br/>approval_code: ' . $approvalCode . '<br/>fraud code:' . $fraudCode . '<br/>approval Code' . $approvalCode . '<br/>pan' . $pan . '<br/>response Code' . $respCode);

                                            $woocommerce->cart->empty_cart();
                                        }
                                    }
                                } else if (strcasecmp(strtoupper($payment_status), "PE") == 0) { // Pending (Waiting customer to pay)
                                    $trans_authorised = true;
                                    //$this->msg['message'] = "Thank you for shopping with us. Right now your payment status is pending. We will keep you posted regarding the status of your order through eMail";
                                    //$this->msg['class'] = 'woocommerce-info';

                                    $order->add_order_note('HBL 2C2P payment status is pending<br/>order_id: ' . $order_id . '<br/>transaction_ref: ' . $transaction_ref . '<br/>eci: ' . $eci . '<br/>transaction_datetime: ' . $transaction_datetime . '<br/>approval_code: ' . $approvalCode . '<br/>fraud code:' . $fraudCode . '<br/>approval Code' . $approvalCode . '<br/>pan' . $pan . '<br/>response Code' . $respCode);

                                    $order->update_status('awaiting-payment');
                                    $woocommerce->cart->empty_cart();
                                } else {
                                    //Rejected,User cancel or Error.
                                    // $this->msg['class'] = 'woocommerce-error';
                                    //$this->msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";

                                    $order->add_order_note('order_id: ' . $order_id . '<br/>transaction_ref: ' . $transaction_ref . '<br/>eci: ' . $eci . '<br/>transaction_datetime: ' . $transaction_datetime . '<br/>approval_code: ' . $approvalCode . '<br/>fraud code:' . $fraudCode . '<br/>approval Code' . $approvalCode . '<br/>pan' . $pan . '<br/>response Code' . $respCode);
                                }
                            } else {
                                //$this->msg['class'] = 'error';
                                // $this->msg['message'] = "Security Error. Illegal access detected.";
                                $order->add_order_note('Checksum ERROR: ' . json_encode($_REQUEST));
                                $order->update_status('failed');
                            }
                            if (!$trans_authorised) {
                                $order->update_status('cancelled');
                            }
                        } else {
                            $logger->info('Order ' . $order_id . ' already complete');
                        }
                    } catch (Exception $e) {
                        $logger->alert('Error');
                        $logger->debug($e);
                    }
                } else {
                    $logger->alert('order id empty');
                }

                /*  if (strcasecmp(strtoupper($payment_status), "AP") == 0 || strcasecmp(strtoupper($payment_status), "PE") == 0) {
                      $redirect_url = $this->get_return_url($order);
                  } else {
                      $redirect_url = get_site_url() . "/cart";
                  }

                  if (version_compare(WOOCOMMERCE_VERSION, '2.1.0', '>=')) { // For WC 2.1.0
                      $checkout_payment_url = $order->get_checkout_payment_url(true);
                  } else {
                      $checkout_payment_url = get_permalink(get_option('woocommerce_pay_page_id'));
                  }*/

                // wp_redirect($redirect_url);
                exit;
            } else {
                $logger->alert('invoiceNo and paymentGatewayID not found in response');
            }


        }

        public function thanku_page()
        {
        }

    } //END-class

    add_action('wp_ajax_paymentajax', 'wp_2c2p_remove_stored_card_Id_ajax');
    add_action('wp_ajax_nopriv_paymentajax', 'wp_2c2p_remove_stored_card_Id_ajax');

    function wp_2c2p_remove_stored_card_Id_ajax()
    {

        $data = $_POST['data'];

        if (!isset($data['token_id']) || !intval($data['token_id'])) {
            echo "0";
            die;
        }

        $isFounded = false;
        $wc_2c2p_stored_card = get_user_meta(get_current_user_id(), "wc_2c2p_stored_card");

        foreach ($wc_2c2p_stored_card as $key => $value) {
            foreach ($value as $innerKey => $innerValue) {
                if (strcasecmp(key($value), $data['token_id']) == 0) {
                    $stored_card_data = array($data['token_id'] => array(key($innerValue) => $innerValue[key($innerValue)]));
                    $isFounded = true;
                    break;
                }
            }
        }

        if ($isFounded) {
            echo delete_user_meta(get_current_user_id(), 'wc_2c2p_stored_card', $stored_card_data);
            die;
        } else {
            echo "0";
            die;
        }
    }

//Add the Gateway to WooCommerce
    function woocommerce_add_gateway_2c2p_gateway($methods)
    {
        $methods[] = 'WC_Gateway_2c2p_Hbl';
        return $methods;
    } //END-wc_add_gateway

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_2c2p_gateway');

} //END-init

//Virasat Solutions 'Settings' link on plugin page
add_filter('plugin_action_links', 'fun2c2p_add_action_plugin', 10, 5);
function fun2c2p_add_action_plugin($actions, $plugin_file)
{
    static $plugin;

    if (!isset($plugin))
        $plugin = plugin_basename(__FILE__);
    if ($plugin == $plugin_file) {

        $settings = array(
            'settings' => '<a href="admin.php?page=wc-settings&tab=checkout&section=WC_Gateway_2c2p_Hbl">' . esc_html__('Settings', 'woo_2c2p') . '</a>'
        );

        $actions = array_merge($settings, $actions);
    }

    return $actions;
} //END-settings_add_action_link


function redirect_to_checkout($order)
{

    if (version_compare(WOOCOMMERCE_VERSION, '2.1.0', '>=')) { // For WC 2.1.0
        $checkout_payment_url = $order->get_checkout_payment_url(true);
    } else {
        $checkout_payment_url = get_permalink(get_option('woocommerce_pay_page_id'));
    }
    $url = add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, $checkout_payment_url));
    wp_redirect($url);
    exit;
}
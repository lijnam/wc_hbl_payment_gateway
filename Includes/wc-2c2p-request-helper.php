<?php

class wc_2c2p_construct_request_helper extends WC_Payment_Gateway
{
    private $pg_2c2p_setting_values;

    function __construct()
    {
        $objWC_Gateway_2c2p_Hbl = new WC_Gateway_2c2p_Hbl();
        $this->pg_2c2p_setting_values = $objWC_Gateway_2c2p_Hbl->wc_2c2p_get_setting();
    }

    private $wc_2c2p_form_fields = array(
        "paymentGatewayID" => "",
        "invoiceNo" => "",
        "productDesc" => "",
        "amount" => "",
        "currencyCode" => "",
        "user_defined_1" => "",
        "user_defined_2" => "",
        "user_defined_3" => "",
        "user_defined_4" => "",
        "user_defined_5" => "",
        "nonSecure" => "N",
        "hashValue" => "",
       // "result_url_1" => "",
       // "result_url_2" => "",

    );

    public function wc_2c2p_construct_request($isloggedin, $paymentBody, $payment_page = false)
    {
        $this->wc_2c2p_123_payment_expiry($paymentBody);
        $this->wc_2c2p_create_common_form_field($paymentBody);

        //Create obj of hash helper class.
        $objwc_2c2p_hash_helper = new wc_2c2p_hash_helper();
        $hashValue = $objwc_2c2p_hash_helper->wc_2c2p_create_hashvalue($this->wc_2c2p_form_fields);

        //$this->wc_2c2p_form_fields['hash_value'] = $hashValue;
        $this->wc_2c2p_form_fields['hashValue'] = $hashValue;
        $strHtml = "";
        foreach ($this->wc_2c2p_form_fields as $key => $value) {
            if (!empty($value)) {
                if ($payment_page && $key == 'invoiceNo') {
                    $strHtml .= '<input type="hidden" name="' . esc_attr($key) . '" value="re' . esc_attr($value) . '">';
                } else {
                    $strHtml .= '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                }
            }
        }

        //$strHtml .= '<input type="hidden" name="request_3ds" value="">';

        return $strHtml;
    }

    function wc_2c2p_create_common_form_field($paymentBody)
    {

        $objWC_Gateway_2c2p_Hbl = new WC_Gateway_2c2p_Hbl();
        // $redirect_url = $objWC_Gateway_2c2p_Hbl->wc_2c2p_response_url($paymentBody['order_id']);
        $merchant_id = isset($this->pg_2c2p_setting_values['key_id']) ? sanitize_text_field($this->pg_2c2p_setting_values['key_id']) : "";
        // $secret_key = isset($this->pg_2c2p_setting_values['key_secret']) ? sanitize_text_field($this->pg_2c2p_setting_values['key_secret']) : "";

        $default_lang = WC_2C2P_Constant::WC_2C2P_VERSION;
        $selected_lang = sanitize_text_field($this->pg_2c2p_setting_values['wc_2c2p_default_lang']);
        // $default_lang = !empty($selected_lang) ? $selected_lang : $default_lang;

        //Get is store currency code from woocommerece.
        $currency = sanitize_text_field($this->wc_2c2p_get_store_currency_code());
        //var_dump($currency);exit;
        $payment_description = $paymentBody['payment_description'];
        // $order_id = $paymentBody['order_id'];
        $invoice_no = $paymentBody['invoice_no'];
        $amount = str_pad($paymentBody['amount'], 12, '0', STR_PAD_LEFT);
        // $customer_email = $paymentBody['customer_email'];
        //   $result_url_1 = $redirect_url;
        // $result_url_2 = $redirect_url;

        /*    //Create key value pair array.
            $this->wc_2c2p_form_fields["version"] = WC_2C2P_Constant::WC_2C2P_VERSION;
            $this->wc_2c2p_form_fields["merchant_id"] = $merchant_id;
            $this->wc_2c2p_form_fields["payment_description"] = $payment_description;
            $this->wc_2c2p_form_fields["order_id"] = $order_id;
            $this->wc_2c2p_form_fields["invoice_no"] = $invoice_no;
            $this->wc_2c2p_form_fields["currency"] = $currency;
            $this->wc_2c2p_form_fields["amount"] = $amount;
            $this->wc_2c2p_form_fields["customer_email"] = $customer_email;
            $this->wc_2c2p_form_fields["result_url_1"]   = $result_url_1; // Specify by plugin
            $this->wc_2c2p_form_fields["result_url_2"]   = $result_url_2; // Specify by plugin
            $this->wc_2c2p_form_fields["payment_option"]   = "A"; // Pass by default Payment option as A
            $this->wc_2c2p_form_fields["default_lang"]   = $default_lang; // Set language.*/

        //Create key value pair array.
        //$this->wc_2c2p_form_fields["version"] = WC_2C2P_Constant::WC_2C2P_VERSION;
        $this->wc_2c2p_form_fields["paymentGatewayID"] = $merchant_id;
        $this->wc_2c2p_form_fields["invoiceNo"] = $invoice_no;
        $this->wc_2c2p_form_fields["productDesc"] = $payment_description;
        $this->wc_2c2p_form_fields["amount"] = $amount;
        //$this->wc_2c2p_form_fields["order_id"] = $order_id;
        $this->wc_2c2p_form_fields["currencyCode"] = $currency;
        //$this->wc_2c2p_form_fields["customer_email"] = $customer_email;
        //$this->wc_2c2p_form_fields["result_url_1"] = $result_url_1; // Specify by plugin
        //$this->wc_2c2p_form_fields["result_url_2"] = $result_url_2; // Specify by plugin
        // $this->wc_2c2p_form_fields["payment_option"]   = "A"; // Pass by default Payment option as A
        // $this->wc_2c2p_form_fields["default_lang"]   = $default_lang; // Set language.

    }

    function wc_2c2p_123_payment_expiry($paymentBody)
    {
        $payment_expiry = $this->pg_2c2p_setting_values['wc_2c2p_123_payment_expiry'];

        if (!isset($payment_expiry) || empty($payment_expiry) || !is_numeric($payment_expiry)) {
            //Set default 123 payment expiry. If validation is failed from merchant configuration sections.
            $payment_expiry = 8;
        }

        if (!($payment_expiry >= 8 && $payment_expiry <= 720)) {
            //Set default 123 payment expiry. If validation is failed from merchant configuration sections.
            $payment_expiry = 8;
        }

        $date = date("Y-m-d H:i:s");
        $strTimezone = date_default_timezone_get();
        $date = new DateTime($date, new DateTimeZone($strTimezone));
        $date->modify("+" . $payment_expiry . "hours");
        $payment_expiry = $date->format("Y-m-d H:i:s");

        //$this->wc_2c2p_form_fields["payment_expiry"] = $payment_expiry;
    }

//Get the store currency code.
    function wc_2c2p_get_store_currency_code()
    {// Geolocation must be enabled @ Woo Settings

        $location = WC_Geolocation::geolocate_ip();
        if (strtoupper($location['country']) == "NP") {
            return 524;
        } else {
            return 840;
        }
        /*  $objWC_2c2p_currency = new WC_2c2p_currency();
         $currenyCode = get_option('woocommerce_currency');


        if (!isset($currenyCode))
             return "";
         foreach ($objWC_2c2p_currency->get_currency_code() as $key => $value) {
             // echo($key.'='.$currenyCode.'?');echo($key == $currenyCode?'true':'false');echo('<br>');
             if ($key == $currenyCode) {
                 //echo('asdds'); exit();

                 // var_dump($value['Num']);exit;

                 return $value['Num'];
             }
             return "";
         }*/
    }
}

?>
<?php

class wc_2c2p_hash_helper
{

    private $params;
    private $hashValue;
    private $pg_2c2p_setting_values;

    function __construct()
    {
        $objWC_Gateway_2c2p_Hbl = new WC_Gateway_2c2p_Hbl();
        $this->pg_2c2p_setting_values = $objWC_Gateway_2c2p_Hbl->wc_2c2p_get_setting();
    }

    //This function is used to check hash value is valid or not.
    function wc_2c2p_is_valid_hash($parameter)

    {
        if (array_key_exists('paymentGatewayID', $parameter)) $this->params .= $parameter['paymentGatewayID'];
        if (array_key_exists('respCode', $parameter)) $this->params .= $parameter['respCode'];
        if (array_key_exists('fraudCode', $parameter)) $this->params .= $parameter['fraudCode'];
        if (array_key_exists('pan', $parameter)) $this->params .= $parameter['pan'];
        if (array_key_exists('amount', $parameter)) $this->params .= $parameter['amount'];
        if (array_key_exists('invoiceNo', $parameter)) $this->params .= $parameter['invoiceNo'];
        if (array_key_exists('tranRef', $parameter)) $this->params .= $parameter['tranRef'];
        if (array_key_exists('approvalCode', $parameter)) $this->params .= $parameter['approvalCode'];
        if (array_key_exists('eci', $parameter)) $this->params .= $parameter['eci'];
        if (array_key_exists('dateTime', $parameter)) $this->params .= $parameter['dateTime'];
        if (array_key_exists('status', $parameter)) $this->params .= $parameter['status'];


        $secret_key = $this->pg_2c2p_setting_values['key_secret'];

        $hash = hash_hmac('SHA256', $this->params, $secret_key, false); // Generate the hash value.

        if (strcasecmp($hash, $parameter['hashValue']) == 0) return true;

        return false;
    }

    //This function is used to create hash value and sent to 2c2p PG.
    function wc_2c2p_create_hashvalue($parameter)
    {
        if (array_key_exists('merchantID', $parameter)) {
            if (!empty($parameter['merchantID'])) $this->hashValue .= $parameter['merchantID'];
        }
        if (array_key_exists('invoiceNumber', $parameter)) {
            if (!empty($parameter['invoiceNumber'])) $this->hashValue .= $parameter['invoiceNumber'];
        }
        if (array_key_exists('amount', $parameter)) {
            if (!empty($parameter['amount'])) $this->hashValue .= $parameter['amount'];
        }
        if (array_key_exists('currencyCode', $parameter)) {
            if (!empty($parameter['currencyCode'])) $this->hashValue .= $parameter['currencyCode'];
        }
        if (array_key_exists('nonSecure', $parameter)) {
            if (!empty($parameter['nonSecure'])) $this->hashValue .= $parameter['nonSecure'];
        }

        $SECRETKEY = esc_attr($this->pg_2c2p_setting_values['key_secret']);

        // Generate the hash value.
        return urlencode(strtoupper(hash_hmac('SHA256', $this->hashValue, $SECRETKEY, false)));
    }
}

?>
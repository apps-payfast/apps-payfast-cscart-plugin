<?php

/* * *************************************************************************
 *                                                                          *
 *  This addon has been written by
 *  APPS Technology Team
 *  
 *  Avanza Premier Payment Services
 *     
 * ************************************************************************** */

use Tygh\Registry;

$source = isset($_REQUEST['dispatch']) ? $_REQUEST['dispatch'] : '';

/**
 * If admin tries to pay, block the action.
 * 
 */
if ($source == "order_management.place_order") {
    $adminorderurl = fn_url("?dispatch=order_management.update", 'A');
    fn_set_notification("W", "Action not allowed", "Admin is not allowed to perform order payments.");
    fn_redirect($adminorderurl);
    exit;
}

if (defined('PAYMENT_NOTIFICATION')) {


    if ($mode == 'notify') {
        $redirect = isset($_REQUEST['redirect']) ? $_REQUEST['redirect'] : '';
        $orderid = isset($_REQUEST['order_id']) ? $_REQUEST['order_id'] : '';


        if ($redirect == "Y") {
            fn_order_placement_routines('route', $orderid, array(), true, AREA);
        } else {
            fn_order_placement_routines('route', $orderid, array(), true, AREA, false);
        }
        fn_clear_cart($cart, true);
    }

    if ($mode == "update") {

        require_once 'app/addons/apps/apps_auth.php';

        $redirect = isset($_REQUEST['redirect']) ? $_REQUEST['redirect'] : '';
        $basketid = isset($_REQUEST['basket_id']) ? $_REQUEST['basket_id'] : '';
        $apps_status_msg = isset($_REQUEST['err_msg']) ? $_REQUEST['err_msg'] : '';
        $apps_transactionid = isset($_REQUEST['transaction_id']) ? $_REQUEST['transaction_id'] : '';
        $apps_statuscode = isset($_REQUEST['err_code']) ? $_REQUEST['err_code'] : '';
        $apps_rdv_key = isset($_REQUEST['Rdv_Message_Key']) ? $_REQUEST['Rdv_Message_Key'] : '';
        
        if ($basketid == '') {
            $order_id = isset($_REQUEST['order_id']) ? $_REQUEST['order_id'] : '';
        } else {
            $order_id = (strpos($basketid, '_')) ? substr($basketid, 0, strpos($basketid, '_')) : $basketid;
        }


        fn_payments_set_company_id($order_id);


        $db_orderinfo = db_get_row("SELECT ?:orders.payment_id as payment_id, "
                . "?:orders.status  as status FROM ?:orders WHERE ?:orders.order_id = ?i", $order_id);

        $payment_id = $db_orderinfo['payment_id'];
        $processor_data = fn_get_payment_method_data($payment_id);

        $orderstatus = $db_orderinfo['status'];

        /**
         * if order has already been paid
         * 
         */
        if ($orderstatus == "C" || $orderstatus == "A" || $orderstatus == "P") {
            $default_url = fn_url("index.php", 'C');
            echo "<head><meta http-equiv='refresh' content='0; url=" . $default_url . "'></head><body></body>";
            exit;
        }

        /**
         * decide the order status if payment failed or succeed, if succeed
         * order status will be "Payment Done"
         */
        $pp_response['order_status'] = ($apps_statuscode == '000' ? 'A' : 'F');


        if ($apps_statuscode == '000') {
            
        } else {
            $pp_response['addons.apps_failure_reason'] = $apps_status_msg;
        }

        /**
         * custom values for APPS Transactions
         */
        $pp_response['transaction_id'] = $apps_transactionid;
        $pp_response['addons.apps_status_code'] = $apps_statuscode;
        $pp_response['addons.apps_rdv_message_key'] = $apps_rdv_key;

        $area = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = 'E'", $order_id);
        $override = ($area == 'A') ? true : false;
        fn_finish_payment($order_id, $pp_response, false);

        $finalurl = fn_url("payment_notification.notify?payment=apps_checkout&order_id=" . $order_id . "&redirect=" . $redirect, 'C');

        if ($redirect == "Y") {
            echo "<head><meta http-equiv='refresh' content='0; url=" . $finalurl . "'></head><body></body>";
        } else {
            curl_request($finalurl, '');
        }
        exit;
    }
} else {

    require_once 'app/addons/apps/apps_auth.php';
    $submit_url = $apps_webcheckout;


    $companydata = Registry::get('runtime.company_data');

    if (!defined('BOOTSTRAP')) {
        die('Access denied');
    }

    $_order_id = ($order_info['repaid']) ? ($order_id . '_' . $order_info['repaid']) : $order_id;

    $order_info = fn_get_order_info($order_id);

    if (isset($order_info['payment_info']['order_status'])) {
        if ($order_info['payment_info']['order_status'] == "C" || $order_info['payment_info']['order_status'] == "A" || $order_info['payment_info']['order_status'] == "P"
        ) {
            $orderurl = fn_url("orders", "C");
            fn_redirect($orderurl);
            exit;
        }
    }

    $signature = md5($processor_data['processor_params']['apps_merchant_id'] . ":" . $processor_data['processor_params']['apps_merchant_key'] . ":" . $order_info['total'] . ":" . $_order_id);
    $merchantname = $processor_data['processor_params']['apps_merchant_name'];

    if (empty($merchantname)) {
        $merchantname = $companydata['company'];
    }

    $auth_token = get_apps_auth_token($processor_data['processor_params']['apps_merchant_id'], $processor_data['processor_params']['apps_merchant_key']);

    $front_redir_url = fn_url("payment_notification.update?order_id=" . $_order_id . "&redirect=Y&payment=apps_checkout&signature=" . $signature, 'C');
    $backend_callback = fn_url("payment_notification.update?order_id=" . $_order_id . "&payment=apps_checkout&signature=" . $signature, 'C');

    if (!empty($auth_token)) {

        $payload = array(
            'MERCHANT_ID' => $processor_data['processor_params']['apps_merchant_id'],
            'MERCHANT_NAME' => $merchantname,
            'TOKEN' => $auth_token,
            'PROCCODE' => 00,
            'TXNAMT' => $order_info['total'],
            'CUSTOMER_MOBILE_NO' => $order_info['phone'],
            'CUSTOMER_EMAIL_ADDRESS' => $order_info['email'],
            'SIGNATURE' => $signature,
            'VERSION' => 'CSCART-APPS-PAYMENT-0.9',
            'TXNDESC' => 'Products purchased from ' . $merchantname,
            'SUCCESS_URL' => urlencode($front_redir_url),
            'FAILURE_URL' => urlencode($front_redir_url),
            'BASKET_ID' => $_order_id,
            'ORDER_DATE' => date('Y-m-d H:i:s', time()),
            'CHECKOUT_URL' => urlencode($backend_callback),
        );

        $order_data = array(
            'order_id' => $order_id,
            'type' => 'E',
            'data' => AREA,
        );
        db_query("REPLACE INTO ?:order_data ?e", $order_data);

        fn_create_payment_form($submit_url, $payload, __("addons.apps_contacting_message"), true); 
        exit;
    } else {
        fn_set_notification("W", __("addons.apps_payment_failed_head"), __("addons.apps_connect_failed"));
    }
}

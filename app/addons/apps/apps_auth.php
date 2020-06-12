<?php

$apps_webcheckout = "https://ipguat.apps.net.pk/Ecommerce/api/Transaction/PostTransaction"; 

/**
 * this file will be included in the payment script
 * to get the APPS auth token to initiate web checkout
 */
function get_apps_auth_token($merchantid, $secret) {
	
    $token_url = "https://ipguat.apps.net.pk/Ecommerce/api/Transaction/GetAccessToken?MERCHANT_ID=".$merchantid."&SECURED_KEY=".$secret; error_log($token_url );
	
    $data = array();
    $jsonpayload = json_encode($data);
    $response = curl_request($token_url, $jsonpayload);
    $response_decode = json_decode($response);
    
    if (isset($response_decode->ACCESS_TOKEN)) {	
        return $response_decode->ACCESS_TOKEN;
    }
    return;
}

/**
 *  curl Request 
 */

function curl_request($url, $data_string) {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'application/json; charset=utf-8    '
    ));
	curl_setopt($ch,CURLOPT_USERAGENT,'CS CART APPS PayFast Plugin');
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

<?php
/**
 *
 *    Copyright (C) 2002-2025 NekoYuzu (MlgmXyysd) All Rights Reserved.
 *    Copyright (C) 2013-2025 MeowCat Studio All Rights Reserved.
 *    Copyright (C) 2020-2025 Meow Mobile All Rights Reserved.
 *
 */

/**
 *
 * ZTE 5G Mobile Wi-Fi U60 Pro MU5250 Debug Port Enabler (openadb)
 *
 * Environment requirement:
 *   - PHP 8.0+
 *   - Curl Extension
 *
 * @author MlgmXyysd
 * @version 1.0
 *
 * All copyright in the software is not allowed to be deleted
 * or changed without permission.
 *
 */

/***********************
 *    Configs Start    *
 ***********************/

// Gateway address
$Gateway = "192.168.0.1";

// Admin password
$Password = "";

/*********************
 *    Configs End    *
 *********************/

/***************************************
 *               WARNING               *
 *    Do NOT modify the codes below    *
 *               WARNING               *
 ***************************************/

/*************************
 *    Functions Start    *
 *************************/

/**
 * Curl HTTP wrapper function
 * @param  $url      string  required  Target url
 * @param  $method   string  required  Request method
 * @param  $fields   array   optional  Request body
 * @param  $header   array   optional  Request header
 * @param  $useForm  bool    optional  Treat request body as urlencoded form
 * @return           array             Curl response
 * @author NekoYuzu (MlgmXyysd)
 * @date   2025/06/05 19:07:05
 */

function http(string $url, string $method, array|string $fields = array(), array $header = array(), bool $useForm = false): array
{
	if ($useForm) {
		$fields = http_build_query($fields);
	} else {
		$fields = json_encode($fields);
	}
    $curl = curl_init();
    curl_setopt_array($curl, array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_CONNECTTIMEOUT => 2,
		CURLOPT_TIMEOUT => 6,
		CURLOPT_CUSTOMREQUEST => $method,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_POST => $method == "POST",
		CURLOPT_POSTFIELDS => $fields,
		CURLOPT_HTTPHEADER => $header
    ));

    $response = curl_exec($curl);
    $info = curl_getinfo($curl);
    $info["errno"] = curl_errno($curl);
    $info["error"] = curl_error($curl);
    $info["request"] = json_encode($fields);
    $info["response"] = $response;
    curl_close($curl);
    return $info;
}

/**
 * HTTP POST wrapper
 * @param  $_api     string  required  Target url
 * @param  $data     array   optional  Request body
 * @param  $header   array   optional  Request header
 * @param  $useForm  bool    optional  Treat request body as urlencoded form
 * @return           array             Curl response
 * @return           false             Response code is not HTTP 200 OK
 * @author NekoYuzu (MlgmXyysd)
 * @date   2025/06/05 19:08:23
 */

function postApi(string $_api, array $data = array(), array $header = array(), bool $useForm = false): array|false
{
    $response = http($_api, "POST", $data, $header, $useForm);
    if ($response["http_code"] != 200) {
        return false;
    }
    return json_decode($response["response"], true);
}

/***********************
 *    Functions End    *
 ***********************/

/********************
 *    Main Logic    *
 ********************/

$sault = postApi("http://" . $Gateway . "/ubus/?t=" . floor(microtime(true) * 1000), array(
	array(
		"jsonrpc" => "2.0",
		"id" => 1,
		"method" => "call",
		"params" => array(
			"00000000000000000000000000000000",
			"zwrt_web",
			"web_login_info",
			array(""=>"")
		)
	)
));

if (!$sault || isset($sault[0]["error"]) || !isset($sault[0]["result"][1]["zte_web_sault"])) {
	echo("Failed to fetch sault: " . PHP_EOL);
	var_dump($sault);
	exit();
}

$sault = $sault[0]["result"][1]["zte_web_sault"];

$_password = strtoupper(hash("sha256", strtoupper(hash("sha256", $Password)) . $sault));

$session = postApi("http://" . $Gateway . "/ubus/?t=" . floor(microtime(true) * 1000), array(
	array(
		"jsonrpc" => "2.0",
		"id" => 2,
		"method" => "call",
		"params" => array(
			"00000000000000000000000000000000",
			"zwrt_web",
			"web_login",
			array(
				"password" => $_password
			)
		)
	)
));

if (!$session || isset($session[0]["error"]) || !isset($session[0]["result"][1]["ubus_rpc_session"])) {
	echo("Failed to log in: " . PHP_EOL);
	var_dump($session);
	exit();
}

$session = $session[0]["result"][1]["ubus_rpc_session"];

var_dump(postApi("http://" . $Gateway . "/ubus/?t=" . floor(microtime(true) * 1000), array(
	array(
		"jsonrpc" => "2.0",
		"id" => 3,
		"method" => "call",
		"params" => array(
			$session,
			"zwrt_bsp.usb",
			"set",
			array(
				"mode" => "debug"
			)
		)
	)
)));
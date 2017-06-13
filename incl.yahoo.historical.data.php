<?php

/*
	Yahoo 
		Get Historical data from Yahoo Finance (chronologically ascending)

	Input
		array(
			'symbol' => "symbol string",
			'start_date' => "m/d/y",
			'end_date' => "m/d/y",
			'additional_days_back' =>  X (offset the start_date by X days)
		)	

	Output
		array(
			"symbol,date,volume,high,low,open,close",
			...
		)
*/
function yahoo($param) {
	
	$cookie = get_cookie();
	$crumb = get_crumb($cookie);
	
	$start = strtotime("{$param['start']} midnight -{$param['offset']} day" );
	$end = strtotime("{$param['end']} midnight +1 day" );

	// get CSV data
	$result = curl(array(
		"url" => "https://query1.finance.yahoo.com/v7/finance/download/{$param['symbol']}?period1={$start}&period2={$end}&interval=1d&events=history&crumb={$crumb}",
		"cookie" => $cookie,
		"header" => false,
		"body" => true
	));	
	
	// clean end of data
	$data = rtrim ($result, "\n\r");

	// split into array
	$arr_data = preg_split ('/$\R?^/m', trim($result));
	
	// remove header
	array_shift($arr_data);

	$arr_temp = array();

	foreach ($arr_data as $row) {
		list($d, $o, $h, $l, $c, $a, $v) = explode(",", $row);

		$factor = $a / $c;
		
		$o = $o * $factor;
		$h = $h * $factor;
		$l = $l * $factor;
		$c = $a * $factor;
		$v = $v / $factor;
		
		$arr_temp[] = "{$param['symbol']},{$d},{$v},{$h},{$l},{$o},{$c}";
	} 
	return $arr_temp;
}


/*
	Curl
	
	Parameters:

		array(
			'url' => "url string",
			'cookie' => "cookie string" | false (no cookie),
			'header' => true (returns header) | false,
			'body' =>  true (returns body) | false
		)
*/
function curl($param) {
	$ch = curl_init($param["url"]);

	if ($param["cookie"]) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			$param["cookie"]
		));
	}

	curl_setopt($ch, CURLOPT_NOBODY, (! $param["body"]));

	
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	// return headers
	if ($param["header"]) {
		curl_setopt($ch, CURLOPT_HEADER, 1);
	}
	$result = curl_exec($ch);
	curl_close($ch);
	
	return $result;
}

// get cookie 
function get_cookie() {
	
	$result = curl(array(
		"url" => "https://finance.yahoo.com",
		"cookie" => false,
		"header" => true,
		"body" => false
	));	

	$cookies = array();
	$cookieParts = array();
	preg_match_all('/Set-Cookie:(?<cookie>\s{0,}.*)$/im', $result, $cookies);
	foreach ($cookies[0] as $cookie) {
		preg_match_all('/Set-Cookie:\s{0,}(B=.*?);/im', $cookie, $cookieParts);
	} 
	return "Cookie: " . $cookieParts[1][0];
}

// get crumb
function get_crumb($cookie) {
	
	$result = curl(array(
		"url" => "https://query1.finance.yahoo.com/v1/test/getcrumb",
		"cookie" => $cookie,
		"header" => false,
		"body" => true
	));	
	
	return ($result);
}

?>
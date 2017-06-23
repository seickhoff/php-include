<?php

/*
	Yahoo 
		Get Historical data from Yahoo Finance (chronologically ascending)

	Input
		array(
			'symbol' => "symbol string",
			'start' => "m/d/y",
			'end' => "m/d/y",
			'offset' =>  X, 
			'columns' => array('s', 'd', 'v', 'h', 'l', 'o', 'c', 'a')	
		)
		
		NOTES: 
			- optional: use 'offset' to set the start date X days prior.
			- optional: use 'columns' to indicate the fields and order to return.
				- default: s (symbol), d (date), v (volume), 'h', (high), 'l' (low), 'o' (open), 'c' (close)
			- The Adjusted Close is used to correct the other values.
	Output
		array of CSV rows in chronologically ascending order
*/
function yahoo($param) {
	
	$cookie = get_cookie();
	$crumb = get_crumb($cookie);
	
	// defaults
	$symbol = (isset($param['symbol'])) ? $param['symbol'] : 'AAPL';

	$end = (isset($param['end'])) ? $param['end'] : date('Y-m-d', strtotime("now"));
	$start = (isset($param['start'])) ? $param['start'] : date('Y-m-d', strtotime("{$end} -14 day"));

	$offset = (isset($param['offset'])) ? $param['offset'] : 0;
	$arr_col = (isset($param['columns'])) ? $param['columns'] : array('s', 'd', 'v', 'h', 'l', 'o', 'c');
	
	$start = strtotime("{$start} midnight -{$offset} day" );
	$end = strtotime("{$end} midnight +1 day" );
	
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
		
		$data = array(
			"a" => $a,
			"s" => $param['symbol'],
			"d" => $d,
			"o" => $o,
			"c" => $c,
			"h" => $h,
			"l" => $l,
			"v" => $v
		); 
		
		$line = array();
		
		foreach ($arr_col as $col) {
			$line[] = $data[$col];
		}
		
		$arr_temp[] = join(',', $line);
		
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
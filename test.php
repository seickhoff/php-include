<?php

include("incl.yahoo.historical.data.php");


$symbol = "ABT";
$start = "01/01/2017";
$end = "06/23/2017";
$additionalDaysBack = 0;


$arr_data = yahoo(array(
	"symbol" => $symbol, 
	"start" => $start, 
	"end" => $end, 
	"offset" => $additionalDaysBack,
	"columns" => array(
		"d", "o", "h", "l", "a"
	)
));

print_r($arr_data);

?>
<?php

/*
	Scott Eickhoff
	Technical Analysis
*/

function average($array) {
	return array_sum($array) / count($array);
}

function SMA ($array_ref, $days, $index, $decimal_places) {
	$width = array();
	$running = array();
	$ma_count = 0;
	$format = '%1.' . $decimal_places . 'f';

	foreach ($array_ref as $ele)	{

		$data = explode(",", $ele);

		if (count($width) < $days) {
			$width[] = $data[$index];
			if (count($width) == $days) { 
				$ma_count = average($width); 
			}
		}
		else {
			array_shift($width);
			$width[] = $data[$index];
			$ma_count = average($width);
		}
		
		$running[] = sprintf($format, $ma_count); 
	}
	return($running);
}




function EMA ($array_ref, $days, $index, $decimal_places) {
	$width = array();
	$running = array();
	$previous_ema = 0;
	$ma_count = 0;
	$format = '%1.' . $decimal_places . 'f';
	
	$k = 2.0 / ($days + 1);
	
	foreach ($array_ref as $ele) {
		$data = explode(",", $ele);

		// prior to the period, build array; at Period calculate sma
		if (count($width) < $days) {
			$width[] = $data[$index];
			if (count($width) == $days) { 
				$ma_count = average($width); 
			}
		}
		// post Period; just calculate ema
		else {
			$ma_count = (($data[$index] - $previous_ema) * $k) +	$previous_ema;
		}
		$previous_ema = $ma_count;
		$running[] = sprintf($format, $ma_count); 
	}
	return($running);
}

function Bollinger ($array_ref, $days, $multiplier, $index, $decimal_places) {
	$width = array();
	global $middle;
	global $upper;
	global $lower;
	$middle = [];
	$upper = [];
	$lower = [];
	$sma = "";
	$upp = "";
	$low = "";
	$format = '%1.' . $decimal_places . 'f';

	foreach ($array_ref as $ele) {
		$data = explode(",", $ele);

		if (count($width) < $days) {
			$width[] = $data[$index];
			if (count($width) == $days) { 
				$sma = average($width); 
				$sum = 0; 
				foreach ($width as $part) {
					$sum += pow(($part - $sma), 2); // Deviations Summed: each part is difference between price and sma, squared
				}
				$std = pow(($sum / $days), 0.5); // Standard Deviation is the square root of Deviations Summed divided by period
				$upp = ($std	* $multiplier) + $sma;
				$low = $sma - ($std	* $multiplier);
			}
		}
		else {
			array_shift ($width);
			$width[] = $data[$index];
			$sma = average($width); 
			$sum = 0;
			foreach ($width as $part) {
				$sum += pow(($part - $sma), 2);
			}
			$std = pow(($sum / $days), 0.5);
			$upp = ($std	* $multiplier) + $sma;
			$low = $sma - ($std	* $multiplier);
		}

		$middle[] = sprintf($format, $sma); 
		$upper[] = sprintf($format, $upp); 
		$lower[] = sprintf($format, $low); 
	}
}

function PriceChannels ($array_ref, $days, $index_high, $index_low, $decimal_places) {
	$h_width = array();
	$l_width = array();
	global $pc_high;
	$pc_high = [];
	global $pc_low;
	$pc_low = [];
	$low = "";
	$high = "";
	$format = '%1.' . $decimal_places . 'f';

	foreach ($array_ref as $ele)	{
		$data = explode(",", $ele);

		if (count($h_width) < $days) {
			$h_width[] = $data[$index_high];
			$l_width[] = $data[$index_low];
			
			if (count($h_width) == $days) { 
				$ordered_high = $h_width;
				rsort($ordered_high, SORT_NUMERIC);
				$ordered_low = $l_width;
				sort($ordered_low, SORT_NUMERIC);
				$low = array_shift($ordered_low);
				$high = array_shift($ordered_high);
			}
		}
		else {
			array_shift($h_width);
			$h_width[] = $data[$index_high];
			array_shift($l_width);
			$l_width[] = $data[$index_low];
			$ordered_high = $h_width;
			rsort($ordered_high, SORT_NUMERIC);
			$ordered_low = $l_width;
			sort($ordered_low, SORT_NUMERIC);
			$low = array_shift($ordered_low);
			$high = array_shift($ordered_high);
		}
		$pc_low[] = sprintf($format, $low); 
		$pc_high[] = sprintf($format, $high); 
	}
}


function MACD ($array_ref, $days_fast, $days_slow, $days_smooth, $index_close, $decimal_places) {
	$format = '%1.' . $decimal_places . 'f';
	
	global $macd;
	global $macd_ema;
	global $divergence;
	
	$macd = [];
	$macd_ema = [];
	$divergence = [];

	$macd_precise = array();
	$padding = 0;

	$ref_fast = EMA($array_ref, $days_fast, $index_close, 9);
	$ref_slow = EMA($array_ref, $days_slow, $index_close, 9);
	
	for ($i = 0; $i < count($ref_fast) ; $i++) {
		if ($ref_fast[$i] == 0 || $ref_slow[$i] == 0 ) {
			$padding++; // don't add the zeros to the array yet since the array will be sent to EMA
		}
		else {
			$macd[] = sprintf($format, ($ref_fast[$i] - $ref_slow[$i]));
			$macd_precise[] = ($ref_fast[$i] - $ref_slow[$i]);
		}
	}

	$ref_smooth = EMA($macd_precise, $days_smooth, 0, 9);
	
	// restore the zeros to the top of array; array count now restored
	for ($j = 1; $j <= $padding; $j++) {
		array_unshift($macd_precise, 0);
		array_unshift($macd, sprintf($format, 0));
		array_unshift($ref_smooth, sprintf($format,	0));
	}
		
	for ($i = 0; $i < count($ref_smooth) ; $i++) {
		if ($ref_smooth[$i] == 0 || $macd_precise[$i] == 0 )
			$divergence[] = sprintf($format, 0);
		else 
			$divergence[] = sprintf($format, ($macd_precise[$i] - $ref_smooth[$i]));
		
		$macd_ema[] = sprintf($format, $ref_smooth[$i]);
	}
}

function true_range ($High, $Low, $yesterday_Close) {
	$tr1 = abs($High - $Low);
	$tr2 = abs($High - $yesterday_Close);
	$tr3 = abs($yesterday_Close - $Low);
	$tr = $tr1;
	if ($tr2 > $tr) 
		$tr = $tr2;
	if ($tr3 > $tr) 
		$tr = $tr3;
	return($tr);
}

function avg_gain ($ref) {
	$loss = null;
	$gain = null;
	foreach ($ref as $ele) {
		if ($ele > 0) 
			$gain += $ele;
	}
	$len = count($ref);
	if (count($ref) > 0) 
		return $gain / $len;
	return -1;
}

function avg_loss ($ref) {
	$loss = null;
	$gain = null;
	foreach ($ref as $ele) {
		if ($ele < 0) 
			$loss += abs($ele);
	}
	$len = count($ref);
	if (count($ref) > 0) 
		return $loss / $len;
	return -1;
}

function RSI ($array_ref, $days, $index, $decimal_places) {

	$format = '%1.' . $decimal_places . 'f';
	
	$previous = array();
	$gains = array();
	$previous_avg_loss = array();
	$previous_avg_gain = array();
	global $rsi;
	$rsi = [];
	$RSI = null;

	// iterate over historical data rows
	for ($i = 0; $i < count($array_ref); $i++) {
		$data = explode(",", $array_ref[$i]);
	
		// Gain
		if (count($previous) < 1) {
			$previous[] = $data[$index];
			if (count($previous) == 1)
				$gain = $data[$index] - $previous[0];
		}
		else {
			$gain = $data[$index] - $previous[0];
			array_shift($previous);
			$previous[] = $data[$index];
		}
			
		// RSI (requires Gain)
		if ($i > 0) {
			if (count($gains) < $i)
				$gains[] = $gain;
				
			// exactly 14 elements in gain history. Example Excel: F14 =AVERAGE(D1:D14)
			if ($i == $days) {
				$avg_loss = avg_loss($gains);
				$avg_gain = avg_gain($gains);
				if ($avg_loss != 0) 
					$RSI = 100 - (100 / ($avg_gain / $avg_loss+ 1));
				else
					$RSI = 100;
				$previous_avg_gain[] = $avg_gain;
				$previous_avg_loss[] = $avg_loss;
			}
			// past the simple average; Excel example: F15 =((F14*13)+D15)/14
			if ($i > $days) {
				$ggain = $gain;
				$lgain = $gain;
				
				if ($gain > 0) 
					$lgain = 0;
				else 
					$ggain = 0;
					
				$avg_gain = (array_shift($previous_avg_gain) * ($days - 1) + $ggain) / $days;
				$avg_loss = (array_shift($previous_avg_loss) * ($days - 1) + abs($lgain)) / $days;
					
				if ($avg_loss != 0)
					$RSI = 100 - (100 / ($avg_gain / $avg_loss + 1));
				else
					$RSI = 100;
					
				$previous_avg_gain[] = $avg_gain;
				$previous_avg_loss[] = $avg_loss;
			}
		}

		$rsi[] = sprintf($format, $RSI);
	}
}

//ADL (arr, h, l, c, v)

function ADL ($array_ref, $index_high, $index_low, $index_close, $index_vol) {
	global $adl;
	$clv = 0;
	$val = 0;
	$total = 0;
	$format = '%1.0f';
				
	// iterate over historical data rows
	for ($i = 0; $i < count($array_ref); $i++) {
		$data = explode(",", $array_ref[$i]);	
		
		$h = $data[$index_high];
		$l = $data[$index_low];
		$c = $data[$index_close];
		$v = $data[$index_vol];
		
		if ($i > 0) {

			if (($h - $l) == 0)
				$clv = 0;
			else 
				$clv = ( ($c - $l) - ($h - $c) ) / ($h - $l);
				
			$val = $v * $clv;
		}
		else 
			$val = 0;
		
		$total += $val;
		$t = sprintf($format, $total);
		$adl[] = $t;
	}
}

function ADX ($array_ref, $days, $index_close, $index_high, $index_low, $decimal_places) {

	$format = '%1.' . $decimal_places . 'f';
	global $DI_plus;
	global $DI_minus;
	global $adx;
	
	$DI_plus = [];
	$DI_minus = [];
	$adx = [];
	
	$tr14 = null;
	$DM14_plus = null;
	$DM14_minus = null;
	$ADX = null;
	$DI14_plus = null;
	$DI14_minus = null;
	$tr_sum = null;
	$DM_plus_sum = null;
	$DM_minus_sum = null;
	$DX_sum = null;
	
	// iterate over historical data rows
	for ($i = 0; $i < count($array_ref); $i++) {
		$data = explode(",", $array_ref[$i]);
		
		########## ADX
		if ($i > 0) {
			$tr = true_range($data[$index_high], $data[$index_low], $yesterday_Close);
			$a = $data[$index_high] - $yesterday_High;
			$b = $yesterday_Low - $data[$index_low];
			$DM_plus = 0;
			$DM_minus = 0;
			if ($a < 0 && $b < 0) { $DM_plus = 0; $DM_minus = 0; }
			else if ($a > $b) { $DM_plus = $a; $DM_minus = 0; }
			else { $DM_plus = 0; $DM_minus = $b; }
		}
		if ($i >= 1 && $i <= $days) {
			$tr_sum = $tr_sum + $tr;
			$DM_plus_sum = $DM_plus_sum + $DM_plus;
			$DM_minus_sum = $DM_minus_sum + $DM_minus;
		}
		if ($i == $days) {
			$tr14 = $tr_sum / $days;
			$DM14_plus = $DM_plus_sum / $days;
			$DM14_minus = $DM_minus_sum / $days;
		}

		if ($i >= ($days + 1)) {
			$tr14 = ($days - 1) * $yesterday_tr14 / $days + $tr / $days;
			$DM14_plus = ($days - 1) * $yesterday_DM14_plus / $days + $DM_plus / $days;
			$DM14_minus= ($days - 1) * $yesterday_DM14_minus / $days + $DM_minus / $days;
		}
		if ($i >= $days) {
			$DI14_plus = 100 * $DM14_plus / $tr14;
			$DI14_minus = 100 * $DM14_minus / $tr14;
			$DI1 = abs($DI14_plus - $DI14_minus);
			$DI2 = $DI14_plus + $DI14_minus;
			$DX = 100 * $DI1 / $DI2;
		}

		if ($i >= $days && $i < ((2 * $days) - 1)) 
			$DX_sum = $DX_sum + $DX;
		if ($i == ((2 * $days) - 1))
			$ADX = $DX_sum / $days;
		if ($i >= (2 * $days))
			$ADX = ($days - 1) * $yesterday_ADX / $days + $DX / $days;
		
		$yesterday_Close = $data[$index_close];
		$yesterday_High = $data[$index_high];
		$yesterday_Low = $data[$index_low];
		$yesterday_tr14 = $tr14;
		$yesterday_DM14_plus = $DM14_plus;
		$yesterday_DM14_minus = $DM14_minus;
		$yesterday_ADX = $ADX;

		$DI_plus[] = sprintf($format, $DI14_plus);
		$DI_minus[] = sprintf($format, $DI14_minus);
		$adx[] = sprintf($format, $ADX);
	}
}

//MFI (arr, d, h, l, c, v)
function MFI ($array_ref, $days, $index_high, $index_low, $index_close, $index_vol) {
	global $mfi;

	$neg_days = -1 * $days;
	$typical_price_Y = 0;
	$arr_nmf = array();
	$arr_pmf = array();
	
	$format = '%1.0f';
				
	// iterate over historical data rows
	for ($i = 0; $i < count($array_ref); $i++) {
		$data = explode(",", $array_ref[$i]);	
		
		$h = $data[$index_high];
		$l = $data[$index_low];
		$c = $data[$index_close];
		$v = $data[$index_vol];
		
		$typical_price = ($h + $l + $c) / 3;
		$money_flow = $typical_price * $v;
		
		// positive flow
		if ($typical_price > $typical_price_Y) {
			$arr_pmf[] = $money_flow;
			$arr_nmf[] = 0; 		
		}
		//negative flow
		else {
			$arr_pmf[] = 0;
			$arr_nmf[] = $money_flow;
		}
		
		$arr_pmf = array_slice($arr_pmf, $neg_days); 
		$arr_nmf = array_slice($arr_nmf, $neg_days); 
		
		$sum_p = array_sum($arr_pmf);
		$sum_n = array_sum($arr_nmf);
		
		if ($sum_n != 0)
			$money_flow_index = 100 - (100 / (1 + ($sum_p / $sum_n)));
		else
			$money_flow_index = 100;		
		
		$mfindex = sprintf($format, $money_flow_index);
		$mfi[] = $mfindex;
		
		$typical_price_Y = $typical_price;
	}
}


// for candles / sma of body size
function SMABody ($array_ref, $days, $index_open, $index_close, $decimal_places) {
	$width = array();
	$running = array();
	$ma_count = 0;
	
	$format = '%1.' . $decimal_places . 'f';

	foreach ($array_ref as $ele) {
		$ele = trim($ele);
		
		$data = explode (',', $ele);

		if (count($width) < $days) {
			array_push($width, abs($data[$index_close] - $data[$index_open]));
			if (count($width) == $days) { 
				$ma_count = average($width); 
			}
		}
		else {
			array_shift($width);
			array_push($width, abs($data[$index_close] - $data[$index_open]) );
			$ma_count = average($width);
		}

		array_push($running, sprintf($format, $ma_count)); 
	}
	return($running);
}


// open, close, high, low
// trend = current close compared to 10 day sma in %
function Candles ($array_ref, $index_open, $index_close, $index_high, $index_low) {
	
	// initialize and fill arrays to avoid key errors; sliced out later
	$trend_arr = array("", "", "", "", "", "", "", "", "", "");
	$CandlePatterns = array("", "", "", "", "", "", "", "", "", "");
	$CandlePatLengths = array("", "", "", "", "", "", "", "", "", "");
	$CandleColor = array("", "", "", "", "", "", "", "", "", "");
	$CandleBody = array("", "", "", "", "", "", "", "", "", "");
	$CandleTrend = array("", "", "", "", "", "", "", "", "", "");
	$body_type_arr = array("", "", "", "", "", "", "", "", "", "");

	$hist_base_body = array("", "", "", "", "", "", "", "", "", "");
	$hist_body_type = array("", "", "", "", "", "", "", "", "", "");
	$hist_body_day = array("", "", "", "", "", "", "", "", "", "");
	$hist_body_mid = array("", "", "", "", "", "", "", "", "", "");
	$hist_trend = array("", "", "", "", "", "", "", "", "", "");
	$hist_color = array("", "", "", "", "", "", "", "", "", "");
	$hist_lower_shadow = array("", "", "", "", "", "", "", "", "", "");
	$hist_upper_shadow = array("", "", "", "", "", "", "", "", "", "");
	$hist_lower_shadow_hl = array("", "", "", "", "", "", "", "", "", "");
	$hist_upper_shadow_hl = array("", "", "", "", "", "", "", "", "", "");
	$hist_Close = array("", "", "", "", "", "", "", "", "", "");
	$hist_Open = array("", "", "", "", "", "", "", "", "", "");
	$hist_High = array("", "", "", "", "", "", "", "", "", "");
	$hist_Low = array("", "", "", "", "", "", "", "", "", "");
	$hist_high_low = array("", "", "", "", "", "", "", "", "", "");
	$hist_body_top = array("", "", "", "", "", "", "", "", "", "");
	$hist_body_bottom = array("", "", "", "", "", "", "", "", "", "");
	$hist_body = array("", "", "", "", "", "", "", "", "", "");
	
	$ema_close = EMA($array_ref, 10, $index_close, 2);
	$sma_body = SMABody($array_ref, 10, $index_open, $index_close, 2);

	// iterate over yahoo historical data rows
	for ($i = 0; $i < count($array_ref); $i++) {
		
		trim($array_ref[$i]);
		
		$pattern = "";
		
		$data = explode(',', $array_ref[$i]);
		$Close = $data[$index_close];
		$Open = $data[$index_open];
		$High = $data[$index_high];
		$Low = $data[$index_low];
		
		$patterns = array();
		$pat_len = array();

		// determine color
		$color = "White";
		$body_top = $Close;
		$body_bottom = $Open;
		if ($Close < $Open) {
			$color = "Black";
			$body_top = $Open;
			$body_bottom = $Close;
		}
		
		$body = abs($Close - $Open);
		$high_low = $High - $Low;
		
		// shadow sizes as percent of body size
		$lower_shadow = ""; 
		$upper_shadow = "";
		
		if ($body != 0) {
			if ($Open > $Close) { 
				$upper_shadow = sprintf('%1.0f', ( ($High - $Open) / $body * 100));
				$lower_shadow = sprintf('%1.0f', ( ($Close - $Low) / $body * 100));			
			}
			else {
				$upper_shadow = sprintf('%1.0f', ( ($High - $Close) / $body * 100));
				$lower_shadow = sprintf('%1.0f', ( ($Open - $Low) / $body * 100));	 
			}
		}
		// shadaws as percent of high-low size
		$lower_shadow_hl = ""; 
		$upper_shadow_hl = "";
		
		if ($high_low != 0) {
			if ($Open > $Close) { 
				$upper_shadow_hl = sprintf('%1.0f', ( ($High - $Open) / $high_low * 100));
				$lower_shadow_hl = sprintf('%1.0f', ( ($Close - $Low) / $high_low * 100));			
			}
			else {
				$upper_shadow_hl = sprintf('%1.0f', ( ($High - $Close) / $high_low * 100));
				$lower_shadow_hl = sprintf('%1.0f', ( ($Open - $Low) / $high_low * 100));	 
			}
		}
		
		// Detrmine Long Day / Short Day.	Long Day is where body is 50% or more of day range
		$body_day = "";
		if ((2 * (abs($Open - $Close))) >= ($High - $Low)) {
			 $body_day = 'Long';
		}
		else {
			 $body_day = 'Short';
		}
		
		// determine mid-point of body
		$body_mid = sprintf('%1.2f', abs($Open + $Close) / 2);
		
		// determine body type: long, short, doji
		// body larger than 125% of average
		//if ($body > ($sma_body->[$i] * 0.75)) { $body_type = "Medium"; }
		// body larger than 75% of average
		$body_type = "Short";
		if ($body >= ($sma_body[$i] * 0.750)) { 
			$body_type = "Long"; 
		}
		
		
		// body less than 20% of sma body
		//elseif ($body <= ($sma_body->[$i] * 0.10)) { $body_type = "Doji";	}
		elseif ($body <= ($sma_body[$i] * 0.10) || $lower_shadow > 490 || $upper_shadow > 490) { 
			$body_type = "Doji";	
		}
		

		
		array_push($body_type_arr, $body_type);
		//array_push($patterns, $body_type);

		// Marubozu
		$base_body = "";
		if ($body_type == 'Long') {
			if (($color == 'White' && $lower_shadow	== 0) || ($color == 'Black' && $upper_shadow_hl	== 0) ) {
				$base_body = 'Opening Marubozu';
			}
			if (($color == 'Black' && $lower_shadow	== 0) || ($color == 'White' && $upper_shadow_hl	== 0) ) {
				$base_body = 'Closing Marubozu';
			}
			if ($upper_shadow_hl == 0 && $lower_shadow	== 0) {
				$base_body = 'Marubozu';
			}
		}
		elseif ($body_type == 'Short') {
			if ($upper_shadow > 100 && $lower_shadow > 100) {
				$base_body = 'Spinning Top';
			}
		}
		if ($base_body != "") {
			//array_push($patterns, $base_body);
		}


		// determine trend strength in % 
		if ($ema_close[$i] != 0) {
			// current close compared to sma close
			//$trend = ($Close - $ema_close->[$i]) / $ema_close->[$i] * 100;
			// current avg high/low compared to sma close
			$trend = sprintf('%1.2f', ( ( ($High + $Low) / 2) - $ema_close[$i]) / $ema_close[$i] * 100); // mid point of day compared to sma
		}
		else { $trend = sprintf('%1.2f',0); }
		array_push($trend_arr, $trend);
		
		// index: 1 = 1 day ago, 2 = 2 days ago, etc
		array_unshift($hist_color, $color);
		array_unshift($hist_high_low, $high_low);
		array_unshift($hist_trend, $trend);
		array_unshift($hist_body_type, $body_type);
		array_unshift($hist_base_body, $base_body);
		array_unshift($hist_upper_shadow, $upper_shadow);
		array_unshift($hist_lower_shadow, $lower_shadow);
		array_unshift($hist_upper_shadow_hl, $upper_shadow_hl);
		array_unshift($hist_lower_shadow_hl, $lower_shadow_hl);
		array_unshift($hist_Close, $Close);
		array_unshift($hist_Open, $Open);
		array_unshift($hist_High, $High);
		array_unshift($hist_Low, $Low);
		array_unshift($hist_body_top, $body_top);
		array_unshift($hist_body_bottom, $body_bottom);
		array_unshift($hist_body, $body);
		array_unshift($hist_body_day, $body_day);
		array_unshift($hist_body_mid, $body_mid);
		if (count($hist_color) == 10) { array_pop($hist_color); }
		if (count($hist_high_low) == 10) { array_pop($hist_high_low); }
		if (count($hist_trend) == 10) { array_pop($hist_trend); }
		if (count($hist_base_body) == 10) { array_pop($hist_base_body); }
		if (count($hist_body_type) == 10) { array_pop($hist_body_type); }
		if (count($hist_upper_shadow) == 10) { array_pop($hist_upper_shadow); }
		if (count($hist_lower_shadow) == 10) { array_pop($hist_lower_shadow); }
		if (count($hist_upper_shadow_hl) == 10) { array_pop($hist_upper_shadow_hl); }
		if (count($hist_lower_shadow_hl) == 10) { array_pop($hist_lower_shadow_hl); }
		if (count($hist_Close) == 10) { array_pop($hist_Close); }
		if (count($hist_Open) == 10) { array_pop($hist_Open); }
		if (count($hist_High) == 10) { array_pop($hist_High); }
		if (count($hist_Low) == 10) { array_pop($hist_Low); }
		if (count($hist_body_top) == 10) { array_pop($hist_body_top); }
		if (count($hist_body_bottom) == 10) { array_pop($hist_body_bottom); }
		if (count($hist_body) == 10) { array_pop($hist_body); }
		if (count($hist_body_day) == 10) { array_pop($hist_body_day); }		
		if (count($hist_body_mid) == 10) { array_pop($hist_body_mid); }	

		//1 hammer
		if ($body_type == 'Short' && 
			$lower_shadow > 200 && 
			$upper_shadow_hl <= 10 && 
			$trend < 0) {
			
			$pattern = 'Hammer R+';
			array_push($patterns, 'Hammer R+');
			array_push($pat_len, 1);
		}

		
		//2 hanging man
		if ($body_type == 'Short' && 
			$lower_shadow > 200 && 
			$upper_shadow_hl <= 10 && 
			$trend > 0) {
			
			$pattern = 'Hanging Man R-';
			array_push($patterns, 'Hanging Man R-');
			array_push($pat_len, 1);
		}

		//3 belt hold
		if ($color == 'White' && 
			$body_type == 'Long' && 
			$lower_shadow == 0 && 
			$upper_shadow > 0 && 
			$trend < 0) {
			
			$pattern = 'Belt Hold R+';
			array_push($patterns, 'Belt Hold R+');
			array_push($pat_len, 1);
		}
		
		//4
		if ($color == 'Black' && 
			$body_type == 'Long' && 
			$upper_shadow == 0 && 
			$lower_shadow > 0 && 
			$trend > 0) {
			
			$pattern = 'Belt Hold R-';
			array_push($patterns, 'Belt Hold R-');
			array_push($pat_len, 1);
		}
 
		//5 engulfing
		if ($color == 'White' && 
			$hist_color[1] == 'Black' &&
			$body_type == 'Long' && 
			$hist_body_type[1] == 'Short' && 
			( ( $body_top >= $hist_body_top[1] && $body_bottom < $hist_body_bottom[1]) || 
			  ( $body_top > $hist_body_top[1] && $body_bottom <= $hist_body_bottom[1]) ) &&
			$hist_trend[1] < 0) {
			
			$pattern = 'Engulfing R+';
			array_push($patterns, 'Engulfing R+');
			array_push($pat_len, 2);
		}
		
		//6
		if ($color == 'Black' && 
			$hist_color[1] == 'White' &&
			$body_type == 'Long' && 
			$hist_body_type[1] == 'Short'&& 
			( ($body_top >= $hist_body_top[1] && $body_bottom < $hist_body_bottom[1]) || 
			  ($body_top > $hist_body_top[1] && $body_bottom <= $hist_body_bottom[1]) ) &&
			$hist_trend[1] > 0) {
			
			$pattern = 'Engulfing R-';
			array_push($patterns, 'Engulfing R-');
			array_push($pat_len, 2);
		 }

		//7 harami
		if ($color == 'White' &&
			$hist_color[1] == 'Black' &&
			$body_type == 'Short' && 
			$hist_body_type[1] == 'Long'&& 
			( ($body_top <= $hist_body_top[1] && $body_bottom > $hist_body_bottom[1]) || 
			  ($body_top < $hist_body_top[1] && $body_bottom >= $hist_body_bottom[1]) ) &&
			$hist_trend[1] < 0) {
			
			$pattern = 'Harami R+';
			array_push($patterns, 'Harami R+');
			array_push($pat_len, 2);
		}
		
		//8
		if ($color == 'Black' && 
			$hist_color[1] == 'White' &&
			$body_type == 'Short' && 
			$hist_body_type[1] == 'Long'&& 
			( ($body_top <= $hist_body_top[1] && $body_bottom > $hist_body_bottom[1]) || 
			  ($body_top < $hist_body_top[1] && $body_bottom >= $hist_body_bottom[1]) ) &&
			$hist_trend[1] > 0) {
			
			$pattern = 'Harami R-';
			array_push($patterns, 'Harami R-');
			array_push($pat_len, 2);
		}
		
		//9 harami cross
		if ($hist_color[1] == 'Black' &&
			$body_type == 'Doji' && 
			$hist_body_type[1] == 'Long' && 
			$High <= $hist_body_top[1]	&& 
			$Low >= $hist_body_bottom[1] && 
			$hist_trend[1] < 0) {
		
			$pattern = 'Harami Cross R+';
			array_push($patterns, 'Harami Cross R+');
			array_push($pat_len, 2);
		}
		//10
		if ($hist_color[1] == 'White' &&
			$body_type == 'Doji' && 
			$hist_body_type[1] == 'Long' && 
			$High <= $hist_body_top[1]	&& 
			$Low >= $hist_body_bottom[1] && 
			$hist_trend[1] > 0) {
			
			$pattern = 'Harami Cross R-';
			array_push($patterns, 'Harami Cross R-');
			array_push($pat_len, 2);
		}
		
		//11 inverted hammer
		if ($body_type == 'Short' && 
			$lower_shadow <= 10 && 
			$upper_shadow_hl <= 200 && 
			$upper_shadow >= 50 && 
			$body_top <= $hist_body_bottom[1] && 
			$trend < 0) {
			
			$pattern = 'Inverted Hammer R+';
			array_push($patterns, 'Inverted Hammer R+');
			array_push($pat_len, 1);
		}

		//12 shooting star
		if ($body_type == 'Short' && 
			$lower_shadow <= 10 && 
			$upper_shadow >= 300 && 
			$body_bottom >= $hist_body_top[1] && 
			$trend > 0) {
			
			$pattern = 'Shooting Star R-';
			array_push($patterns, 'Shooting Star R-');
			array_push($pat_len, 1);
		}

		//13 piercing line
		if ($body_type == 'Long' && 
			$hist_body_type[1] == 'Long' &&
			$color == 'White' && 
			$hist_color[1] == 'Black' &&	 
			$body_bottom < $hist_body_bottom[1] && 
			$body_top < $hist_body_top[1] && 
			$body_top >= (($hist_Open[1] + $hist_Close[1]) / 2) &&
			$trend < 0) {
			
			$pattern = 'Piercing Line R+';
			array_push($patterns, 'Piercing Line R+');
			array_push($pat_len, 2);
		}

		//14 dark cloud cover
		if ($body_type == 'Long' && 
			$hist_body_type[1] == 'Long' &&
			$color == 'Black' && 
			$hist_color[1] == 'White' &&	 
			$body_top > $hist_body_top[1] && 
			$body_bottom > $hist_body_bottom[1] && 
			$body_bottom <= (($hist_Open[1] + $hist_Close[1]) / 2) &&
			$trend > 0) {
			
			$pattern = 'Dark Cloud Cover R-';
			array_push($patterns, 'Dark Cloud Cover R-');
			array_push($pat_len, 2);
		}
		
		//15 doji star
		if ($body_type == 'Doji' && 
			$hist_body_type[1] == 'Long' && 
			$hist_color[1] == 'Black' && 
			$body_top <= $hist_body_bottom[1] && 
			$trend < 0 ) {
			
			$pattern = 'Doji Star R+';
			array_push($patterns, 'Doji Star R+');
			array_push($pat_len, 2);
		}
		
		//16
		if ($body_type == 'Doji' && 
			$hist_body_type[1] == 'Long' && 
			$hist_color[1] == 'White' && 
			$body_bottom >= $hist_body_top[1] && 
			$trend > 0 ) {
			
			$pattern = 'Doji Star R-';
			array_push($patterns, 'Doji Star R-');
			array_push($pat_len, 2);
		}
		
		//17 meeting lines
		if ($body_type == 'Long' && 
			$hist_body_type[1] == 'Long' && 
			$color == 'White' && 
			$hist_color[1] == 'Black' && 
			$Close == $hist_Close[1] &&
			$trend < 0) {
			
			$pattern = 'Meeting Lines R+';
			array_push($patterns, 'Meeting Lines R+');
			array_push($pat_len, 2);
		}
		
		//18
		if ($body_type == 'Long' && 
			$hist_body_type[1] == 'Long' && 
			$color == 'Black' && 
			$hist_color[1] == 'White' && 
			$Close == $hist_Close[1] &&
			$trend > 0) {
			
			$pattern = 'Meeting Lines R-';
			array_push($patterns, 'Meeting Lines R-');
			array_push($pat_len, 2);
		}
		
		//19 homing pigeon (note: $body > $sma_body[$i] * 0.10)
		if ($color == 'Black' && 
			$hist_color[1] == 'Black' && 
			($body_type == 'Short' || $body > $sma_body[$i] * 0.10) && 
			$hist_body_type[1] == 'Long' && 
			$body_top < $hist_body_top[1] && 
			$body_bottom > $hist_body_bottom[1]	&&
			$hist_trend[1] < 0) {
		
			$pattern = 'Homing Pigeon R+';
			array_push($patterns, 'Homing Pigeon R+');
			array_push($pat_len, 2);

		//20 decending hawk
		}
		if ($color == 'White' && 
			$hist_color[1] == 'White' && 
			$body_type == 'Long' && 
			$hist_body_type[1] == 'Long'&& 
			$body_top < $hist_body_top[1] && 
			$body_bottom > $hist_body_bottom[1] &&
			$hist_trend[1] > 0) {
			
			$pattern = 'Decending Hawk R-';
			array_push($patterns, 'Decending Hawk R-');
			array_push($pat_len, 2);
		}
		
		//21 matching low
		 if ($color == 'Black' && 
			$hist_color[1] == 'Black' && 
			$body_type != 'Doji' && 
			$hist_body_type[1] != 'Doji' && 
			$body_top < $hist_body_top[1] && 
			$body_bottom == $hist_body_bottom[1] &&
			$hist_trend[1] < 0) {
			
			$pattern = 'Matching Low R+';
			array_push($patterns, 'Matching Low R+');
			array_push($pat_len, 2);
		}

		//22 matching high
		 if ($color == 'White' && 
			$hist_color[1] == 'White' && 
			$body_type != 'Doji' && 
			$hist_body_type[1] == 'Long' && 
			$body_top == $hist_body_top[1] && 
			$body_bottom > $hist_body_bottom[1] &&
			$hist_trend[1] > 0 && 
			$upper_shadow_hl <= 10 && 
			$hist_upper_shadow_hl[1] <= 10 ) {
			
			$pattern = 'Matching High R-';
			array_push($patterns, 'Matching High R-');
			array_push($pat_len, 2);
		}
		
		//23 kicking
		if ($base_body == 'Marubozu' && 
			$hist_base_body[1] == 'Marubozu' && 
			$color == 'White' && 
			$hist_color[1] == 'Black' &&
			$body_bottom > $hist_body_top[1]) {
			
			$pattern = 'Kicking R+'; 
			array_push($patterns, 'Kicking R+');
			array_push($pat_len, 2);
		}
		
		//24
		if ($base_body == 'Marubozu' && 
			$hist_base_body[1] == 'Marubozu' && 
			$color == 'Black' && 
			$hist_color[1] == 'White' &&
			$body_top < $hist_body_bottom[1]) {
			
			$pattern = 'Kicking R-'; 
			array_push($patterns, 'Kicking R-');
			array_push($pat_len, 2);
		}
		
		//25 one white soldier
		if ($body_type == 'Long' && 
			$hist_body_type[1] == 'Long'&&	
			$color == 'White' && 
			$hist_color[1] == 'Black' && 
			$body_bottom > $hist_body_bottom[1] && 
			$body_bottom < $hist_body_top[1] && 
			$body_top > $hist_High[1] && 
			$hist_trend[1] < 0) {
			
			$pattern = 'One White Soldier R+';		 
			array_push($patterns, 'One White Soldier R+');
			array_push($pat_len, 2);
		}

		//26 one black crow
		if ($body_type == 'Long' && 
			$hist_body_type[1] == 'Long'&&	
			$color == 'Black' && 
			$hist_color[1] == 'White' && 
			$body_bottom < $hist_Low[1] && 
			$body_top < $hist_body_top[1] && 
			$body_top > $hist_body_bottom[1] && 
			$hist_trend[1] > 0) {
			
			$pattern = 'One Black Crow R-';		 
			array_push($patterns, 'One Black Crow R-');
			array_push($pat_len, 2);
		}
		
		//27 morning star
		if ($hist_color[2] == 'Black' && 
			$color == 'White' &&	
			$hist_body_type[2] == 'Long'&& 
			$hist_body_type[1] == 'Short' && 
			$body_type == 'Long' &&
			$hist_body_bottom[2] > $hist_body_top[1] &&	
			$body_bottom > $hist_body_top[1] && 
			$hist_trend[1] < 0) {
			
			$pattern = 'Morning Star R+';		 
			array_push($patterns, 'Morning Star R+');
			array_push($pat_len, 3);
		}

		//28 evening star
		if ($hist_color[2] == 'White' && 
			$color == 'Black' && 
			$hist_body_type[2] == 'Long' && 
			$hist_body_type[1] == 'Short' && 
			$body_type == 'Long' &&
			$hist_body_top[2] < $hist_body_bottom[1] &&	
			$body_top < $hist_body_bottom[1] && 
			$hist_trend[1] > 0) {
			
			$pattern = 'Evening Star R-';		 
			array_push($patterns, 'Evening Star R-');
			array_push($pat_len, 3);
		}

		//29 morning doji star
		if ($hist_color[2] == 'Black' && 
			$color == 'White' &&	
			$hist_body_type[2] == 'Long'&& 
			$hist_body_type[1] == 'Doji' && 
			$body_type == 'Long' &&
			$hist_body_bottom[2] > $hist_body_top[1] &&	
			$body_bottom > $hist_body_top[1] && 
			$hist_trend[1] < 0) {
			
			$pattern = 'Morning Doji Star R+';		 
			array_push($patterns, 'Morning Doji Star R+');
			array_push($pat_len, 3);
		}

		//30 evening doji star
		if ($hist_color[2] == 'White' && 
			$color == 'Black' && 
			$hist_body_type[2] == 'Long' && 
			$hist_body_type[1] == 'Doji' && 
			$body_type == 'Long' &&
			$hist_body_top[2] < $hist_body_bottom[1] &&	
			$body_top < $hist_body_bottom[1] && 
			$hist_trend[1] > 0) {
			
			$pattern = 'Evening Doji Star R-';		 
			array_push($patterns, 'Evening Doji Star R-');
			array_push($pat_len, 3);
		}
		
		//31 abandoned baby
		if ($hist_color[2] == 'Black' && 
			$color == 'White' &&	
			$hist_body_type[2] == 'Long' && 
			$hist_body_type[1] == 'Doji' && 
			$body_type == 'Long' &&
			$hist_Low[2] > $hist_High[1] &&	
			$Low > $hist_High[1] && 
			$hist_trend[1] < 0) {
			
			$pattern = 'Abandoned Baby R+';		 
			array_push($patterns, 'Abandoned Baby R+');
			array_push($pat_len, 3);
		}
		
		//32
		if ($hist_color[2] == 'White' && 
			$color == 'Black' && 
			$hist_body_type[2] == 'Long'&& 
			$hist_body_type[1] == 'Doji' && 
			$body_type == 'Long' &&
			$hist_High[2] < $hist_Low[1] &&	
			$High < $hist_Low[1] && 
			$hist_trend[1] > 0) {
			
			$pattern = 'Abandoned Baby R-';		 
			array_push($patterns, 'Abandoned Baby R-');
			array_push($pat_len, 3);
		}
		
		//33 tri star
		if ($hist_body_type[2] == 'Doji' && 
			$hist_body_type[1] == 'Doji' && 
			$body_type == 'Doji' &&
			$hist_body_bottom[2] > $hist_body_top[1] &&	
			$body_bottom > $hist_body_top[1] && 
			$hist_trend[1] < 0) {
			
			$pattern = 'Tri Star R+';		 
			array_push($patterns, 'Tri Star R+');
			array_push($pat_len, 3);
		}
		
		//34
		if ($hist_body_type[2] == 'Doji' && 
			$hist_body_type[1] == 'Doji' && 
			$body_type == 'Doji' &&
			$hist_body_top[2] < $hist_body_bottom[1] &&	
			$body_top < $hist_body_bottom[1] && 
			$hist_trend[1] > 0) {
			
			$pattern = 'Tri Star R-';		 
			array_push($patterns, 'Tri Star R-');
			array_push($pat_len, 3);
		}
		
		//35 upside gap two crows
		if ($color == 'Black' && 
			$hist_color[1] == 'Black' && 
			$hist_color[2] == 'White' &&
			$hist_body_type[2] == 'Long'&& 
			$body_top > $hist_body_top[1] && 
			$body_bottom < $hist_body_bottom[1]	&& 
			$body_bottom > $hist_body_top[2]	&&
			$hist_trend[1] > 0) {
			
			$pattern = 'Upside Gap Two Crows R-';
			array_push($patterns, 'Upside Gap Two Crows R-');
			array_push($pat_len, 3);
		}
		
		//36 downside gap two rabbits
		if ($color == 'White' && 
			$hist_color[1] == 'White' && 
			$hist_color[2] == 'Black' &&
			$hist_body_type[2] == 'Long'&& 
			$body_top > $hist_body_top[1] && 
			$body_bottom < $hist_body_bottom[1]	&& 
			$body_top < $hist_body_bottom[2] &&
			$hist_trend[1] < 0) {
			
			$pattern = 'Downside Gap Two Rabbits R+';
			array_push($patterns, 'Downside Gap Two Rabbits R+');
			array_push($pat_len, 3);
		}
		 
		//37 unique three river bottom (note: using this to see a short instead of doji: $hist_body[1] > $sma_body[$i-1] * 0.10
		if ($hist_color[1] == 'Black' && 
			$hist_color[2] == 'Black' && 
			($hist_body_type[1] == 'Short' || $hist_body[1] > $sma_body[$i-1] * 0.10) && 
			$hist_body_type[2] == 'Long' && 
			$hist_body_top[1] < $hist_body_top[2] && 
			$hist_body_bottom[1] > $hist_body_bottom[2]	&&
			$body_type == 'Short' && 
			$color == 'White' && 
			$Open > $hist_Low[1] && 
			$Close < $hist_Close[1] &&
			$hist_Low[1] < $hist_Low[2] && 
			$hist_Low[1] < $Low &&
			$hist_trend[1] < 0) {
			
			$pattern = 'Unique Three River Bottom R+';
			array_push($patterns, 'Unique Three River Bottom R+');
			array_push($pat_len, 3);
		}
		 
		//38 unique three mountain top (note: using this to see a short instead of doji: $hist_body[1] > $sma_body[$i-1] * 0.10
		if ($hist_color[1] == 'White' && 
			$hist_color[2] == 'White' && 
			($hist_body_type[1] == 'Short' || $hist_body[1] > $sma_body[$i-1] * 0.10) && 
			$hist_body_type[2] == 'Long' && 
			$hist_body_bottom[1] > $hist_body_bottom[2]	&& 
			$hist_body_top[1] < $hist_body_top[2] &&
			$body_type == 'Short' && 
			$color == 'Black' && 
			$Open < $hist_High[1] && 
			$Close > $hist_Close[1] &&
			$hist_High[1] > $hist_High[2] && 
			$hist_High[1] > $High &&
			$hist_trend[1] > 0) {
			
			$pattern = 'Unique Three Mountain Top R-';
			array_push($patterns, 'Unique Three Mountain Top R-');
			array_push($pat_len, 3);
		}
		
		//39 three white soldiers
		if ($color == 'White' && 
			$hist_color[1] == 'White' && 
			$hist_color[2] == 'White'&&
			$body_type != 'Doji' && 
			$hist_body_type[1] != 'Doji' && 
			$hist_body_type[2] != 'Doji' && 
			$Open > $hist_Open[1] && 
			$Open < $hist_Close[1] && 
			$hist_Open[1] > $hist_Open[2] && 
			$hist_Open[1] < $hist_Close[2] && 
			$hist_trend[2] < 0) {
			
			//$Open > $hist_Open[1] && 
			//$hist_Open[1] > $hist_Open[2]	&& $hist_trend[1] < 0) {
			
			$pattern = 'Three White Soldiers R+';
			array_push($patterns, 'Three White Soldiers R+');
			array_push($pat_len, 3);
		}

		//40, 41 three black crows
		if ($color == 'Black' && 
			$hist_color[1] == 'Black' && 
			$hist_color[2] == 'Black'&&
			$body_type == 'Long' && 
			$hist_body_type[1] == 'Long' && 
			$hist_body_type[2] == 'Long' && 
			$hist_Close[2] > $hist_Close[1] && 
			$hist_Close[1] > $Close && $hist_trend[2] > 0) {
			
			if (abs(($hist_Close[1] - $Open) / $Open * 100)	< 0.85 && 
				abs(($hist_Close[2] - $hist_Open[1]) / $hist_Open[1] * 100) < 0.85) { //	similar	(with 0.85 %))
				
				$pattern = 'Identical Three Crows R-';
				array_push($patterns, 'Identical Three Crows R-');
			array_push($pat_len, 3);				
			}
			else {
				$pattern = 'Three Black Crows R-';
				array_push($patterns, 'Three Black Crows R-');
				array_push($pat_len, 3);
			}
		}
		
		//42 advance block
		if ($color == 'White' && 
			$hist_color[1] == 'White' && 
			$hist_color[2] == 'White'&&
			$body_type != 'Doji' && 
			$hist_body_type[1] != 'Doji' && 
			$hist_body_type[2] == 'Long' && 
			$upper_shadow_hl >= 40 && 
			$hist_upper_shadow_hl[1] >= 40 &&
			$Open >= $hist_body_bottom[1] && 
			$Open <= $hist_body_top[1] && 
			$hist_Open[1] >= $hist_body_bottom[2] && 
			$hist_Open[1] <= $hist_body_top[1] && 
			$hist_trend[2] > 0) {
			
			$pattern = 'Advance Block R-';
			array_push($patterns, 'Advance Block R-');
			array_push($pat_len, 3);
		}

		//43 descent block
		if ($color == 'Black' && 
			$hist_color[1] == 'Black' && 
			$hist_color[2] == 'Black'&&
			$body_type != 'Doji' && 
			$hist_body_type[1] != 'Doji' && 
			$hist_body_type[2] == 'Long' && 
			$lower_shadow_hl >= 40 && 
			$hist_lower_shadow_hl[1] >= 40 &&
			$Open >= $hist_body_bottom[1] && 
			$Open <= $hist_body_top[1] && 
			$hist_Open[1] >= $hist_body_bottom[2] && 
			$hist_Open[1] <= $hist_body_top[1] && 
			$hist_trend[2] < 0) {
			
			$pattern = 'Descent Block R+';
			array_push($patterns, 'Descent Block R+');
			array_push($pat_len, 3);
		}
		
		//44 deliberation
		if ($color == 'White' && 
			$hist_color[1] == 'White' && 
			$hist_color[2] == 'White'&&
			$hist_body_day[1] == 'Long' && 
			$hist_body_day[2] == 'Long' && 
			$upper_shadow_hl > 20 && 
			$lower_shadow > 20 &&
			($Open > $hist_Open[1] || abs(($hist_Close[1] - $Open) / $Open * 100) < 0.85) && 
			$hist_Open[1] > $hist_Open[2] && 
			$hist_Open[1] < $hist_Close[2] && 
			$hist_trend[2] > 0) {
			
			$pattern = 'Deliberation R-';
			array_push($patterns, 'Deliberation R-');
			array_push($pat_len, 3);
		}
		
		//45
		if ($color == 'Black' && 
			$hist_color[1] == 'Black' && 
			$hist_color[2] == 'Black'&&
			$hist_body_day[1] == 'Long' && 
			$hist_body_day[2] == 'Long' && 
			$upper_shadow_hl > 20 && 
			$lower_shadow > 20 &&
			($Open < $hist_Close[1] || abs(($hist_Close[1] - $Open) / $Open * 100) < 0.85) && 
			$hist_Close[1] > $hist_Close[2] && 
			$hist_Open[1] < $hist_Close[2] && 
			$hist_trend[2] < 0) {
			
			$pattern = 'Deliberation R+';
			array_push($patterns, 'Deliberation R+');
			array_push($pat_len, 3);
		}

		//46 two crows
		if ($color == 'Black' && 
			$hist_color[1] == 'Black' && 
			$hist_color[2] == 'White'&&
			$body_type == 'Long' && 
			$hist_body_type[1] != 'Doji' && 
			$hist_body_type[2] == 'Long' && 
			$hist_body_bottom[1] > $hist_body_top[2] && 
			$Open <= $hist_body_top[1]	&& 
			$Open >= $hist_body_bottom[1] &&
			$Close >= $hist_body_bottom[2] && 
			$Close <= $hist_body_top[2]	&&
			$hist_trend[2] > 0) {
			
			$pattern = 'Two Crows R-';
			array_push($patterns, 'Two Crows R-');
			array_push($pat_len, 3); 
		}

		//47 two rabbits
		if ($color == 'White' && 
			$hist_color[1] == 'White' && 
			$hist_color[2] == 'Black'&&
			$body_type == 'Long' && 
			$hist_body_type[1] != 'Doji' && 
			$hist_body_type[2] == 'Long' && 
			$body_day == 'Long' && 
			$hist_body_day[1] == 'Long' && 
			$hist_body_day[2] == 'Long' &&
			($hist_body_bottom[2] - $hist_body_top[1]) > ($hist_body_top[2] - $hist_body_bottom[2]) * 0.10 &&
			$body_bottom >= $hist_body_bottom[1] && 
			$body_bottom <= $hist_body_top[1] && 
			$Close <= $hist_body_top[2] && 
			$Close >= $hist_body_bottom[2] &&
			$hist_trend[2] < 0) {
			
			$pattern = 'Two Rabbits R+';
			array_push($patterns, 'Two Rabbits R+');
			array_push($pat_len, 3);
		}
		
		//48 three inside up
		if ($color == 'White' && 
			$hist_color[1] == 'White' && 
			$hist_color[2] == 'Black' &&
			$body_type == 'Long' && 
			$hist_body_type[1] != 'Doji' && 
			$hist_body_type[2] == 'Long' && 
			( ( $hist_body_top[1] <= $hist_body_top[2]	&& $hist_body_bottom[1] > $hist_body_bottom[2]) || 
			  ( $hist_body_top[1] < $hist_body_top[2] && $hist_body_bottom[1] >= $hist_body_bottom[2]) ) &&
			$Close > $hist_body_top[2] &&
			$hist_trend[2] < 0) {
			
			$pattern = 'Three Inside Up R+';
			array_push($patterns, 'Three Inside Up R+');
			array_push($pat_len, 3);
		}
		
		//49 three inside down
		if ($color == 'Black' && 
			$hist_color[1] == 'Black' && 
			$hist_color[2] == 'White' &&
			$body_type == 'Long'&& 
			$hist_body_type[1] != 'Doji' && 
			$hist_body_type[2] == 'Long'&& 
			( ($hist_body_top[1] <= $hist_body_top[2] && $hist_body_bottom[1] > $hist_body_bottom[2]) || 
			 ( $hist_body_top[1] < $hist_body_top[2] && $hist_body_bottom[1] >= $hist_body_bottom[2]) ) &&
			$Close < $hist_body_bottom[2] &&
			$hist_trend[2] > 0) {
			
			$pattern = 'Three Inside Down R-';
			array_push($patterns, 'Three Inside Down R-');
			array_push($pat_len, 3);
		}
		
		
		//50 three outside up
		if ($color == 'White' && 
			$hist_color[1] == 'White' && 
			$hist_color[2] == 'Black' &&
			$body_type != 'Doji' && 
			$hist_body_type[1] == 'Long' && 
			($hist_body_type[2] == 'Short'|| ((($hist_body[1] - $hist_body[2]) / ($hist_body[2] + 0.000001)) * 100	< 80)) && // or first day body less than 80% of 2nd day body
			( ($hist_body_top[1] >= $hist_body_top[2] && $hist_body_bottom[1] < $hist_body_bottom[2]) || 
			  ($hist_body_top[1] > $hist_body_top[2] && $hist_body_bottom[1] <= $hist_body_bottom[2]) ) &&
			$Close > $hist_Close[1] && 
			$hist_trend[2] < 0) {
	
			$pattern = 'Three Outside Up R+';
			array_push($patterns, 'Three Outside Up R+');
			array_push($pat_len, 3);
		}
		
		//51 three Outside down
		if ($color == 'Black' && 
			$hist_color[1] == 'Black' && 
			$hist_color[2] == 'White' &&
			$body_type != 'Doji' && 
			$hist_body_type[1] == 'Long' && 
			($hist_body_type[2] == 'Short' || ((($hist_body[1] - $hist_body[2]) / ($hist_body[2] + 0.000001)) * 100	< 80)) && // or first day body less than 80% of 2nd day body
			( ($hist_body_top[1] >= $hist_body_top[2] && $hist_body_bottom[1] < $hist_body_bottom[2]) || 
			  ($hist_body_top[1] > $hist_body_top[2] && $hist_body_bottom[1] <= $hist_body_bottom[2]) ) &&
			$hist_trend[2] > 0) {
			
			$pattern = 'Three Outside Down R-';
			array_push($patterns, 'Three Outside Down R-');
			array_push($pat_len, 3);
		}
		 
		//52 Three stars in the south
		if ($color == 'Black' && 
			$hist_color[1] == 'Black' && 
			$hist_color[2] == 'Black' &&
			$body_type != 'Doji'&& 
			$hist_body_type[1] != 'Doji' && 
			$hist_body_type[2] != 'Doji'&& 
			$hist_lower_shadow[2] > 50 && 
			$hist_lower_shadow[1] < $hist_lower_shadow[2] && 
			$hist_body[1] < $hist_body[2] &&
			$hist_Low[2] < $hist_Low[1] && 
			$hist_Low[1] < $Low &&
			$hist_body_top[2] > $hist_body_top[1] && 
			$hist_body_top[1] > $body_top &&
			$hist_body_bottom[2] > $hist_body_bottom[1] && 
			$hist_body_bottom[1] > $body_bottom && 
			$hist_trend[2] < 0) {
			
			$pattern = 'Three Stars in the South R+';
			array_push($patterns, 'Three Stars in the South R+');
			array_push($pat_len, 3);
		}
		
		//53 Three stars in the north (no known examples)
		if ($color == 'White' && 
			$hist_color[1] == 'White' && 
			$hist_color[2] == 'White' &&
			$body_type != 'Doji'&& 
			$hist_body_type[1] != 'Doji' && 
			$hist_body_type[2] == 'Long'&& 
			$body_day == 'Long' &&
			$hist_upper_shadow_hl[2] >= 40 && 
			$hist_lower_shadow_hl[2] <= 7.5 &&
			$hist_upper_shadow_hl[1] >= 40 && 
			$hist_lower_shadow_hl[1] <= 7.5 &&
			$hist_Close[1] > $hist_Close[2]	&& 
			$hist_Open[1] > $hist_Open[2]	&& 
			$Open > $hist_Low[1] && 
			$Close < $hist_High[1] &&
			$hist_High[1] < $hist_High[2] && 
			$hist_trend[2] > 0) {
			
			$pattern = 'Three Stars in the North R-';
			array_push($patterns, 'Three Stars in the North R-');
			array_push($pat_len, 3);
		}		
		
		//54 stick sandwich +  chkp - 19980320
		if ($color == 'Black' && 
			$hist_color[1] == 'White' && 
			$hist_color[2] == 'Black' &&
			(abs($Close - $hist_Close[2]) / $hist_Close[2]) <= 0.002 && // nearly the same: i.e. if one close is 20, the other must fall within 19.98 and 20.02
			$body_top > $hist_body_top[1] &&
			$hist_body_top[1] > $hist_body_top[2] &&
			$hist_body_bottom[1] > $body_bottom &&
			$hist_trend[2] < 0) {
			
			$pattern = 'Stick Sandwich R+';
			array_push($patterns, 'Stick Sandwich R+');
			array_push($pat_len, 3);
		}			 
		 
		//55 stick sandwich - 
		if ($color == 'White' && 
			$hist_color[1] == 'Black' && 
			$hist_color[2] == 'White' &&
			(abs($Close - $hist_Close[2]) / $hist_Close[2]) <= 0.002 && // nearly the same: i.e. if one close is 20, the other must fall within 19.98 and 20.02
			$body_top > $hist_body_top[1] &&
			$hist_body_bottom[1] > $body_bottom &&
			$hist_body_bottom[2] > $hist_body_bottom[1] &&
			$hist_trend[2] > 0) {
			
			$pattern = 'Stick Sandwich R-';
			array_push($patterns, 'Stick Sandwich R-');
			array_push($pat_len, 3);
		}		 
		 
		//56 squeeze alert +  XRX 20010727
		if ($hist_color[2] == 'Black' &&
			$hist_color[3] == 'Black' &&
			$High < $hist_High[1] &&
			$hist_High[1] < $hist_High[2] &&
			$Low > $hist_Low[1] &&
			$hist_Low[1] > $hist_Low[2] &&
			$hist_trend[2] < 0) {
			
			$pattern = 'Squeeze Alert R+';
			array_push($patterns, 'Squeeze Alert R+');
			array_push($pat_len, 3);
		}	
		
		//57 squeeze alert - XRX 20030815
		if ($hist_color[2] == 'White' &&
			$hist_color[3] == 'White' &&
			$High < $hist_High[1] &&
			$hist_High[1] < $hist_High[2] &&
			$Low > $hist_Low[1] &&
			$hist_Low[1] > $hist_Low[2] &&
			$hist_trend[2] > 0) {
			
			$pattern = 'Squeeze Alert R-';
			array_push($patterns, 'Squeeze Alert R-');
			array_push($pat_len, 3);
		}		 
		 
		//58 breakaway +  (PFE 2002-07-24)
		if ($body_type == 'Long' && 
			$hist_body_type[4] == 'Long' && 
			$color == 'White' &&
			$hist_color[1] == 'Black' &&
			$hist_color[3] == 'Black' &&
			$hist_color[4] == 'Black' &&
			$hist_Low[4] > $hist_High[3] &&
			$hist_body_top[3] > $hist_body_top[2] &&
			$hist_body_top[2] > $hist_body_top[1] &&
			$body_top > $hist_body_top[3] &&
			$body_top < $hist_body_bottom[4] &&
			$hist_trend[4] < 0) {
			
			$pattern = 'Breakaway R+';
			array_push($patterns, 'Breakaway R+');
			array_push($pat_len, 5);
		}	
		
		//59 breakaway -  (AA 2009-05-07)
		if ($body_type == 'Long' && 
			$hist_body_type[4] == 'Long' && 
			$color == 'Black' &&
			$hist_color[1] == 'White' &&
			$hist_color[3] == 'White' &&
			$hist_color[4] == 'White' &&
			$hist_High[4] < $hist_Low[3] &&
			$hist_body_top[3] < $hist_body_top[2] &&
			$hist_body_top[2] < $hist_body_top[1] &&
			$body_bottom < $hist_body_bottom[3] &&
			$body_bottom > $hist_body_top[4] &&
			$hist_trend[4] > 0) {
			
			$pattern = 'Breakaway R-';
			array_push($patterns, 'Breakaway R-');
			array_push($pat_len, 5);
		}			
		
		//60 concealing baby swallow +  no known examples
		if ($color == 'Black' &&
			$hist_color[1] == 'Black' &&
			$hist_color[2] == 'Black' &&
			$hist_color[3] == 'Black' &&
			$hist_base_body[2] == 'Marubozu' && 
			$hist_base_body[3] == 'Marubozu' && 
			$hist_body_top[1] < $hist_body_bottom[2] &&
			$body_top > $hist_High[1] &&
			$body_bottom < $hist_Low[1] &&
			$trend < 0) {
			
			$pattern = 'Concealing Baby Swallow R+';
			array_push($patterns, 'Concealing Baby Swallow R+');
			array_push($pat_len, 4);
		}
		
		//61 Ladder bottom + (JNY 1996-12-18)
		if ($color == 'White' &&
			$hist_color[1] == 'Black' &&
			$hist_color[2] == 'Black' &&
			$hist_color[3] == 'Black' &&
			$hist_color[4] == 'Black' &&
			$body_type != 'Doji' &&
			$hist_body_type[1] != 'Doji' && 
			$hist_body_type[2] != 'Doji' && 
			$hist_body_type[3] != 'Doji' && 
			$hist_body_type[4] != 'Doji' && 
			$hist_Close[4] > $hist_Close[3] &&
			$hist_Close[3] > $hist_Close[2] &&
			$hist_Close[2] > $hist_Close[1] &&
			$body_bottom > $hist_body_top[1] &&
			$body_top < $hist_body_top[4] &&
			$hist_upper_shadow_hl[1] >= 40 &&
			$hist_trend[1] < 0) {
			
			$pattern = 'Ladder Bottom R+';
			array_push($patterns, 'Ladder Bottom R+');
			array_push($pat_len, 5);
		}
		
		//62 Ladder top - AMR 1998-09-24 
		if ($color == 'Black' &&
			$hist_color[1] == 'White' &&
			$hist_color[2] == 'White' &&
			$hist_color[3] == 'White' &&
			$hist_color[4] == 'White' &&
			$body_type != 'Doji' &&
			$hist_body_type[1] != 'Doji' && 
			$hist_body_type[2] != 'Doji' && 
			$hist_body_type[3] != 'Doji' && 
			$hist_body_type[4] != 'Doji' && 
			$hist_Close[4] < $hist_Close[3] &&
			$hist_Close[3] < $hist_Close[2] &&
			$hist_Close[2] < $hist_Close[1] &&
			$Close < $hist_Low[1] &&
			$hist_lower_shadow_hl[1] >= 40 &&
			$hist_trend[1] > 0) {
			
			$pattern = 'Ladder Top R-';
			array_push($patterns, 'Ladder Top R-');
			array_push($pat_len, 5);
		}
		
		//63 after bottom gap up + (TWX 1999-06-16)
		if ($color == 'White' &&
			$hist_color[1] == 'White' &&
			$hist_color[2] == 'Black' &&
			$hist_color[3] == 'Black' &&
			$hist_color[4] == 'Black' &&
			$body_type != 'Doji' &&
			$hist_body_type[1] != 'Doji' && 
			$hist_body_type[2] != 'Doji' && 
			$hist_body_type[3] != 'Doji' && 
			$hist_body_type[4] != 'Doji' &&
			$body_day == 'Long' && 			
			$hist_body_day[1] == 'Long' && 
			$hist_body_day[2] == 'Long' && 
			$hist_body_day[3] == 'Long' && 
			$hist_body_day[4] == 'Long' && 
			$hist_Close[4] > $hist_Close[3] &&
			$hist_Close[3] > $hist_Close[2] &&
			($hist_High[4] - $hist_Low[4]) / 10 < ($hist_Close[3] - $hist_Open[2]) && // day 2 & 3 gap > than 10% of day 1 high-low range
			($hist_High[4] - $hist_Low[4]) / 10 < ($Open - $hist_Close[1]) && // day 4 & 5 gap > than 10% of day 1 high-low range
			$Close < $hist_High[4] &&
			$hist_trend[1] < 0) {
			
			$pattern = 'After Bottom Gap Up R+';
			array_push($patterns, 'After Bottom Gap Up R+');
			array_push($pat_len, 5);
		}		
		
		//64 after top gap down - 
		if ($color == 'Black' &&
			$hist_color[1] == 'Black' &&
			$hist_color[2] == 'White' &&
			$hist_color[3] == 'White' &&
			$hist_color[4] == 'White' &&
			$body_type != 'Doji' &&
			$hist_body_type[1] != 'Doji' && 
			$hist_body_type[2] != 'Doji' && 
			$hist_body_type[3] != 'Doji' && 
			$hist_body_type[4] != 'Doji' &&
			$body_day == 'Long' && 			
			$hist_body_day[1] == 'Long' && 
			$hist_body_day[2] == 'Long' && 
			$hist_body_day[3] == 'Long' && 
			$hist_body_day[4] == 'Long' && 
			$hist_Close[4] < $hist_Close[3] &&
			$hist_Close[3] < $hist_Close[2] &&
			($hist_High[4] - $hist_Low[4]) / 10 < ($hist_Open[2] - $hist_Close[3]) && // day 2 & 3 gap > than 10% of day 1 high-low range
			($hist_High[4] - $hist_Low[4]) / 10 < ($hist_Close[1] - $Open) && // day 4 & 5 gap > than 10% of day 1 high-low range
			$Close > $hist_Low[4] &&
			$hist_trend[1] > 0) {
			
			$pattern = 'After Top Gap Down R-';
			array_push($patterns, 'After Top Gap Down R-');
			array_push($pat_len, 5);
		}
		
		//65 Three Gap Downs + (no known examples)
		if ($color == 'Black' &&
			$hist_color[1] == 'Black' &&
			$body_type != 'Doji' &&
			$hist_body_type[1] != 'Doji' && 
			$body_day == 'Long' && 			
			$hist_body_day[1] == 'Long' && 
			// had to add '@' to supress warnings
			@($hist_High[3] - $hist_Low[3]) / 10 < @($hist_body_bottom[3] - $hist_body_top[2]) && // day 1 & 2 gap > than 10% of day 1 high-low range
			@($hist_High[3] - $hist_Low[3]) / 10 < @($hist_body_bottom[2] - $hist_body_top[1]) && // day 2 & 3 gap > than 10% of day 1 high-low range
			@($hist_High[3] - $hist_Low[3]) / 10 < @($hist_body_bottom[1] - $Open) && // day 3 & 4 gap > than 10% of day 1 high-low range
			$hist_trend[1] < 0) {
			
			$pattern = 'Three Gap Downs R+';
			array_push($patterns, 'Three Gap Downs R+');
			array_push($pat_len, 4);
		}		
		
		//66 Three Gap Ups - (ALTR 2002-04-17)
		if ($color == 'White' &&
			$hist_color[1] == 'White' &&
			$body_type != 'Doji' &&
			$hist_body_type[1] != 'Doji' && 
			$body_day == 'Long' && 			
			$hist_body_day[1] == 'Long' && 
			@($hist_High[3] - $hist_Low[3]) / 10 < @($hist_body_bottom[2] - $hist_body_top[3]) && // day 1 & 2 gap > than 10% of day 1 high-low range
			@($hist_High[3] - $hist_Low[3]) / 10 < @($hist_body_bottom[1] - $hist_body_top[2]) && // day 2 & 3 gap > than 10% of day 1 high-low range
			@($hist_High[3] - $hist_Low[3]) / 10 < @($Open - $hist_body_bottom[1]) && // day 3 & 4 gap > than 10% of day 1 high-low range
			$hist_trend[1] > 0) {
			
			$pattern = 'Three Gap Ups R-';
			array_push($patterns, 'Three Gap Ups R-');
			array_push($pat_len, 4);
		}
		
		////////////////////////////////////////////////////////////////////
		
		//separating lines
		if ($body_type != 'Doji' && 
			$hist_body_type[1] != 'Doji' && 
			$color == 'White' && 
			$hist_color[1] == 'Black' && 
			$Open == $hist_Open[1] &&
			$trend > 0) {
			
			$pattern = 'Separating Lines C+';
			array_push($patterns, 'Separating Lines C+');
			array_push($pat_len, 2);
		}
		if ($body_type != 'Doji' && 
			$hist_body_type[1] != 'Doji' && 
			$color == 'Black' && 
			$hist_color[1] == 'White' && 
			$Open == $hist_Open[1] &&
			$trend < 0) {
			
			$pattern = 'Separating Lines C-';
			array_push($patterns, 'Separating Lines C-');
			array_push($pat_len, 2);
		}
		
		//on neck line
		if ($hist_body_type[1] == 'Long' && 
			$body_type != 'Doji' && 
			$color == 'Black' && 
			$hist_color[1] == 'White' && 
			$Open > $hist_High[1] &&
			$Close == $hist_High[1] && 
			$trend > 0) {
			
			$pattern = 'On Neck Line C+';
			array_push($patterns, 'On Neck Line C+');
			array_push($pat_len, 2);
		}
		if ($hist_body_type[1] == 'Long' && 
			$body_type != 'Doji' && 
			$color == 'White' && 
			$hist_color[1] == 'Black' && 
			$Open < $hist_Low[1] &&
			$Close == $hist_Low[1] && 
			$trend < 0) {
			
			$pattern = 'On Neck Line C-';
			array_push($patterns, 'On Neck Line C-');
			array_push($pat_len, 2);
		}
		
		//in neck line
		if ($hist_body_type[1] == 'Long' && 
			$body_type != 'Doji' && 
			$color == 'Black' && 
			$hist_color[1] == 'White' && 
			$hist_body_day[1] == 'Long' && 
			$body_day == 'Long' &&
			$Open > $hist_High[1] && 
			$Close < $hist_Close[1] && 
			$Close >= $hist_Close[1] - (($hist_High[1] - $hist_Low[1]) * 0.05) && 
			$trend > 0) {
			
			$pattern = 'In Neck Line C+';
			array_push($patterns, 'In Neck Line C+');
			array_push($pat_len, 2);
		}
		if ($hist_body_type[1] == 'Long' && 
			$body_type != 'Doji' && 
			$color == 'White' && 
			$hist_color[1] == 'Black'&& 
			$Open < $hist_Low[1] && 
			$Close > $hist_Close[1] && 
			$Close <= $hist_Close[1] + (($hist_High[1] - $hist_Low[1]) * 0.05) && 
			$trend < 0) {
			
			$pattern = 'In Neck Line C-';
			array_push($patterns, 'In Neck Line C-');
			array_push($pat_len, 2);
		}

		//thrusting
		if ($hist_body_type[1] == 'Long' && 
			$body_type != 'Doji' && 
			$hist_body_day[1] == 'Long' && 
			$body_day == 'Long' && 
			$color == 'Black' && 
			$hist_color[1] == 'White' && 
			$Open > $hist_High[1] + (($hist_High[1] - $hist_Low[1]) * 0.3) && 
			$Close >= (($hist_Open[1] + $hist_Close[1]) / 2) && 
			$Close < $hist_Close[1] && 
			$hist_trend[1] > 0) {
			
			$pattern = 'Thrusting C+';
			array_push($patterns, 'Thrusting C+');
			array_push($pat_len, 2);
		}
		if ($hist_body_type[1] == 'Long' && 
			$body_type != 'Doji' && 
			$color == 'White' && 
			$hist_color[1] == 'Black'&& 
			$Open < $hist_Low[1] && 
			$Close > $hist_Close[1] - (($hist_High[1] - $hist_Low[1]) * 0.05) && 
			$Close <= (($hist_Open[1] + $hist_Close[1]) / 2) && 
			$hist_trend[1] < 0) {
			
			$pattern = 'Thrusting C-';
			array_push($patterns, 'Thrusting C-');
			array_push($pat_len, 2);
		}
		
		//upside tasuki gap (example aes : 20000809)
		if ($body_type != 'Doji' && 
			$hist_body_type[1] != 'Doji' && 
			$hist_body_type[2] != 'Doji' &&	
			$color == 'Black' && 
			$hist_color[1] == 'White' && 
			$hist_color[2] == 'White' &&	
			$hist_Close[2] < $Close && 
			$hist_Open[1] > $Close && 
			$hist_Close[1] >= $Open && 
			$hist_Open[1] <= $Open &&	// last day must open in 2nd day's body
			$hist_trend[2] > 0) {
			
			$pattern = 'Upside Tasuki Gap C+';
			array_push($patterns, 'Upside Tasuki Gap C+');
			array_push($pat_len, 3);
		}
		//downside tasuki gap 
		if ($body_type != 'Doji' && 
			$hist_body_type[1] != 'Doji' && 
			$hist_body_type[2] != 'Doji' &&	
			$color == 'White' && 
			$hist_color[1] == 'Black' && 
			$hist_color[2] == 'Black' &&	
			$body_top < $hist_body_bottom[2] && 
			$body_top > $hist_body_top[1] && 
			$body_bottom > $hist_body_bottom[1] && 
			$body_bottom	< $hist_body_top[1] && // last day must open in 2nd day's body
			$hist_trend[2] < 0) {
			
			$pattern = 'Downside Tasuki Gap C-';
			array_push($patterns, 'Downside Tasuki Gap C-');
			array_push($pat_len, 3);
		}

		//side by side white lines
		if ($body_type != 'Doji' && 
			$hist_body_type[1] != 'Doji' && 
			$hist_body_type[2] == 'Long' &&	
			$color == 'White' && 
			$hist_color[1] == 'White' && 
			$hist_color[2] == 'White' &&	
			$hist_body_top[2] < $hist_body_bottom[1] && 
			$hist_body_top[2] < $body_bottom &&
			abs(($hist_Open[1] - $Open) / $Open * 100)	< 1 &&	 // last two days must have similar closes (with 1 %)
			$hist_trend[2] > 0) {
			
			$pattern = 'Side by Side White Lines C+';
			array_push($patterns, 'Side by Side White Lines C+');
			array_push($pat_len, 3);
		}
		if ($body_type != 'Doji' && 
			$hist_body_type[1] != 'Doji' && 
			$hist_body_type[2] == 'Long' &&	
			$color == 'White' && 
			$hist_color[1] == 'White' && 
			$hist_color[2] == 'Black' &&	
			$hist_body_bottom[2] > $hist_body_top[1] && 
			$hist_body_bottom[2] > $body_top &&
			abs(($hist_Close[1] - $Close) / $Close * 100) < 1 &&	 // last two days must have similar closes (with 1 %)
			$hist_trend[2] < 0) {
			
			$pattern = 'Side by Side White Lines C-';
			array_push($patterns, 'Side by Side White Lines C-');
			array_push($pat_len, 3);
		}
		
		//side by side black lines
		if ($body_type != 'Doji' && 
			$hist_body_day[2] == 'Long' && 
			$hist_body_type[1] != 'Doji'	&&	
			$color == 'Black' && 
			$hist_color[1] == 'Black' && 
			$hist_color[2] == 'White' &&	
			$hist_body_top[2] < $hist_body_bottom[1] && 
			$hist_body_top[2] < $body_bottom &&
			abs(($hist_Close[1] - $Close) / $Close * 100) < 1 && // last two days must have similar closes (with 1 %)
			$Open > (($hist_Open[1] + $hist_Close[1]) / 2) && // opens above mid of previous day
			$hist_trend[2] > 0) {
			
			$pattern = 'Side by Side Black Lines C+';
			array_push($patterns, 'Side by Side Black Lines C+');
			array_push($pat_len, 3);
		}
		
		if ($body_type != 'Doji' && 
			$hist_body_day[2] == 'Long' && 
			$hist_body_type[1] != 'Doji'	&&	
			$color == 'Black' && 
			$hist_color[1] == 'Black' && 
			$hist_color[2] == 'Black' &&	
			$hist_body_bottom[2] > $hist_body_top[1] && 
			$hist_body_bottom[2] > $body_top &&
			abs(($hist_Open[1] - $Open) / $Open * 100)	< 1 &&	 // last two days must have similar closes (with 1 %)
			(($hist_High[1] + $hist_Low[1]) > (($High + $Low) * 0.5) || ($High + $Low) > (($hist_High[1] + $hist_Low[1]) * 0.5) ) && // 2nd and 3rd days similar size; page 250
			$hist_trend[2] < 0) {
			
			$pattern = 'Side by Side Black Lines C-';
			array_push($patterns, 'Side by Side Black Lines C-');
			array_push($pat_len, 3);
		}		

		//upside gap 3 methods
		if ($body_type != 'Doji' && 
			$hist_body_type[2] == 'Long' && 
			$hist_body_type[1] == 'Long' &&	
			$color == 'Black' && 
			$hist_color[1] == 'White' && 
			$hist_color[2] == 'White' &&	
			$hist_body_top[2] < $hist_body_bottom[1] &&
			$hist_body_top[2] > $body_bottom && 
			$hist_body_bottom[2] < $body_bottom && 
			$hist_body_top[1] > $body_top && 
			$hist_body_bottom[1] < $body_top &&
			$hist_trend[2] > 0) {
			
			$pattern = 'Upside Gap 3 Methods C+';
			array_push($patterns, 'Upside Gap 3 Methods C+');
			array_push($pat_len, 3);
		}
		
		//downside gap 3 methods; example: c 20090121
		if ($body_type != 'Doji' && 
			$hist_body_type[2] == 'Long' && 
			$hist_body_type[1] == 'Long' &&	
			$color == 'White' && 
			$hist_color[1] == 'Black' && 
			$hist_color[2] == 'Black' &&	
			$hist_body_top[1] < $hist_body_bottom[2] &&
			$hist_body_top[2] > $body_top && 
			$hist_body_bottom[2] < $body_top && 
			$hist_body_top[1] > $body_bottom && 
			$hist_body_bottom[1] < $body_bottom &&
			$hist_trend[2] < 0) {
			
			$pattern = 'Downside Gap 3 Methods C-';
			array_push($patterns, 'Downside Gap 3 Methods C-');
			array_push($pat_len, 3);
		}

		//rest after battle
		if ($hist_body_type[2] == 'Long' && 
			$hist_color[2] == 'White' &&	
			$hist_body_top[1] > $hist_Close[2] &&
			$hist_Low[1] < $hist_High[2] &&
			$Open < $hist_High[1] && 
			$Close < $hist_High[1] && 
			$Open > $hist_Low[1] && 
			$Close > $hist_Low[1] &&
			$Low > ($hist_High[2] + $hist_Low[2]) / 2 &&
			($hist_high_low[2] * 0.75) > $high_low && 
			($hist_high_low[2] * 0.75) > $hist_high_low[1] && 
			$body_day == 'Short' && 
			$hist_body_day[1] == 'Short' &&
			$hist_trend[2] > 0) {
			
			$pattern = 'Rest After Battle C+';
			array_push($patterns, 'Rest After Battle C+');
			array_push($pat_len, 3);
		}

		//rising 3 methods
		if ($hist_body_type[4] == 'Long' && 
			$hist_body_type[3] == 'Short' && 
			$hist_body_type[2] == 'Short' && 
			$hist_body_type[1] == 'Short' && 
			$body_type == 'Long' &&
			$hist_color[4] == 'White' &&
			$hist_color[3] == 'Black' &&
			$hist_color[1] == 'Black' && 
			$color == 'White' &&
			$hist_body_top[3] < $hist_High[4] && 
			$hist_body_bottom[3] > $hist_Low[4] &&
			$hist_body_top[2] < $hist_High[4] && 
			$hist_body_bottom[2] > $hist_Low[4] &&
			$hist_body_top[1] < $hist_High[4] && 
			$hist_body_bottom[1] > $hist_Low[4] &&
			( ($hist_High[3] + $hist_Low[3]) / 2) > (($hist_High[1] + $hist_Low[1]) / 2) && 
			$Close > $hist_Close[4] && 
			$hist_trend[4] > 0) {
			
			$pattern = 'Rising 3 Methods C+';
			array_push($patterns, 'Rising 3 Methods C+');
			array_push($pat_len, 5);
		}
		
		//falling 3 methods - example ABC 20040205
		if ($hist_body_type[4] == 'Long' && 
			$hist_body_type[3] == 'Short' && 
			$hist_body_type[2] == 'Short' && 
			$hist_body_type[1] == 'Short' && 
			$body_type == 'Long' &&
			$hist_color[4] == 'Black' &&	
			$hist_color[3] == 'White' &&	
			$hist_color[1] == 'White' && 
			$color == 'Black' &&
			$hist_body_top[3] < $hist_High[4] && 
			$hist_body_bottom[3] > $hist_Low[4] &&
			$hist_body_top[2] < $hist_High[4] && 
			$hist_body_bottom[2] > $hist_Low[4] &&
			$hist_body_top[1] < $hist_High[4] && 
			$hist_body_bottom[1] > $hist_Low[4] &&
			( ($hist_High[3] + $hist_Low[3]) / 2) < (($hist_High[1] + $hist_Low[1]) / 2) && 
			$Close < $hist_Close[4] && 
			$hist_trend[4] < 0) {
			
			$pattern = 'Falling 3 Methods C-';
			array_push($patterns, 'Falling 3 Methods C-');
			array_push($pat_len, 5);
		}
		
		//mat hold
		if ($hist_color[4] == 'White' && 
			$hist_color[3] == 'Black' && 
			$color == 'White' &&
			$hist_body_type[4] == 'Long' && 
			$body_type == 'Long' && 
			$hist_body_top[4] < $hist_body_bottom[3] &&
			$hist_body_top[2] < $hist_body_top[3] && 
			$hist_body_bottom[2] < $hist_body_bottom[3] &&
			$hist_body_top[1] < $hist_body_top[2] && 
			$hist_body_bottom[1] < $hist_body_bottom[2] &&
			$Close > $hist_body_top[3] && 
			$hist_trend[4] > 0 ) {
			
			$pattern = 'Mat Hold C+';
			array_push($patterns, 'Mat Hold C+');
			array_push($pat_len, 5);
		}
		if ($hist_color[4] == 'Black' && 
			$hist_color[3] == 'White' && 
			$color == 'Black' &&
			$hist_body_type[4] == 'Long' && 
			$body_type == 'Long' && 
			$hist_body_bottom[4] > $hist_body_top[3] &&
			$hist_body_top[2] > $hist_body_top[3] && 
			$hist_body_bottom[2] > $hist_body_bottom[3] &&
			$hist_body_top[1] > $hist_body_top[2] && 
			$hist_body_bottom[1] > $hist_body_bottom[2] &&
			$Open < $hist_Close[1] && 
			$Close < $hist_Open[3] && 
			$hist_trend[4] < 0 ) {
			
			$pattern = 'Mat Hold C-';
			array_push($patterns, 'Mat Hold C-');
			array_push($pat_len, 5);
		}

		//3 line strike
		if ($hist_color[3] == 'White' && 
			$hist_color[2] == 'White' && 
			$hist_color[1] == 'White' && 
			$color == 'Black' &&
			$hist_body_type[3] != 'Doji' && 
			$hist_body_type[2] != 'Doji' && 
			$hist_body_type[1] != 'Doji' && 
			$body_type == 'Long' && 
			$hist_body_top[3] < $hist_body_top[2] && 
			$hist_body_top[2] < $hist_body_top[1] && 
			$hist_body_top[1] < $body_top && 
			$body_bottom < $hist_body_bottom[3] &&
			$hist_trend[3] > 0 ) {
			
			$pattern = '3 Line Strike C+';
			array_push($patterns, '3 Line Strike C+');
			array_push($pat_len, 4);
		}
		if ($hist_color[3] == 'Black' && 
			$hist_color[2] == 'Black' && 
			$hist_color[1] == 'Black' && 
			$color == 'White' &&
			$hist_body_type[3] != 'Doji' && 
			$hist_body_type[2] != 'Doji' && 
			$hist_body_type[1] != 'Doji' && 
			$body_type == 'Long' && 
			$hist_body_top[3] > $hist_body_top[2] && 
			$hist_body_top[2] > $hist_body_top[1] && 
			$body_top > $hist_body_top[3] && 
			$body_bottom < $hist_body_bottom[1] &&
			$hist_trend[3] < 0 ) {
			
			$pattern = '3 Line Strike C-';
			array_push($patterns, '3 Line Strike C-');
			array_push($pat_len, 4);
		}
		
		
		
		$patternList = implode(", ", $patterns);
		$lengthList = implode(", ", $pat_len);
		//$lengthList = $pat_len;
		//$patternList =~ s/, $//;
		array_push($CandlePatterns, $patternList);
		array_push($CandlePatLengths, $lengthList);
		array_push($CandleColor, $color);
		array_push($CandleBody, $body_day);
		array_push($CandleTrend, $trend);	
		
	}
	// trend = current close compared to 5-day sma; in %
	
	//return array(array_slice($CandlePatterns, 10), array_slice($CandlePatLengths, 10)); 
	return array(array_slice($CandleColor, 10), array_slice($CandleTrend, 10), array_slice($CandlePatterns, 10), array_slice($CandlePatLengths, 10));
}


?>
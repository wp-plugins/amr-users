<?php



/* -------------------------------------------------------------------------------------------------------------*/
	function ausersort2(  $one, $sdir1 = SORT_ASC, $two, $sdir2 = SORT_ASC,  $data) {
		// Obtain a list of columns
		foreach ($data as $key => $row) {
		    $one1[$key]  = $row[$one];
		    $two2[$key] = $row[$two];
		}
		// Add $data as the last parameter, to sort by the common key
		array_multisort($one1, $sdir1, $two2, SORT_ASC, $data);

		return ($data);
	}

/* -------------------------------------------------------------------------------------------------------------*/
	function ausersort1( $one, $data) {
		// Obtain a list of columns
		foreach ($data as $key => $row) {
		    $one1[$key]  = $row[$one];
		}
		array_multisort($one1, SORT_ASC, $data);
		return ($data);
	}



/* -------------------------------------------------------------------------------------------------------------*/
	function auser_sortbyother( $sort, $other) {
	/* where  other is in an order that we want the sort array to be in .  Note nulls or emptyies to end */
		// Obtain a list of columns
	//	echo '<br>Sort = ';	var_dump($sort);
	//	echo '<br><br>other = ';	var_dump($other);	
	//	echo '<br>';

		if (empty($other)) return ($sort);
		$temp = $sort; 
		foreach ($other as $key => $row) {
		    $s2[$key]  = $temp[$key];
			unset ($temp[$key]);
		}
	//	echo '<br><br>temp (remainder) = ';
	//	var_dump($temp);
		
	//	echo '<br><br>s2 = ';
	//	var_dump($s2);	
	//		echo '<br>';
		if (count($temp) > 0) return (array_merge ($s2, $temp));
		else return ($s2);
	}


/* -------------------------------------------------------------------------------------------------------------*/

	function amr_to_csv ( $csv, $name='List-') {
	/* create a csv file for download */

		$file = $name.date('YmdHis').'.csv';
		header("Content-Description: File Transfer");
		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=$file");
		header("Pragma: no-cache");
		header("Expires: 0");
		echo $csv;
		exit(0);   /* Terminate the current script sucessfully */	
	}
/* -------------------------------------------------------------------------------------------------------------*/


If (ICAL_EVENTS_DEBUG) {
		echo '<br />Plugin Version is: '.AMR_ICAL_VERSION;
		echo '<br />Php Version is: '.PHP_VERSION;
}		
		
if (function_exists ('get_option')) {
	if ($d = get_option ('date_format')) $amr_formats['Day'] = $d;		
	if ($d = get_option ('time_format')) $amr_formats['Time'] = $d;	
	if ($a_tz = get_option ('timezone_string') ) {
			$amr_globaltz = timezone_open($a_tz);
			date_default_timezone_set($a_tz);
			If (ICAL_EVENTS_DEBUG or isset($_REQUEST['tzdebug'])) {
				echo '<br />Found tz string:'.$a_tz;
				}
		}
	else {	
		If (ICAL_EVENTS_DEBUG or isset($_REQUEST['tzdebug'])) {	echo '<h2>No timezone string found.</h2>';		}
		if (($gmt_offset = get_option ('gmt_offset')) and (!(is_null($gmt_offset))) and (is_numeric($gmt_offset))) {
			$a_tz = amr_getTimeZone($gmt_offset);
			$amr_globaltz = timezone_open($a_tz);
			date_default_timezone_set($a_tz);
			If (ICAL_EVENTS_DEBUG or isset($_REQUEST['tzdebug'])) {
				echo '<h2>Found gmt offset in wordpress options:'.$gmt_offset.'</h2>';
			}
		}
		else {
			$amr_globaltz = timezone_open(date_default_timezone_get());		
			
		}
	}
	
}
else $amr_globaltz = timezone_open(date_default_timezone_get());
If (ICAL_EVENTS_DEBUG or isset($_REQUEST['tzdebug'])) echo '<br />The default php timezone is set to:'.date_default_timezone_get().'<br />';




?>
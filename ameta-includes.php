<?php 
/*
*/
define('AMETA_NAME','amr-users'); 
if ( !defined('WP_SITEURL') )
	define( 'WP_SITEURL', get_option('wp_url')); 

if (!(defined('AMR_NL'))) {
    define('AMR_NL',"\n");
}


/* -------------------------------------------------------------------------------------------------------------*/	

function auser_novalue ($v) {
/* since empty returns true on 0 and 0 is valid , use this instead */
return (empty($v) or (strlen($v) <1));
};
/* -------------------------------------------------------------------------------------------------------------*/	

function ameta_defaultnicenames () {
global $wpdb;     /* setup some defaults - get all the available keys - look up if any nice names already entered, else default some.   */

$nicenames = array (
	'user_login' => __('User name',AMETA_NAME),
	'user_nicename'=> __('Nice name',AMETA_NAME),
	'user_email' => __('Email',AMETA_NAME),
	'user_url' => __('Url',AMETA_NAME),
	'user_registered' => __('Registration date',AMETA_NAME)
);

$list = amr_get_alluserkeys();  /* maybe only do this if a refresjh is required ? No only happens on admin anyway ? */

/**** wp has changed - need to alllow for prefix now on fields */
foreach ($list as $i => $v) {
	if (!(isset( $nicenames[$i]))) 	{ /* set a reasonable default nice name */
		$nicenames[$i] = (str_replace('-', ' ',$i));
		if (isset ($wpdb->prefix)){$nicenames[$i] = str_replace ($wpdb->prefix, '', $nicenames[$i]);} 
		/* Note prefix has underscore*/
		$nicenames[$i] = ucwords (str_replace('_', ' ',$nicenames[$i]));
	}
}

return ($nicenames);
}


/* -------------------------------------------------------------------------------------------------------------*/	

function ameta_defaultoptions () {
/* setup some list defaults */

$sortdir = array ( /* some fields should always be sorted in a certain order, so keep that fact, even if not sorting by it*/
					'user_registered' => 'SORT_DESC',
					'ym_user-expire_date' => 'SORT_DESC',
					'post_count' => 'SORT_DESC',
					'comment_count' => 'SORT_DESC'
					);

$default = array (
	'list' => 
		array ( '1' => 
				array(
				'selected' => array ( 
					'user_login' => 1, 
					'user_email' => 2,
					'user_registered' => 5,
					'first_name' => 3,
					'last_name' => 4
					),
				'sortdir' => $sortdir,
				'sortby' => array ( 
					'user_email' => '1'
					),
				'links' => array ( 
					'user_email' => 'mailto',
					'post_count' => 'postbyauthor' /* author=id */
					),
				),
				'2' => 
				array(
				'selected' => array ( 
					'user_login' => 1, 
					'user_registered' => 2,
					's2_subscribed' => 3,
					's2_autosub' => 4,
					's2_format' => 5,
					'ym_user-expire_date' => 6,
					'ym_user-account_type' => 7
					),
				'included' => array ( 
					'user_status' => array('active')
					),
				'excludeifblank' => array (
					'first_name' => true
					),	
				'excluded' => array ( 
					'user_level' => array('10', '8')
					),
				'sortby' => array ( 
					'ym_user-expire_date' => '1',
					'user_login' => '2'
					)		
				),
				'3' => 
				array(
				'selected' => array ( 
					'user_login' => 1, 
					'user_nicename' => 2,
					'post_count' => 3,
					'comment_count' => 4
					),
				'sortby' => array ( 
					'post_count' => '1',
					'comment_count' => '2'
					),
				'sortdir' => $sortdir					
				)
			),
	'stats' => array ( '1' => 
				array(
					'selected' => $selected,
					'totals' => array ( /* within the selected */
						'ym_status' ,
						'account_type'
						)
				),
				'2' => 
				array(
				'selected' => $selected,
				'totals' => array ( /* within the selected */
						'ym_status' ,
						'expire_date'
						)
				)
			)
		);

	return ($default);

}	
/* -------------------------------------------------------------------------------------------------------------*/	

function ameta_defaultmain () {
/* setup some defaults */

$default = array (
    'no-lists' => 3,
	'names' => 
		array ( '1' => __("Users: Details", AMETA_NAME),
				'2' => __("Users: Member status and dates", AMETA_NAME),
				'3' => __("Users: Post and Comment Counts", AMETA_NAME)
				)
	);
				
	return ($default);

}	
/* -------------------------------------------------------------------------------------------------------------*/	
function ameta_no_lists(){
/* Return an array of no lists ansd array of names - may need to convert for a while */
	if ($a = get_option (AMETA_NAME.'-no-lists'))  {
		return($a)	;	
		}
	else {
		if ($b = get_option (AMETA_NAME)) {
			if (isset($b['no-lists']) ) {/* old version */
				$a['no-lists'] = $b['no-lists'];
				if (isset ($b['list'])) {
					foreach ($b['list'] as $i=>$l ) {
						$a['names'][$i] = $l['name'];
					}
				}
				update_option(AMETA_NAME.'-no-lists',$a );
				return($a);
				}
			else return ($a = ameta_defaultmain());	
		}
		else return ($a = ameta_defaultmain());
	}
}
/* -------------------------------------------------------------------------------------------------------------*/	
function ameta_nicenames (){
/* amr lists already done */
global $amr_nicenames;
	
	$target = ameta_defaultnicenames();
	/* chcek if we have options already in Database., if not, use default, else overwrite .	Add any new fields in */
	if ($a = get_option (AMETA_NAME.'-nicenames')) {
		array_replace ($target, $a);
		/* array_replace() replaces the values of the first array  with the same values from all the following arrays. If a key from the first array exists in the second array, its value will be replaced by the value from the second array. If the key exists in the second array, and not the first, it will be created in the first array. If a key only exists in the first array, it will be left as is. If several arrays are passed for replacement, they will be processed in order, the later arrays overwriting the previous values. */
		}
	return($target);
}

/* -------------------------------------------------------------------------------------------------------------*/	
function ameta_options (){

global $aopt;
global $amr_lists;
global $amr_nicenames;

	if (!isset ($amr_lists) ) $amr_lists = ameta_no_lists();
	$amr_nicenames = ameta_nicenames();
	$num = ($amr_lists['no-lists']); 
	$default = ameta_defaultoptions();

	/* chcek if we have options already in Database., if not, use default, else overwrite */
	if ($a = get_option (AMETA_NAME)) {
		if (isset ($a['list'])) {
			if ($num > count ($a['list']))  /* if we have a request for more lists */
				for ($i = $num+1; $i <= $num; $i++) $a['list'][$i] = $a['list'][1];
			else if ($num < count ($a['list'])) /* if we have a request for more lists */
				for ($i = $num+1; $i <= count($a['list']); $i++)	{ unset($a['list'][$i]);}		
		}
		else $a = $default;
	}	
	else $a = $default;
	
	return($a);
}

/* -------------------------------------------------------------------------------------------------------------*/	
function agetnice ($v){
global $amr_nicenames;
	if (isset ($amr_nicenames[$v])) 
		return ($amr_nicenames[$v]);
	else return ($v);	
	/*** ideally check for table prefix and string out for newer wordpress versions ***/
}
/** -----------------------------------------------------------------------------------*/ 
	function get_all_metaarraykeys($v) {
	global $wpdb;
    $results = $wpdb->get_results( 
		'SELECT meta_value FROM $wpdb->usermeta WHERE meta_key = "'.$v.'"'); 
	$t = array();	
	foreach ($results as $i => $arr) {
		if (is_array($arr))		$t = merge ($t, $arr);
		else 'Unexpected Data: Mixed Meta value Type found for meta key :'.$v;
		}
	return ($t);	

	}
/** -----------------------------------------------------------------------------------*/ 	
/*
    * Convert an object to an array
    *
    * @param    object  $object The object to convert
    * @reeturn      array
    *
    */
function objectToArray( $object ) {
/* useful for converting any meta values that are objects into arrays */

	 if (gettype ($object) == 'object') {
		$s =  (get_object_vars ($object));
			if (isset ($s['__PHP_Incomplete_Class_Name'])) unset ($s['__PHP_Incomplete_Class_Name']);
		/*		forced access */
			return($s);
		 }
	else if (is_array ($object)) return array_map( 'objectToArray', $object ); /* repeat function on each value of array */
	else return ($object );
}
 
/** -----------------------------------------------------------------------------------*/ 
function amr_excluded_userkey ($i) {
/* excludes ome less than useful keys to reduce the list a bit */
		if (stristr ($i, 'autosave_draft_ids')) return (true);
		if (stristr ($i, 'usersettings')) return (true);
		if (stristr ($i, 'user_pass')) return (true);
		if (stristr ($i, 'activation_key')) return (true);
		return (false);
		
	}
/** ----------------------------------------------------------------------------------- */

function amr_get_users_of_blog( $id = '' ) {
	global $wpdb, $blog_id;
	if ( empty($id) )
		$id = (int) $blog_id;
		$users = $wpdb->get_results( "SELECT ID FROM $wpdb->users" );
	return ($users);
	}

/* -----------------------------------------------------------------------------------*/ 	
function amr_get_alluserdata(  ) {

/*  get all user data and attempt to extract out any object values into arrays for listing  */

global $wpdb;

	$all = amr_get_users_of_blog(); /* modified form of  wordpress function to pick up user entries with no meta */
 
//	if (is_admin()) echo '<span class="inprogress1">Refreshing info from user tables.'; 
	foreach ($all as $i => $arr) {
		/* arr are objects  */
//		if (is_admin()) echo ' .'; 
		if ($uobjs[$i] = get_userdata($arr->ID)) {
			foreach ($uobjs[$i] as $i2 => $v2) {

			/* Excluded non useful stuff */
				if (!amr_excluded_userkey($i2) ) {
					$temp = maybe_unserialize ($v2);
					$temp = objectToArray ($temp); /* *must do all so can cope with incomplete objects */
					$key = str_replace(' ','_', $i2); /* html does not like spaces in the names*/
					if (is_array($temp)) { 
						foreach ($temp as $i3 => $v3) {
							$key = $i2.'-'.str_replace(' ','_', $i3); /* html does not like spaces in the names*/
							$users[$i][$key] = $v3;
							}
						unset ($users[$i][$key]);	/*** do we really need this */
						}
					else $users[$i][$key] = $v2;
				}
			}
		}
	}
//	if (is_admin()) echo '</span>'; 
	
return ($users);	
}
/** ----------------------------------------------------------------------------------- */
function amr_get_alluserkeys(  ) {

global $wpdb;
/*  get all user data and attempt to extract out any object values into arrays for listing  */

	$keys = array('comment_count'=>'comment_count', 'post_count'=>'post_count');
	if ($users = amr_get_alluserdata()) {
		foreach ($users as $i => $v ) {
			$keys = array_merge ($keys, $v);	
		}
	}
	return($keys);
}

/** ----------------------------------------------------------------------------------- */
 
function amr_get_usermetavalues( $selected ) {

/*  get the selected meta values from the user meta table amd attempt to extract out any object values into arrays for listing  */
/* get all the meat values in one query - faster? maybe than multiple get user data queries ?*/
global $wpdb;


	$u =   "SELECT * FROM $wpdb->user";
	$results = $wpdb->get_results($u, ARRAY_A); 

	$s = '(';
	foreach ($selected as $i => $v) {
	/* if not a meta key, then get separately ? */

		if (!($s==='(')) { $s .= ', '; }
		$s .=  '\''.$i.'\'';	
		}
	$s .= ')';
	$q =  "SELECT user_id, meta_key, meta_value FROM $wpdb->usermeta WHERE meta_key IN ".$s;

	
    $results = $wpdb->get_results($q, ARRAY_A);   
 
	foreach ($results as $i => $arr) {
		$a = maybe_unserialize ($arr['meta_value' ]);
		$a2 = objectToArray( $a ); 
		if (is_array ($a2)) { /* then flatten the array */
			foreach ($a2 as $i2 => $v2) {
				$metas [$arr['user_id']][$i2] = $v2;
			}
		}
		else {	
			$metas [$arr['user_id']][$arr['meta_key']] = $arr['meta_value' ];
		}
	}
	/* Prepare for IN query */
	$selected_users = "(";
	foreach ($metas as $userid => $data) {$selected_users .= "\"".$userid."\",";}
	$selected_users = substr($selected_users, 0, -1).")";
	

	
	/* Now we have the meta values and thus also the users, lets get the basic data */
//	foreach ($metas as $userid => $data) {
//		$user = get_userdata($userid);
	$s = $wpdb->prepare("SELECT * FROM $wpdb->users");
	//WHERE ID IN %s", $selected_users);

	$users = $wpdb->get_results($s,ARRAY_A);

	if ($users) {
		foreach ($users as $i => $v) {
			$metas[$i] = $v;
		}
	}
	
	return ($metas);
}
/* -------------------------------------------------------------------------------------------------------------*/
function amr_to_csv ($csv) {
/* create a csv file for download */

	$file = 'userlist-'.date('YmdHis').'.csv';
	header("Content-Description: File Transfer");
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=$file");
	header("Pragma: no-cache");
	header("Expires: 0");
	echo $csv;
	exit(0);   /* Terminate the current script sucessfully */	
}
/* -------------------------------------------------------------------------------------------------------------*/
function auser_msort($array, $cols)
{
/* Example: $arr2 = array_msort($arr1, array('name'=>array(SORT_DESC,SORT_REGULAR), 'cat'=>SORT_ASC));*/
    $colarr = array();
    foreach ($cols as $col => $order) {
        $colarr[$col] = array();
        foreach ($array as $k => $row) { $colarr[$col]['_'.$k] = strtolower($row[$col]); }
    }
    $params = array();
    foreach ($cols as $col => $order) {
        $params[] =& $colarr[$col];
        $params = array_merge($params, (array)$order);
    }
    call_user_func_array('array_multisort', $params);
    $ret = array();
    $keys = array();
    $first = true;
    foreach ($colarr as $col => $arr) {
        foreach ($arr as $k => $v) {
            if ($first) { $keys[$k] = substr($k,1); }
            $k = $keys[$k];
            if (!isset($ret[$k])) $ret[$k] = $array[$k];
            $ret[$k][$col] = $array[$k][$col];
        }
        $first = false;
    }
    return $ret;

}
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
function auser_usort( $a, $b) {
/* comparision function  - don't mess with it - it works - sorts strings to end, else in ascending order */
	if ($a == $b) return (0);
	else if (is_string($a) and (strlen($a) == 0)) return (1);
	else if (is_string($b) and (strlen($a) == 0)) return (-1);
	else return ($a<$b) ? -1: 1;
}
/* -------------------------------------------------------------------------------------------------------------*/
function auser_sortbyother( $sort, $other) {
/* where  other is in an order that we want the sort array to be in .  Note nulls or emptyies to end */
	// Obtain a list of columns
//	echo '<br>Sort = ';	var_dump($sort);
//	echo '<br><br>other = ';	var_dump($other);	
//	echo '<br>';

	
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

/* ---------------------------------------------------------------------*/	
?>
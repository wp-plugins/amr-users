<?php 
/*
*/
define('AMETA_NAME','amr-users'); 

if (!(defined('AMR_NL'))) {
    define('AMR_NL',"\n");
}

/* -------------------------------------------------------------------------------------------------------------*/	

function ameta_defaultoptions () {
global $wpdb;
/* setup some defaults *** wp has changed - need to alllow for prefix now  */

$nicenames = array (
	'user_login' => __('User name',AMETA_NAME),
	'user_nicename'=> __('Nice name',AMETA_NAME),
	'user_email' => __('Email',AMETA_NAME),
	'user_url' => __('Url',AMETA_NAME),
	'user_registered' => __('Registration date',AMETA_NAME)
);

$list = amr_get_alluserkeys();

foreach ($list as $i => $v) {
	if (!(isset( $nicenames[$i]))) 	{ /* set a reasonabe default nice name */
		$nicenames[$i] = (str_replace('-', ' ',$i));
		if (isset ($wpdb->prefix)){$nicenames[$i] = str_replace ($wpdb->prefix, '', $nicenames[$i]);} 
		/* Note prefix has underscore*/
		$nicenames[$i] = ucwords (str_replace('_', ' ',$nicenames[$i]));
	}
}

return ( array (
    'no-lists' => '3',
	'no-stats' => '2',
	'nicenames' => $nicenames,
	'list' => 
		array ( '1' => 
				array(
				'name' => __("User Details", AMETA_NAME),
				'selected' => array ( 
					'user_login' => 1, 
					'user_email' => 2,
					'user_registered' => 5,
					'first_name' => 3,
					'last_name' => 4
					),
				'sortby' => array ( 
					'1' => array('user_email','SORT_ASC')
					)					
				),		
				'2' => 
				array(
				'name' => __("Member status and dates", AMETA_NAME),
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
				'excluded' => array ( 
					'user_level' => array('10', '8'),
					),
				'sortby' => array ( 
					'1' => array('ym_user-expire_date','SORT_DESC'),
					'2' => array('user_login','SORT_ASC')
					)		
				),
				'3' => 
				array(
				'name' => __("User Post and Comment Counts", AMETA_NAME),
				'selected' => array ( 
					'user_login' => 1, 
					'user_nicename' => 2,
					'post_count' => 3,
					'comment_count' => 4
					),
				'sortby' => array ( 
					'1' => array('post_count','SORT_DESC'),
					'2' => array('comment_count','SORT_DESC')
					)		
				)
			),	
	'stats' => array ( '1' => 
				array(
					'name' => __("Member Stats", AMETA_NAME),
					'selected' => $selected,
					'totals' => array ( /* within the selected */
						'ym_status' ,
						'account_type'
						)
				),
				'2' => 
				array(
				'name' => __("Other Stats", AMETA_NAME),
				'selected' => $selected,
				'totals' => array ( /* within the selected */
						'ym_status' ,
						'expire_date'
						)
				)
			)
		)	
	);
}	
/* -------------------------------------------------------------------------------------------------------------*/	
function ameta_options (){
	$default = ameta_defaultoptions();
	/* chcek if we have options already in Database., if not, use default, else overwrite */
	if ($a = get_option (AMETA_NAME)) {
		foreach ($default as $i => $v) {
			if (!isset ($a[$i]))  $a[$i] = $v;  /* if the saved option does have this, it must  be new, so add in */
			else {
				if (is_array ($v)) {
					foreach ($v as $i2 => $v2) {
						if (!isset ($a[$i][$i2]))  $a[$i][$i2] = $v2;  /* if the saved option does have this, it must  be new, so add in */
						else 
							if (is_array ($v2)) 
								$a[$i][$i2] = array_merge ($v2, $a[$i][$i2]);  /* any string keys in a should overwrite the keys the default aopt */
					}
				}	
			}
		}
		return ($a);
	}
	else return($default);
}

/* -------------------------------------------------------------------------------------------------------------*/	
function agetnice ($v){
global $aopt;
	if (isset ($aopt['nicenames'][$v])) 
		return ($aopt['nicenames'][$v]);
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
 
	foreach ($all as $i => $arr) {
		/* arr are objects  */
		if ($uobjs[$i] = get_userdata($arr->ID)) {
			foreach ($uobjs[$i] as $i2 => $v2) {
			/* Excluded non useful stuff */
				if (!amr_excluded_userkey($i2) ) {
					$temp = maybe_unserialize ($v2);
					$temp = objectToArray ($temp); /* *must do all so can cope with incomplete objects */
					if (is_array($temp)) { 
						foreach ($temp as $i3 => $v3) {
							$users[$i][$i2.'-'.$i3] = $v3;
							}
						unset ($users[$i][$i2]);	
						}
					else $users[$i][$i2] = $v2;
				}
			}
		}
	}
	
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

	$file = 'memberlist-'.date('YmdHis').'.csv';
	header("Content-Description: File Transfer");
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=$file");
	header("Pragma: no-cache");
	header("Expires: 0");
	echo $csv;
	exit(0);   /* Terminate the current script sucessfully */	
}
/* -------------------------------------------------------------------------------------------------------------*/
?>
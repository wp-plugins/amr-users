<?php 

if ( !defined('WP_SITEURL') ) {
	if (defined('BB_PATH')) 	define( 'WP_SITEURL', BB_PATH); 
	else	define( 'WP_SITEURL', get_bloginfo('wpurl')); 
	}
if (!(defined('AMR_NL'))) { /* for new lines in code, so we can switch off */
    define('AMR_NL',"\n");
}
/* ---------------------------------------------------------------------------*/
if (!function_exists('amr_setDefaultTZ')) {/* also used in other amr plugins */
	function amr_setDefaultTZ() {
		if (function_exists ('get_option')) {
	/* Set the default php timezone, for various reasons wordpress does not do this, buut assumes  UTC*/
		$current_offset = get_option('gmt_offset');
		$tzstring = get_option('timezone_string');
		}
		else if (function_exists ('date_default_timezone_get'))  $tzstring = date_default_timezone_get();
		else $tzstring = 'UTC';

	/* (wop code: Remove old Etc mappings.  Fallback to gmt_offset. */
		if ( false !== strpos($tzstring,'Etc/GMT') )
			$tzstring = '';
		if (empty($tzstring)) { // Create a UTC+- zone if no timezone string exists
			if ( 0 == $current_offset )
				$tzstring = 'UTC+0';
			elseif ($current_offset < 0)
				$tzstring = 'UTC' . $current_offset;
			else
				$tzstring = 'UTC+' . $current_offset;
		}
	}
}




/* -------------------------------------------------------------------------------------------------------------*/	

function ameta_defaultnicenames () {
global $wpdb;     /* setup some defaults - get all the available keys - look up if any nice names already entered, else default some.   */

unset($nicenames);
$nicenames = array (
	'ID' => __('Id', 'amr-users'),
	'user_login' => __('User name','amr-users'),
	'user_nicename'=> __('Nice name','amr-users'),
	'user_email' => __('Email','amr-users'),
	'user_url' => __('Url','amr-users'),
	'user_registered' => __('Registration date','amr-users'),
	'user_status' => __('User status','amr-users'),
	'display_name' => __('Display Name','amr-users'),
	'first_name' => __('First name','amr-users'),
	'last_name' => __('Last name','amr-users'),
	'nick_name' => __('Nick Name','amr-users'),
	'post_count' => __('Post Count','amr-users'),
	'comment_count' => __('Comment Count','amr-users'),
	'first_role' => __('First Role', 'amr-users')
);

return ($nicenames);
}


/* -------------------------------------------------------------------------------------------------------------*/	

function ameta_defaultoptions () {
/* setup some list defaults */

$sortdir = array ( /* some fields should always be sorted in a certain order, so keep that fact, even if not sorting by it*/
					'user_registered' => 'SORT_DESC',
//					'ym_user-expire_date' => 'SORT_DESC',
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
				'links' => array (    /* not in use yet and may never be !*** */
					'user_email' => 'mailto',
					'post_count' => 'postbyauthor' /* author=id */
					),
				),
				'2' => 
				array(
				'selected' => array ( 
					'user_login' => 1, 
					'user_registered' => 2,
					'first_role' => 3
					),


				'sortby' => array ( 
//					'ym_user-expire_date' => '1',
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
				'excludeifblank' => array ( 
					'post_count'=> true),
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
    'rows_per_page' => 20,

    'no-lists' => 3,
	'names' => 
		array ( '1' => __("Users: Details", 'amr-users'),
				'2' => __("Users: Member status and dates", 'amr-users'),
				'3' => __("Users: Post and Comment Counts", 'amr-users')
				)
	);
				
	return ($default);

}	
/* -------------------------------------------------------------------------------------------------------------*/	
function ameta_no_lists(){
/* Return an array of no lists ansd array of names - may need to convert for a while */
	if ($a = get_option ('amr-users-no-lists'))  {
		return($a)	;	
		}
	else { /* if we do not have the option, then it may be an older version, or an unsaved version */
		if ($b = get_option ('amr-users')) {
			if (isset($b['no-lists']) ) {/* old version */
				$a['no-lists'] = $b['no-lists'];
				if (isset ($b['list'])) {
					foreach ($b['list'] as $i=>$l ) {
						$a['names'][$i] = $l['name'];
					}
					unset($b['list']);
				}
				update_option('amr-users'.'-no-lists',$a );
				update_option('amr-users',$b );
				return($a);
				}
			else return ($a = ameta_defaultmain());	
		}
		else return ($a = ameta_defaultmain());
	}
}



/* -------------------------------------------------------------------------------------------------------------*/	
function ameta_options (){

global $aopt;
global $amain;
global $amr_nicenames;

	$default = ameta_defaultoptions();
	$amain = ameta_no_lists();
	$amr_nicenames = get_option ('amr-users-nicenames');
	$num = ($amain['no-lists']); 

	/* chcek if we have options already in Database., if not, use default, else overwrite */
	if ($a = get_option ('amr-users')) {
		if (isset ($a['list'])) {
			if ($num > count ($a['list']))  /* if we have a request for more lists */
				for ($i = $num+1; $i <= $num; $i++) $a['list'][$i] = $a['list'][1];
			else if ($num < count ($a['list'])) /* if we have a request for more lists */
				for ($i = $num+1; $i <= count($a['list']); $i++)	{ unset($a['list'][$i]);}		
		}
		else $a = $default;
	}	
	else $a = $default;
	
	$aopt = $a;
	
	return;
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
function amr_excluded_userkey ($i) {
/* exclude some less than useful keys to reduce the list a bit */
		if (stristr ($i, 'autosave_draft_ids')) return (true);
		if (stristr ($i, 'usersettings')) return (true);
		if (stristr ($i, 'user_pass')) return (true);
		if (stristr ($i, 'user_activation_key')) return (true);
		if (stristr ($i, 'admin_color')) return (true);
		
/* and exclude some deprecated fields, since wordpress creates both for backward compatibility ! */		
		if (stristr ($i, 'user_description')) return (true);
		if (stristr ($i, 'user_lastname')) return (true);
		if (stristr ($i, 'user_firstname')) return (true);
		if (stristr ($i, 'user_level')) return (true);
		if (stristr ($i, 'metabox')) return (true);		
		if (stristr ($i, 'comment_shortcuts')) return (true);	
		if (stristr ($i, 'plugins_last_view')) return (true);	
		if (stristr ($i, 'rich_editing')) return (true);
		if (stristr ($i, 'closedpostboxes')) return (true);
		if (stristr ($i, 'columnshidden')) return (true);
		if (stristr ($i, 'screen_layout')) return (true);
		if (stristr ($i, 'metaboxhidden_')) return (true);	
		if (stristr ($i, 'metaboxorder_')) return (true);	
		if (stristr ($i, '_per_page')) return (true);		
		return (false);
		
	}
/** ----------------------------------------------------------------------------------- */

function amr_get_users_of_blog( $id = '' ) {
	global $wpdb, $blog_id;
	if ( empty($id) ) 		$id = (int) $blog_id;
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
		
		if (isset($_GET['udebug'])) {echo '<hr>'; print_r($arr);}
		
		
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
	unset($all);
return ($users);	
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

/* -----------------------------------------------------------------------------------*/
if (!function_exists('auser_msort')) {
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
}
/* -----------------------------------------------------------------------------------*/
if (!function_exists('amr_pagetext')) {
function amr_pagetext($thispage=1, $totalitems, $rowsperpage=30){ 
/* echo's paging text based on parameters - */

	$from = (($thispage-1) * $rowsperpage) + 1;
	$to = $from + $rowsperpage;
	$totalpages = ceil($totalitems / $rowsperpage);
	
	if (isset($_GET['listpage'])) $oldpage = $_GET['listpage'];
	$base = str_replace('&listpage='.$oldpage,'',$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']); 
	$base = str_replace('?listpage='.$oldpage,'',$base); /* just in case */
	
	$paging_text = paginate_links( array(  /* uses wordpress function */
				'total' => $totalpages,
				'current' => $thispage,
				'base' => $base.'%_%', // http://example.com/all_posts.php%_% : %_% is replaced by format (below)
				'format' => '&listpage=%#%', // ?page=%#% : %#% is replaced by the page number
				'end_size' => 2,
				'mid_size' => 1,
				'add_args' => $args
			) );
		if ( $paging_text ) {
				$paging_text = 
					'<div class="tablenav"><div class="tablenav-pages">'
					.sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>&nbsp;%s',
					number_format_i18n( $from ),
					number_format_i18n( $to ),
					number_format_i18n( $totalitems ),
					$paging_text
					.'</div></div>'
				);
			}
	return($paging_text);		
}
}
/* -------------------------------------------------------------------------------------------------------------*/	
if (!function_exists('in_current_page')) {
function in_current_page($item, $thispage, $rowsperpage ){
/* checks if the item by number should be in the current page or not */
	$ipage =  ceil ($item/$rowsperpage);
	return ($ipage == $thispage);
}
}

/* -------------------------------------------------------------------------------------------------------------*/
if (!function_exists('auser_sortbyother')) 
{
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
}

/* -------------------------------------------------------------------------------------------------------------*/
if (!function_exists('amr_usort')) {
	function amr_usort( $a, $b) {
	/* comparision function  - don't mess with it - it works - sorts strings to end, else in ascending order */
		if ($a == $b) return (0);
		else if (is_string($a) and (strlen($a) == 0)) return (1);
		else if (is_string($b) and (strlen($a) == 0)) return (-1);
		else return ($a<$b) ? -1: 1;
	}
}
/* ---------------------------------------------------------------------*/	
if (!function_exists('amr_csv_form')) {
	function amr_csv_form($csv) {
	/* accept a long csv string and output a form with it in the data - this is to keep private - avoid the file privacy issue */
	return (
//		  '<form method="post" action="" id="csvexp" ><fieldset >'.
		  '<input type="hidden" name="csv" value="'.htmlentities($csv) . '" />'.AMR_NL
		.  '<input style="font-size: 1.5em !important;" type="submit" name="reqcsv" value="'
		.__('Export to CSV','amr-users').'" class="button" />'
//		.  '</fieldset></form>'
		);
	}
}
/* ---------------------------------------------------------------------*/	

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
/* ---------------------------------------------------------------------*/	
if (!function_exists('amr_check_memory')) {
function amr_check_memory() { /* */

if (!function_exists(memory_get_peak_usage)) return(false);


		$mem_usage = memory_get_peak_usage(true);       
        if ($mem_usage < 1024)
            $html .= $mem_usage." bytes";
        elseif ($mem_usage < 1048576)
            $html .= round($mem_usage/1024,2)." KB"; /* kilobytes*/
        else
            $html .= round($mem_usage/1048576,2)." MB"; /* megabytes */

		return($html);
	}
}
/** -----------------------------------------------------------------------------------*/ 	
/*
    * Convert an object to an array
    *
    * @param    object  $object The object to convert
    * @reeturn      array
    *
    */
if (!function_exists('objectToArray')) {
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
}

/* ---------------------------------------------------------------------*/	
if (class_exists('adb_cache')) return;
{	global $wpdb;
	class adb_cache {
	var $table_name;
	
		
	/* A database table is used for the cacheing in order to keep the user data private - otherwise a csv file would be used */
	/* ---------------------------------------------------------------------- */
	function adb_cache() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . "amr_reportcache";
		$this->eventlog_table = $wpdb->prefix."amr_reportcachelogging";
		$this->localizationName = 'amr-users';
		$this->errors = new WP_Error();
		$this->errors->add('numoflists', __('Number of Lists must be between 1 and 40.','amr-users'));
		$this->errors->add('rowsperpage', __('Rows per page must be between 1 and 999.','amr-users'));
		$this->errors->add('nonamesarray',__('Unexpected Problem reading names of lists - no array','amr-users'));
		$this->errors->add('nocache',__('No cache exists for this report.','amr-users'));
		$this->errors->add('nocacheany',__('No cache exists for any reports.',$this->localizationName));
		$this->errors->add('inprogress',__('Cache update in progress.  Please wait a few seconds then refresh.','amr-users'));
		$this->tz = new DateTimeZone(date_default_timezone_get());

	}
	/* ---------------------------------------------------------------------- */
	function record_cache_peakmem ($reportid) {
	/* record the peak memory usage */
		$status = get_option ('amr-users-cache-status');
		$this->peakmem = $status[$reportid]['peakmem'] = amr_check_memory();	
		return(update_option ('amr-users-cache-status', $status));
	}
	/* ---------------------------------------------------------------------- */
	function record_cache_headings ($reportid, $html) {
	/* record the peak memory usage */
		$status = get_option ('amr-users-cache-status');
		$this->headings = $status[$reportid]['headings'] = $html;	
		return(update_option ('amr-users-cache-status', $status));
	}
	
	function record_cache_start ($reportid, $name) {
		$status = get_option ('amr-users-cache-status');
		unset ($status[$reportid]);
		unset ($this);
		$this->start = $status[$reportid]['start'] = time();
		$this->name = $status[$reportid]['name'] = $name;
		return(update_option ('amr-users-cache-status', $status));
	}
	function record_cache_end ($reportid, $lines) {
		$status = get_option ('amr-users-cache-status');
		$this->end = $status[$reportid]['end'] = time();
		$this->lines = $status[$reportid]['lines'] = $lines;
		$this->timetaken = $this->end - $this->start;
		return(update_option ('amr-users-cache-status', $status));
	}
	function cache_in_progress ($reportid) {
		$status = get_option ('amr-users-cache-status');
		if ((isset($status[$reportid]['start'])) and 
			(!isset($status[$reportid]['end'])))
			return(true);
		else return(false);			
	}
	function cache_already_scheduled ($list) {	
		$args[] = $list;
		if ($timestamp = wp_next_scheduled('amr_reportcacheing',$args)) { /*** fix*/
			$d = date_create(strftime('%c',$timestamp));
			$timetext = $d->format(get_option('date_format').' '.get_option('time_format'));
			$text = sprintf(__('Cache of list %s already scheduled for %s', 'amr-users'),$list,$timetext);
			return ($text);
		}
		else return(false);
	}

	function last_cache ($reportid) { /* the last successful cache */
		$status = get_option ('amr-users-cache-status');
		if ((isset($status[$reportid]['start'])) and 
			(isset($status[$reportid]['end'])))
			return(strftime('%c',round($status[$reportid]['end'])));
		else return(false);			
	}
	
	function cache_report_line ($reportid, $line, $csvcontent ) {
		global $wpdb;	
		$wpdb->show_errors();		
		$sql = "INSERT INTO " . $this->table_name .
            " ( reportid, line, csvcontent ) " .
            "VALUES ('" . $reportid . "','" . $line . "','" . $csvcontent . "')";

		$results = $wpdb->query( $sql );
		return ($results);
	}
		function log_cache_event($text) {

		global $wpdb;	
		$wpdb->show_errors();	
		
		$datetime = date_create('now', $this->tz);
		
		/* clean up oldder log entries first  if there are any */
		$old = date_create();
		$old = clone ($datetime);
		date_modify($old, '-1 day');
		$sql = "DELETE FROM " . $this->eventlog_table .
            " WHERE eventtime <= '" . date_format($old,'Y-m-d H:i:s') . "'";
		$results = $wpdb->query( $sql );

		/* now log our new message  */
		$sql = "INSERT INTO " . $this->eventlog_table .
            " ( eventtime, eventdescription ) " .
            "VALUES ('" . date_format($datetime,'Y-m-d H:i:s') . "','" . $text . "')";

		$results = $wpdb->query( $sql );
		return ($results);
	}
	


	function clear_cache ($reportid ) {
	global $wpdb;		
      $sql = "DELETE FROM " . $this->table_name .
             " WHERE reportid = '" . $reportid . "'";

      $results = $wpdb->query( $sql );
	  $opt = get_option('amr-users-cache-status');
	  unset ($opt[$reportid]);
	  update_option('amr-users-cache-status', $opt);
	  
	  return ($results);

	}
	function clear_all_cache () {
	global $wpdb;		
      $sql = "DELETE FROM " . $this->table_name;
      $results = $wpdb->query( $sql );
	  $opt = delete_option('amr-users-cache-status');

	  return ($results);
	}
	
	function cache_exists ($reportid ) {
	global $wpdb;			
		$sql = "SELECT line FROM " . $this->table_name .
             " WHERE reportid = '" . $reportid . "' LIMIT 1;";
		$wpdb->show_errors();
		$results = $wpdb->query( $sql );
	
	  return ($results);

	}
	
	/* -------------------------------------------------------------------------------------------------------------*/
	function reportid ( $i, $type='user') {
	if ($i < 10) return ($type.'-0'.$i);
	return ($type.'-'.$i);
	}
	
	function reportname ($i ) {
	global $amain;
		if (!($amain = get_option ('amr-users-no-lists'))) ameta_options();
		return($amain['names'][$i]);
	}
	function get_cache_totallines ($reportid ) {
		$status = get_option ('amr-users-cache-status');
		return($status[$reportid]['lines']); 
	}

	function get_cache_report_lines ($reportid, $start=1,  $rowsperpage ) {
		global $wpdb;	
		$wpdb->show_errors();		
		$sql = 'SELECT line, csvcontent FROM ' . $this->table_name
             .' WHERE reportid = "'. $reportid . '"'
			.' AND line >= "'.$start
			.' ORDER BY line'
			.'" LIMIT '.$rowsperpage.';';

		$results = $wpdb->get_results( $sql, ARRAY_A );
		if (empty($results)) return (false);
		return ($results);
	}
/* ---------------------------------------------------------------------- */		
		function cache_log () { /* Display the cache reporting log */
		global $wpdb;	
		
		$sql = 'SELECT id, eventtime, eventdescription FROM ' . $this->eventlog_table
			.' ORDER BY id DESC'
			.';';

		$results = $wpdb->get_results( $sql, ARRAY_A );
		if (empty($results)) return (false);
		foreach ($results as $i => $r ) {
			$html .= '<li>'.$r['eventtime'].' - '.$r['eventdescription'].'</li>';
		}	
		return ('<ul>'.$html.'</ul>');
	}
/* ---------------------------------------------------------------------- */	
	function cache_status () {
	/* show the cache status and offer to rebuild */
		global $wpdb;	
		global $amain;
		
		if (is_admin()) {
			if (!($amain = get_option ('amr-users-no-lists'))) 	 $amain = ameta_defaultmain();
		
			$wpdb->show_errors();		
			$sql = 'SELECT DISTINCT reportid AS "rid", COUNT(reportid) AS "lines" FROM ' . $this->table_name.' GROUP BY reportid';
			$results = $wpdb->get_results( $sql, ARRAY_A );  /* Now e have a summary of what isin the cache table - rid, lines */

			if ( is_wp_error($results) )	{	echo '<h2>'.$results->get_error_message().'</h2>';		return (false);			}
			else {		

				$status = get_option ('amr-users-cache-status');	/* No pickup the record of starts etc reportid, start   and reportid end*/							

				if (!empty($results)) foreach ($results as $i => $rpt) {
						$r = intval(substr($rpt['rid'],5));   /* *** skip the 'users' and take the rest */						
						$summary[$r]['rid'] =  $rpt['rid'];
						$summary[$r]['lines'] = $rpt['lines']  - 2; /* as first two liens are headers anyway*/
						$summary[$r]['name'] = $amain['names'][intval($r)];
						}
				else  echo $this->get_error('nocacheany'); 

				if (!empty($status)) foreach ($status as $rd => $se) {
					$r = intval(substr($rd,5));   /* *** skip the 'users' and take the rest */						
				if (empty( $se['end'])) {
				
					$summary[$r]['end'] = __('In progress', 'amr-users');
					$summary[$r]['time_since'] = __('?','amr-users');
					$summary[$r]['time_taken'] = __('?','amr-users');
					$summary[$r]['peakmem'] = __('?','amr-users');
					$summary[$r]['rid'] = $rd;
					$r = intval(substr($rd,5));   /* *** skip the 'users' and take the rest */		
					$summary[$r]['name'] = $amain['names'][intval($r)];
				}
				else {
			
						$summary[$r]['end'] = empty($se['end']) ? 'In progress' :date_i18n('D, j M H:i:s',$se['end']);  /* this is in unix timestamp not "our time" , so just say how long ago */
						$summary[$r]['start'] = date_i18n('D, j M Y H:i:s',$se['start']);  /* this is in unix timestamp not "our time" , so just say how long ago */

						$dt = new DateTime('now', $this->tz);
						$now = date_format( $dt,'D, j M Y H:i:s');
						$summary[$r]['time_since'] = human_time_diff ($se['end'],time());; /* the time that the last cache ended */		
						$summary[$r]['time_taken'] = $se['end'] - $se['start']; /* the time that the last cache ended */	
						$summary[$r]['peakmem'] = $se['peakmem'];
						$summary[$r]['headings'] = $se['headings'];
					}
				}	
						
				if (!empty($summary)) { 	
					echo  '<div class="wrap" style="padding-top: 20px;"><table class="widefat" style="width:auto; ">'
						.'<caption>'.__('Report Cache Status','amr_users').' </caption>'
						.'<thead><tr><th>'.__('Report Id', 'amr-users')
						.'</th><th>'.__('Name', 'amr-users')
						.'</th><th>'.__('Lines', 'amr-users')
						.'</th><th align="left">'.__('Ended?', 'amr-users')
						.'</th><th align="left">'.__('How long ago?', 'amr-users')
						.'</th><th align="left">'.__('Seconds taken', 'amr-users')
						.'</th><th align="left">'.__('Peak Memory', 'amr-users')
						.'</th><th align="left">'.__('Details', 'amr-users')
						.'</th></tr></thead>';	
					foreach ($summary as $rd => $rpt) {
						echo '<tr>'
						.'<td>'.$rpt['rid'].'</td>'
						.'<td>'.au_view_link($rpt['name'], $rd, '').'</td>'
						.'<td align="right">'.$rpt['lines'].'</td>'
						.'<td align="right">'.$rpt['end'].'</td>'
						.'<td align="right">'.$rpt['time_since'].'</td>'
						.'<td align="right">'.$rpt['time_taken'].'</td>'
						.'<td align="right">'.$rpt['peakmem'].'</td>'
						.'<td align="right">'.$rpt['headings'].'</td>'
						.'</tr>';
					}
				
					echo '</table></div>';
				}
			}
			
		}
		else echo '<h3>not admin?</h3>';
		
	echo '</div>';
	}
/* ---------------------------------------------------------------------- */		
			
	/* get_error - Returns an error message based on the passed code
	Parameters - $code (the error code as a string)
	Returns an error message */
	function get_error($code = '') {
		$errorMessage = $this->errors->get_error_message($code);
		if ($errorMessage == null) {
			return __("Unknown error.", $this->localizationName);
		}
		return $errorMessage;
	}
	/* ---------------------------------------------------------------------- */
	/* Initializes all the error messages */
//	function initialize_errors() {
//		$this->errors->add('numoflists', __('Number of Lists must be between 1 and 40.',$this->localizationName));
//		$this->errors->add('rowsperpage', __('Rows per page must be between 1 and 999.',$this->localizationName));
//		$this->errors->add('nonamesarray',__('Unexpected Problem reading names of lists - no array. ',$this->localizationName));
//		$this->errors->add('nocache',__('No cache exists for this report.  Please inform the administrator. ',$this->localizationName));
//		$this->errors->add('nocacheany',__('No cache exists for any reports.  Please inform the administrator. ',$this->localizationName));
//	} //end function initialize_errors
}
	
	/* ---------------------------------------------------------------------- */	


}



/* This holds common amr functions file - it may  be in several plugins  */
if (!function_exists('mimic_meta_box')) {
	function mimic_meta_box($id, $title, $callback ) {
	global $screen_layout_columns;

	//	$style = 'style="display:none;"';
		$h = (2 == $screen_layout_columns) ? ' has-right-sidebar' : '';
		echo '<div style="clear:both;" class="metabox-holder'.$h.'">';
		echo '<div class="postbox-container" style="width: 49%;">';
		echo '<div class="meta-box-sortables" style="min-height: 10px;">';
		echo '<div id="' . $id . '" class="postbox ' . 'if-js-closed' . '">' . "\n";
		echo '<div class="handlediv" title="' . __('Click to toggle') . '"><br /></div>';
		echo "<h3 class='hndle'><span>".$title."</span></h3>\n";
		echo '<div class="inside">' . "\n";
		call_user_func($callback);
		echo "</div></div></div></div></div>";
		
	}
}

/* -------------------------------------------------------------------------------------------------------------*/	
function amr_which_role($user_object, $role_no=1) {
/* The wordpress user role area is described in the wordpress code as a big mess  - I think the role business is one reason why */
/* This code is largely copied from  wordpress */
/* Wordpress alllows multiple or no roles.  However most users expect to see 1 role only */
global $wp_roles;

	if (empty($user_object->roles)) return (false);
	$roles = $user_object->roles;
	$role = array_shift($roles);

	if (isset($wp_roles->role_names[$role])) 
		$rolename = translate_user_role($wp_roles->role_names[$role] );
	else $role_name = $role;


	
	return ($rolename);
}
/* -------------------------------------------------------------------------------------------------------------*/	

if (!function_exists('a_novalue')) {
	function a_novalue ($v) {
	/* since empty returns true on 0 and 0 is valid , use this instead */
	return (empty($v) or (strlen($v) <1));
	};
}

/* ---------------------------------------------------------------------*/	

	function amr_flag_error ($text) {
		echo '<div class="error">'.$text.'</div>';
	}

/* ---------------------------------------------------------------------*/	

	function amr_message ($text) {
		echo '<div class="error">'.$text.'</div>';
	}


/* ---------------------------------------------------------------------*/
	function amr_feed($uri, $num=3, $text='Recent News',$icon="http://webdesign.anmari.com/images/amrusers-rss.png") {
	
	$feedlink = '<h3><a href="'.$uri.'">'.$text.'</a><img src="'.$icon.'" alt="Rss icon" style="vertical-align:middle;" /></h3>';	

	if (!function_exists (fetch_feed)) { 
		echo $feedlink;
		return (false);
		}
	if (!empty($text)) {?>
	<div>
	<h3><?php _e($text);?><a href="<?php echo $uri; ?>" title="<?php echo $text; ?>" >
	</a><img src="<?php echo $icon;?>"  alt="Rss icon" style="vertical-align:middle;"/></h3><?php
	}
	// Get RSS Feed(s)
	include_once(ABSPATH . WPINC . '/feed.php');
	include_once(ABSPATH . WPINC . '/formatting.php');
	// Get a SimplePie feed object from the specified feed source.
	$rss = fetch_feed($uri);
	if ( is_wp_error($rss) )   {
		echo $rss->get_error_message();
		echo $feedlink;
		return (false);
	}


	// Figure out how many total items there are, but limit it to 5. 
	$maxitems = $rss->get_item_quantity($num); 

	// Build an array of all the items, starting with element 0 (first element).
	$rss_items = $rss->get_items(0, $maxitems); 
	?>

	<ul class="rss_widget">
	    <?php if ($maxitems == 0) echo '<li>'.__('No items').'</li>';
	    else
	    // Loop through each feed item and display each item as a hyperlink.
	    foreach ( $rss_items as $item ) :  ?>
	    <li> 
	        <a href='<?php echo $item->get_permalink(); ?>'
	        title='	<?php echo $item->get_date('j F 2009'); ?>'>
	        <?php echo $item->get_title(); ?></a> 
			<?php echo balanceTags(substr($item->get_description(),0, 80)).'...'; ?>
	    </li>
	    <?php endforeach; ?>
		<li>...</li>
	</ul>
	</div>
	<?php }

	
	/* -----------------------------------------------------------*/
/* if (!defined('str_getcsv')) { */   /* if someone else has defined a better function, rather use that */
	function amr_str_getcsv ($string, $sep, $e1, $e2 ) {  /*** a pseudo function only  */
		$arr = explode( $sep, $string);

		$arr[0] = ltrim($arr[0], '"');
		$end = count($arr);
		$arr[$end-1] = rtrim($arr[$end-1],'"');
//		foreach ($arr as $i => $s) {
//			$arr[$i] = substr ($s, 1, strlen($s)-2);  /* take the first and last chars off as they should be quotes */
//		}
		return($arr);
	}
/* }
	/* -----------------------------------------------------------*/

	function ameta_cache_enable () {
	/* Create a cache table if t does not exist */
		global $wpdb;
	/* 	if the cache table does not exist, then create it . be VERY VERY CAREFUL about editing this sql */
		$table_name = $wpdb->prefix . "amr_reportcache";
		if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		$sql = "CREATE TABLE " . $table_name . " (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  reportid varchar(20) NOT NULL,
		  line bigint(20) NOT NULL,
		  csvcontent text NOT NULL,
		  PRIMARY KEY  (id),
		  UNIQUE KEY reportid (reportid,line )
		);";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);		
		if($wpdb->get_var("show tables like '$table_name'") != $table_name) return false;
		else return true;
	}
}
	/* -----------------------------------------------------------*/

	function ameta_cachelogging_enable() {
	/* Create a cache logging register table if t does not exist */
		global $wpdb;
	/* 	if the cache table does not exist, then create it . be VERY VERY CAREFUL about editing this sql */
		$table_name = $wpdb->prefix . "amr_reportcachelogging";
		if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		$sql = "CREATE TABLE " . $table_name . " (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  eventtime datetime NOT NULL,
		  eventdescription text NOT NULL,
		  PRIMARY KEY  (id)
		);";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		dbDelta($sql);
		
		if($wpdb->get_var("show tables like '$table_name'") != $table_name) return false;
		else return true;

	}
}
	/* -----------------------------------------------------------*/


?>
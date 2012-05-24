<?php 

if (!(defined('PHP_EOL'))) { /* for new lines in code, so we can switch off */
    define('PHP_EOL',"\n");
}
/* --------------------------------------------------------------------------------------------*/	
function amr_users_can_edit ($type) {
		if (is_admin() and isset($_GET[$type])
		and (current_user_can('manage_options') or current_user_can('manage_userlists') ) )
		return true;
		else return false;
}
/* ----------------------------------------------------------------------------------- */
function ausers_form_end() {
	$html = PHP_EOL.'</form><!-- end amr users form -->';
	$html .= PHP_EOL.'</div><!-- end amr users form wrap -->';
	return ($html);
}
/* ----------------------------------------------------------------------------------- */
function ausers_form_start() {
global $amain;
	if (isset($_REQUEST['clear_filtering']) or !empty($_REQUEST['su'])) 
		$base = get_permalink();
	else  
		$base = remove_query_arg(array('refresh', 'listpage', 'rows_per_page','filter','su', 'fieldvaluefilter'));
	
	if (!empty($_REQUEST['rows_per_page'])) { 

		if (!($_REQUEST['rows_per_page'] == $amain['rows_per_page']) )
			$base = add_query_arg('rows_per_page',(int) $_REQUEST['rows_per_page'],$base);
	}
	$html = PHP_EOL.'<div class="wrap"><!-- form wrap -->'.PHP_EOL;
	$html .= PHP_EOL.'<form id="userlist" action="'.$base.'" method="post">';
	$html .= PHP_EOL.'<input type="hidden" name="action" value="save" />';
	$html .= PHP_EOL.wp_nonce_field('amr-meta','amr-meta',true,false);
	return ($html);

}
/* -------------------------------------------------------------------------------------------------------------*/
function amr_convert_mem($size) {
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),4).' '.$unit[$i];
 }
/* -------------------------------------------------------------------------------------------------------------*/
function track_progress($text) {
global $time_start;
global $cache;
	//**** return;
	if (!is_admin()) return;
	if (!(WP_DEBUG or isset($_REQUEST['mem']) )) return; // only do something if debugging or reqquested

	if (!isset($time_start)) {
		$time_start = microtime(true);
		$diff = 0;
	}
	else {
		$now = microtime(true);
		$diff = round(($now - $time_start),3);
	}
	$mem = memory_get_peak_usage(true);
	$mem = amr_convert_mem($mem);
	$t = 'At '.number_format($diff,3). ' seconds,  peak mem= '.$mem ;
	$mem = memory_get_usage (true);
	$mem = amr_convert_mem($mem);
	$t .= ' real_mem='.$mem;
	$t .=' - '.$text;
	echo '<br />'.$t;
	error_log($t);  //debug only
	if (!empty ($cache)) $cache->log_cache_event($t);
}
/* ---------------------------------------------------------------------*/
function amr_js_cdata( $data) { //inline js
	echo "<script type='text/javascript'>\n";
	echo "/* <![CDATA[ */\n";
	echo $data;
	echo "/* ]]> */\n";
	echo "</script>\n";
	} 
/* ---------------------------------------------------------------------*/
function amr_loading_message_js() {
	$js = 'jQuery(function ($) {
		$(window).load(function(){  
			$(".loading").hide(); 
		}
	})';
	 return (amr_js_cdata( $js));
	  
}
//---------------------------------------------------------------------------------------
function amr_get_combo_fields($list) {
global $aopt;

	$s = $aopt['list'][$list]['selected'];  
	asort ($s);

	foreach ($s as $is => $cl) { // for each selected and sorted
		$colno = (int) $cl;  // reduce to an integer to get the column number
		$combofields[$colno][] = $is;  // make a note of the fields in a column in case there are multple
	}
	$iline = amr_build_cols ($s);	 
	foreach ($combofields as $colno => $field) { // convert from columnnumber to tech column name
		$combofields[$iline[$colno]] = $field;
		unset($combofields[$colno]);
	}
	return($combofields);
}
/* ---------------------------------------------------------------------*/
function amr_build_cols ($s) {  // get the technical column names, which couldbe combo fields
global $amain, $amr_current_list;
	$iline = array();
	$iline[0] = 'ID';

	foreach ($s as $is => $cl) { // for each selected and sorted
		$colno = (int) $cl;
		if (!empty($iline[$colno])) { // then it's a combo
			$iline[$colno] .= $is; 
		}
		else $iline[$colno] = $is;
	}
	if (! empty($amain['customnav'][$amr_current_list] )) // if we are doingcustom navigation, need to record the index
		$iline[99999] = 'index';
	return ($iline);
}		
/* ---------------------------------------------------------------------*/
function amr_build_col_headings ($s) {  // get the user column nice names, which could be combo fields	
global $amain, $amr_current_list;	
	$line = array();
	$line[0] = 'ID'; // must be first

	foreach ($s as $is => $cl) { // for each selected and sorted		
		$colno = (int) $cl;
		$value = agetnice($is); 
		if (!empty($line[$colno])) {
			$line[$colno] = $line[$colno].'&nbsp;'.$value; 						
			}
		else $line[$colno] = $value;
	}
	if (! empty($amain['customnav'][$amr_current_list] )) // if doing custom  nav, must cache index
		$line[99999] = 'index';
	return ($line);
}
/* ---------------------------------------------------------------------*/
function amr_get_icols($c, $rptid) {
	$line = $c->get_cache_report_lines ($rptid, '0', '1'); /* get the internal heading names  for internal plugin use only */  /* get the user defined heading names */				
		if (!defined('str_getcsv')) 
			$icols = amr_str_getcsv( ($line[0]['csvcontent']), ',','"','\\');
		else 
			$icols = str_getcsv( $line[0]['csvcontent'], ',','"','\\');
		return ($icols);
}
/* ---------------------------------------------------------------------*/
function amr_get_usermasterfields() {
global $wpdb,$wp_version ;

	if (version_compare($wp_version,'3.3','<')) {
			
		$main_fields = array(
		'ID',
		'user_login',
		'user_nicename',
		'user_email',
		'user_url',
		'user_registered',
		'user_status',
		'user_activation_key',
		'display_name');	// unlikley to use for selection normally?
		
	}
	else { // wp may have added some fields
		$q =  'SELECT DISTINCT(COLUMN_NAME) FROM information_schema.COLUMNS WHERE TABLE_NAME = "'.$wpdb->users.'"';
		$all = $wpdb->get_results($q, ARRAY_N); 
		//if (WP_DEBUG) {echo '<br/> found in info schema : ';var_dump($all);}
		
		if (is_wp_error($all)) {amr_flag_error ($all); die;}
		foreach ($all as $i=>$arr) $main_fields[$i] = array_shift($arr);
		
	}
	
	if (!($excluded_nicenames = ausers_get_option('amr-users-nicenames-excluded')))
		$excluded_nicenames = array();

	foreach ($main_fields as $i=>$f) {
		if (isset ($excluded_nicenames[$f])) {
			unset ($main_fields[$i]); 
		}
	}
	return $main_fields;
}
/* ---------------------------------------------------------------------*/
function amr_get_createdfields(  ) {
	return (array('post_count','comment_count','avatar','first_role'));
}
/* ---------------------------------------------------------------------*/
if (!function_exists('esc_textarea') ) {
	function esc_textarea( $text ) {
	$safe_text = htmlspecialchars( $text, ENT_QUOTES );
	}
}	
/* ---------------------------------------------------------------------*/	
  // Only validates empty or completely associative arrays
function amr_is_assoc ($arr) {
     return (is_array($arr) && count(array_filter(array_keys($arr),'is_string')) == count($arr));
}
/* ---------------------------------------------------------------------*/	
function amr_get_userdata($id){
	$data = get_userdata($id);    
	if (!empty($data->data)) return($data->data); // will not have meta data
	else return ($data);
};
/* ---------------------------------------------------------------------*/	
// not in use ?
function amr_users_dropdown ($choices, $current) { // does the options of the select
 	if (empty($choices)) return'';
	foreach ($choices as $opt => $value){	
		echo '<option value="'.$value.'"';
		if ($value === $current) echo ' selected="selected" ';
		echo '>'.$choices[$opt].'</option>';
	}
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
function ausers_delete_htmltransients() {
global $amain;	
	if (empty($amain)) return;
	if (empty($amain['names'])) return; ///something wrong
	foreach ($amain['names'] as $i => $list) {
		delete_transient('amr-users-html-for-list-'.$i);
		
	}
}
/* -------------------------------------------------------------------------------------------------------------*/
function agetnice ($v){
global $amr_nicenames;
	if (isset ($amr_nicenames[$v])) 
		return ($amr_nicenames[$v]);
	else return ucwords(str_replace('_',' ',$v));	
	/*** ideally check for table prefix and string out for newer wordpress versions ***/
}
/** -----------------------------------------------------------------------------------*/ 
function amr_is_network_admin() {
	global $ausersadminurl;	
	if (is_network_admin()) return true;
	if (stristr($ausersadminurl,'network') == FALSE) 
		return false;
	
	return (true);
}
/* -----------------------------------------------------------------------------------*/ 	
function ausers_job_prefix () {
	if (amr_is_network_admin()	) return ('network_');
	else return ('');
}
/* -----------------------------------------------------------------------------------*/
function amru_get_users( $args ) { /*  get all user data and attempt to extract out any object values into arrays for listing  */
global $wpdb;

// just do simply for now, as we have filtering later to chope out bits
	$_REQUEST['mem'] = true;  // to show memory

	if (is_multisite() and is_network_admin()) {
		$where = ' INNER JOIN ' . $wpdb->usermeta .  
       ' ON      ' . $wpdb->users . '.ID = ' . $wpdb->usermeta . '.user_id 
        WHERE   ' . $wpdb->usermeta .'.meta_key =\'' . $wpdb->prefix . 'capabilities\'' ;
		$wheremeta = " WHERE ".$wpdb->usermeta.".user_id IN ".
		"(SELECT distinct user_id FROM ".$wpdb->usermeta
		." WHERE ".$wpdb->usermeta .".meta_key ='" . $wpdb->prefix . "capabilities')";
	}
	else $where = '';
	
	$query = $wpdb->prepare( "SELECT * FROM $wpdb->users".$where); // WHERE meta_key = %s", $meta_key );
	$users = $wpdb->get_results($query, OBJECT_K);  // so returns id as key
	
	track_progress('After get users without meta');
	
	$query = $wpdb->prepare( "SELECT * FROM $wpdb->usermeta".$where); // WHERE meta_key = %s", $meta_key );
	$metalist = $wpdb->get_results($query, OBJECT_K);
	
	if (WP_DEBUG) {
		//echo '<h2>metalist</h2>'; var_dump($metalist);
	}
	
	track_progress('After get users meta');
	
	foreach ($users as $i => $u) {

		if (isset($metalist[$i])) {
			$users[$i] = (object) array_merge((array) $u, (array) $metalist[$i]);			
			unset($metalist[$i]);
		}
		
	}		
	track_progress('After combining users with their meta');
	return ($users);

}
/* -----------------------------------------------------------------------------------*/ 	
function amr_get_alluserdata( $list ) { /*  get all user data and attempt to extract out any object values into arrays for listing  */

global $excluded_nicenames, 
	$amain,
	$aopt, // the list options (selected, included, excluded)
	$orig_mk, // original meta key mapping - nicename key to original metakey
	$amr_current_list;
	
	$amr_current_list = $list;	
	$main_fields = amr_get_usermasterfields();
	
// 	maybe use, but no major improvement for normal usage add_filter( 'pre_user_query', 'amr_add_where'); 
		
	if (!$orig_mk = ausers_get_option('amr-users-original-keys')) 
		$orig_mk = array();
//	
//	track_progress ('Meta fields we could use to improve selection: '.print_r($orig_mk, true));
	$combofields = amr_get_combo_fields($list);  

	$role = '';
	$mkeys = array();
	if (!empty($aopt['list'][$list]['included'])) { 	
		// if we have fields that are in main user table, we could add - but unliket as selection criateria - more in search	
		foreach ($aopt['list'][$list]['included'] as $newk=> $choose ) {

			if (isset ($orig_mk[$newk])) 
				$keys[$orig_mk[$newk]] = true;
		
			if ($newk == 'first_role') {
				if (is_array($choose)) 
					$role = array_pop($choose);
				else 
					$role = $choose;
			}
		
			if (isset ($orig_mk[$newk]) and ($newk == $orig_mk[$newk])) {// ie it is an original meta field
				if (is_array($choose)) {
					if (count($choose) == 1) {
						$choose = array_pop($choose);
						$compare = '=';
					}
					else $compare = 'IN';
				}
				else $compare = '=';
				
				$meta_query[] = array (
					'key' => $newk,
					'value' => $choose,
					'compare' => $compare
				);
			}
		}
	}
// now try for exclusions 	
	if (!empty($aopt['list'][$list]['excluded'])) { 
		foreach ($aopt['list'][$list]['excluded'] as $newk=> $choose ) {
			if (isset ($orig_mk[$newk])) {
				$keys[$orig_mk[$newk]] = true; // we need to fetch a meta value
				if ($newk == $orig_mk[$newk]) {// ie it is an original meta field 1 to 1
					if (is_array($choose)) {
						if (count($choose) == 1) {
							$choose = array_pop($choose);
							$compare = '!=';
						}
						else $compare = 'NOT IN';
					}
					else $compare = '!=';
					
					$meta_query[] = array (
						'key' => $newk,
						'value' => $choose,
						'compare' => $compare
					);
				}				
			}
		} // end for each
	}
// now need to make sure we find all the meta keys we need

	foreach (array('selected','excludeifblank','includeifblank' ,'sortby' ) as $v) {
		//if (WP_DEBUG) echo '<br />'.$v;
		if (!empty($aopt['list'][$list][$v])) { 
			foreach ($aopt['list'][$list][$v] as $newk=> $choose ) {			
				if (isset ($orig_mk[$newk])) {// ie it is FROM an original meta field
					$keys[$orig_mk[$newk]] = true;
				}
				
			}
		}
	}
	
	$args = array();
	if (!empty ($role) ) 		$args['role'] = $role;
	if (!empty ($meta_query) ) 	$args['meta_query'] = $meta_query;
	//if (!empty ($fields) ) $args['fields'] = $fields;
	
	$args['fields'] = 'all_with_meta'; //might be too huge , but fast - DOES NOT GET META DATA ??
	
	//track_progress ('Simple meta selections to pass to query: '.print_r($args, true));

	if (is_network_admin() or amr_is_network_admin() ) 
		$args['blog_id'] = '0';
	
	if (isset($amain['use_wp_query'])) {
		
		$all = get_users($args); // later - add selection if possible here to reduce memory requirements 
	/*	if (WP_DEBUG) {
			echo '<br/>Fetching with wp query '; 
			foreach ($all as $i => $uobj) {
				$test = get_user_meta($uobj->ID, 'Speciality',false); 
				echo '<br />'.$uobj->ID;
				var_dump($test);
			}
			//var_dump($all);
		}*/
		
		}
	else {	
		//if (WP_DEBUG) echo '<br/>Fetching with own query ';
		$all = amru_get_users($args); // later - add selection if possible here to reduce memory requirements 
	}
	$all = apply_filters('amr_get_users', $all); // allow addition or removal of normal wp users who will have userid


//	$all = get_users(array('blog_id'=>0));
	
	track_progress('after get wp users, we have '.count($all));
	
	foreach ($all as $i => $userobj) { 
// save the main data, toss the rest
		foreach ($main_fields as $i2=>$v2) {			
			$users[$i][$v2] = $userobj->$v2;   		
		}
// we just need to expand the meta data
		if (!empty($keys)) { // if some meta request
			foreach ($keys as $i2 => $v2) {	
				//if (WP_DEBUG) {echo '<br /> Key:'.$i2;}
				//if (!isset($userobj->$i2)) {  // in some versions the overloading does not work - only fetches 1
					//$userobj->$i2 = get_user_meta($userobj->ID, $i2, false);
					//wordpress does some kind of overloading to fetch meta data  BUT above only fetches single
					
					$test = get_user_meta($userobj->ID, $i2, false); // get as array
					
					//if (WP_DEBUG) {echo '<br /> '.$i2.' tst ='; var_dump($test);}
					if (!empty($test)){
						$userobj->$i2 = implode(',',$test);
						//if (WP_DEBUG) {echo '<br /> '.$i2.' tst ='; var_dump($test);}
					}
				//}
				
				if (!empty($userobj->$i2)) { 
					$temp = maybe_unserialize ($userobj->$i2);
					$temp = objectToArray ($temp); /* *must do all so can cope with incomplete objects */
					$key = str_replace(' ','_', $i2); /* html does not like spaces in the names*/
					if (is_array($temp)) { if (WP_DEBUG) echo '<br/>It is an array - multi value';
						foreach ($temp as $i3 => $v3) {
							$key = $i2.'-'.str_replace(' ','_', $i3);/* html does not like spaces in the names*/
							
							if (is_array($v3)) {  // code just in case another plugin nests deeper, until we know tehre is one, let us be more efficient
//								if (amr_is_assoc($v3)) { // does not yet handle, just dump values for now
//									$users[$i][$key] = implode(", ", $v3);
//								}
//								else { // is numeric array eg s2member custom multi choice
									$users[$i][$key] = implode(", ", $v3);
//								}
							}
							else $users[$i][$key] = $v3;
						}
					}	
					else {
						$users[$i][$key] = $temp;
						//if (WP_DEBUG) {echo '<br/>Not an array'; var_dump($temp);}
					}
					unset($temp);
					// we could add some include / exclude checking here?
				}	
			} /// end for each keys
		} // 
		unset($all[$i]);
	} // end for each all
	unset($all);
	track_progress('after get users meta check '.(count($users)));

	$post_types=get_post_types();			
	/* get the extra count data */
	if (amr_need_the_field($list,'comment_count')) 
		$c = get_commentnumbers_by_author();
	else $c= array();		
	track_progress('after get comments check');
	if (!empty($users)) foreach ($users as $iu => $u) {
	// do the comments
		if (isset ($c[$u['ID']])) {
			$users[$iu]['comment_count'] = $c[$u['ID']]; /*** would like to cope with situation of no userid */
			}
	// do the post counts		
		foreach ( $post_types as $post_type ) {		
			if (amr_need_the_field($list,$post_type.'_count')) {				
				$users[$iu][$post_type.'_count'] = amr_count_user_posts($u['ID'], $post_type);
//					if ()WP_DEBUG) echo '<br />**'.$post_type.' '.$list[$iu][$post_type.'_count'];
//					$list[$iu]['post_count'] = get_usernumposts($u['ID']); /* wordpress function */
				if ($users[$iu][$post_type.'_count'] == 0) unset($users[$iu][$post_type.'_count']);
			}				
		}
		if (amr_need_the_field($list,'first_role')) {
			$user_object = new WP_User($u['ID']);
			if (!empty($user_object->roles)) 
				$users[$iu]['first_role'] = amr_which_role($user_object); 
			if (empty ($users[$iu]['first_role'] )) 
				unset($users[$iu]['first_role']);
		}
	}
	track_progress('after post types and roles:'.count($users));
	unset($c);
	$users = apply_filters('amr_get_users_with_meta', $users); // allow addition of users from other tables with own meta data
	
	track_progress('after user filter, have'.count($users));
	if (empty($users)) return (false);
	
return ($users);	
}
/* -----------------------------------------------------------------------------------*/
if (!function_exists('amr_pagetext')) {
function amr_pagetext($thispage=1, $totalitems, $rowsperpage=30){ 
/* echo's paging text based on parameters - */

	$lastpage = ceil($totalitems / $rowsperpage);
	if ($thispage > $lastpage) 
		$thispage = $lastpage;
	$from = (($thispage-1) * $rowsperpage) + 1;
	$to = $from + $rowsperpage-1;
	if ($to > $totalitems) 
		$to = $totalitems;
	$totalpages = ceil($totalitems / $rowsperpage);
	$base = remove_query_arg (array('refresh','listpage'));
	
	if (!empty($_POST['filter'])) {
		$argstoadd = $_POST;
		foreach ($argstoadd as $i => $value) {
			if (empty($value)) unset($argstoadd[$i]);
		};
		//unset($argstoadd['fieldvaluefilter']);
		$base = add_query_arg($argstoadd, $base);
		//var_dump($base); 
	}	
	if (!empty($_REQUEST['su'])) {
		$search = sanitize_title($_REQUEST['su']);
		$base = add_query_arg('su',$search ,$base);
	}
	if (!empty($_REQUEST['rows_per_page'])) 
		$base = add_query_arg('rows_per_page',(int) $_REQUEST['rows_per_page'],$base);
//	if (!empty($_SERVER['QUERY_STRING']) ) $format = '&listpage=%#%'; // ?page=%#% : %#% is replaced by the page number
//	else $format = '?listpage=%#%';
	
	$paging_text = paginate_links( array(  /* uses wordpress function */
				'total' 	=> $totalpages,
				'current' 	=> $thispage,
//				'base' => $base.'%_%', // http://example.com/all_posts.php%_% : %_% is replaced by format (below)
				'base' 		=> @add_query_arg('listpage','%#%', $base),
				'format' 	=> '',
				'end_size' 	=> 2,
				'mid_size' 	=> 2,
				'add_args' 	=> false
			) );
		if ( $paging_text ) {
				$paging_text = PHP_EOL.
					'<div class="tablenav">'.PHP_EOL.
					'<div class="tablenav-pages">'
					.sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>&nbsp;%s',
					number_format_i18n( $from ),
					number_format_i18n( $to ),
					number_format_i18n( $totalitems ),
					$paging_text
					.'</div>'.PHP_EOL.'</div>'
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
/* ---------------------------------------------------------------------*/	
if (!function_exists('amr_check_memory')) {
function amr_check_memory() { /* */

	if (!function_exists('memory_get_peak_usage')) return(false);

		$mem_usage = memory_get_peak_usage(true);       
        $html = amru_convert_mem($mem_usage);

		return($html);
	}
}
/* -----------------------------------------------------------------------------------*/ 
function amru_convert_mem($mem_usage) {
	$html = '';
	if ($mem_usage < 1024)
            $html .= $mem_usage." bytes";
        elseif ($mem_usage < 1048576)
            $html .= round($mem_usage/1024,2)." KB"; /* kilobytes*/
        else
            $html .= round($mem_usage/1048576,2)." MB"; /* megabytes */
	return ($html);		
}
/* -----------------------------------------------------------------------------------*/ 	
if (!(function_exists('objectToArray'))) { //    * Convert an object to an array
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
/* ---------------------------------------------------------------------- */
function amr_getset_timezone () {
	global $tzobj;
	
	if ($tz = get_option ('timezone_string') ) $tzobj = timezone_open($tz);	
	else $tzobj = timezone_open('UTC');
	
}
/* ---------------------------------------------------------------------- */
function amr_users_reset_column_headings ($ulist) {
	if ($amr_users_column_headings = ausers_get_option('amr-users-custom-headings')) {
		unset($amr_users_column_headings[$ulist]); 
		$results = ausers_update_option('amr-users-custom-headings', $amr_users_column_headings);
	}
	else $results = true;
	return ($results);
}
/* ---------------------------------------------------------------------- */
function amr_users_store_column_headings ($ulist, $customcols ) {
	if (!($amr_users_column_headings = ausers_get_option('amr-users-custom-headings'))) {
	
		$amr_users_column_headings = array();
	}
	
	$amr_users_column_headings[$ulist] = $customcols;
	$results = ausers_update_option('amr-users-custom-headings', $amr_users_column_headings);
	if ($results) {
		amru_message(__('Custom Column Headings Updated'));
			
	}
	else amru_message(__('Column headings not updated - no change or error.'));
		
		return ($results);
}
/* ---------------------------------------------------------------------- */
function amr_users_get_column_headings ($ulist, $cols, $icols ) {
	global $amr_users_column_headings;
	
	if ($amr_users_column_headings = ausers_get_option('amr-users-custom-headings')) {
		if (!empty($amr_users_column_headings[$ulist]) ) {
			$customcols = $amr_users_column_headings[$ulist];
			foreach ($icols as $ic => $cv) { 
				if (isset($customcols[$cv])) { 
					$cols[$ic] = $customcols[$cv];
				}
			}
			return ($cols);	
		}
	}
	return ($cols);
}
/* ---------------------------------------------------------------------*/	
function amr_mimic_meta_box($id, $title, $callback , $toggleable = true) {
	global $screen_layout_columns;

	//	$style = 'style="display:none;"';
		$h = (2 == $screen_layout_columns) ? ' has-right-sidebar' : '';
		echo '<div style="clear:both;" class="metabox-holder'.$h.'">';
		echo '<div class="postbox-container" style="width: 49%;">';
		echo '<div class="meta-box-sortables" style="min-height: 10px;">';
		echo '<div id="' . $id . '" class="postbox ' ;
		if ($toggleable) { echo 'if-js-closed' ;}
		echo '">' . "\n";
		echo '<div class="handlediv" title="' . __('Click to toggle') . '"><br /></div>';
		
		echo "<h3 class='hndle'><span>".$title."</span></h3>\n";
		echo '<div class="inside">' . "\n";
		call_user_func($callback);
		echo "</div></div></div></div></div>";
		
	}
//}
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
if (function_exists('amr_flag_error')) return;
else {
	function amr_flag_error ($text) {
		echo '<div class="error"><p>'.$text.'</p></div>';
	}
}
/* ---------------------------------------------------------------------*/	
	function amru_message ($text) {
		echo '<div class="updated"><p>'.$text.'</p></div>';
	}

/* ---------------------------------------------------------------------*/
function amr_users_feed($uri, 
		$num=5, 
		$text='Recent News',
		$icon="http://webdesign.anmari.com/images/amrusers-rss.png") {
	
	$feedlink = '<h3><a href="'.$uri.'">'.$text.'</a><img src="'.$icon.'" alt="Rss icon" style="vertical-align:middle;" /></h3>';	

	if (!function_exists ('fetch_feed')) { 
		echo $feedlink;
		return (false);
		}
	if (!empty($text)) {?>
		<div><!-- rss widget -->
		<h3><?php _e($text);?><a href="<?php echo $uri; ?>" title="<?php echo $text; ?>" >
		<img src="<?php echo $icon;?>"  alt="Rss icon" style="vertical-align:middle;"/></a></h3><?php
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
	    else {
	    // Loop through each feed item and display each item as a hyperlink.
	    foreach ( $rss_items as $item ) { 
			$url = $item->get_permalink(); 
			?>
	    <li> <?php //echo $item->get_date('F j').'&nbsp;'; ?>
	        <a href="<?php echo $url; ?>" title="<?php echo $item->get_date('j F Y'); ?>" >
	        <?php echo $item->get_title(); ?> </a> 
			<?php $teaser = $item->get_description();
			$teaser = strip_tags(substr($teaser,0,stripos($teaser, 'Related posts')), null);
			$teaser = substr($teaser,0, 200 - strlen($item->get_title()));
			echo $teaser.'<a href="'.$url.'">...</a>'; ?>
			<?php //echo $item->get_description(); ?>
	    </li>
	    <?php
		}?>
		<li>...</li>
		<?php 
		}?>
	</ul>
	</div><!-- end rss widget -->
	<?php 
}
	
/* -----------------------------------------------------------*/
function amr_str_getcsv ($string, $sep, $e1, $e2 ) {  /*** a pseudo function only  */
		$arr = explode( $sep, $string);
		$arr[0] = ltrim($arr[0], '"');
		$end = count($arr);
		$arr[$end-1] = rtrim($arr[$end-1],'"');
		return($arr);
	}
/* -------------------------------------------------------------------------------------------------------------*/
function auser_sortbyother( $sort, $other) {
	/* where  other is in an order that we want the sort array to be in .  Note nulls or emptyies to end */
		// Obtain a list of columns

		if (empty($other)) return ($sort);
		$temp = $sort; 
		foreach ($other as $key => $row) {
			if (!empty ($temp[$key]) )
				$s2[$key]  = $temp[$key];
			unset ($temp[$key]);
		}

		if (count($temp) > 0) return (array_merge ($s2, $temp));
		else return ($s2);
	}
/* -------------------------------------------------------------------------------------------------------------*/
function amr_usort( $a, $b) {
	/* comparision function  - don't mess with it - it works - sorts strings to end, else in ascending order */
		if ($a == $b) return (0);
		else if (is_string($a) and (strlen($a) == 0)) return (1);
		else if (is_string($b) and (strlen($a) == 0)) return (-1);
		else return ($a<$b) ? -1: 1;
	}
//}
/* -------------------------------------------------------------------------------------------------------------*/
function ameta_cache_enable () {
	/* Create a cache table if t does not exist */
		global $wpdb, $charset_collate;
	/* 	if the cache table does not exist, then create it . be VERY VERY CAREFUL about editing this sql */

	
		if (empty($charset_collate)) 
			$cachecollation = ' DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci ';
		else 
			$cachecollation = $charset_collate;

	
		$table_name = ameta_cachetable_name();
		
		if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
			$sql = "CREATE TABLE " . $table_name . " (
			  id bigint NOT NULL AUTO_INCREMENT,
			  reportid varchar(20) NOT NULL,
			  line bigint(20) NOT NULL,
			  csvcontent text NOT NULL,
			  PRIMARY KEY  (id),
			  UNIQUE KEY reportid (reportid,line ) )
			  ".$cachecollation." ;";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);		
			if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
				error_log($table_name.' not created');
				return false;
			}
			else return true;
		}
	return true;
}
	/* -----------------------------------------------------------*/
function ameta_cachelogtable_name() {
	global $wpdb;
	global $table_prefix;
	
		if (is_network_admin() or amr_is_network_admin())
			$table_name = $wpdb->base_prefix . "network_amr_reportcachelogging";
		else
			$table_name = $wpdb->prefix . "amr_reportcachelogging";
		return($table_name);
	}
	/* -----------------------------------------------------------*/
function ameta_cachetable_name() {
	global $wpdb;
	global $table_prefix;
		if (is_network_admin() or amr_is_network_admin())
			$table_name = $wpdb->base_prefix . "network_amr_reportcache";
		else
			$table_name = $wpdb->prefix . "amr_reportcache";
		return($table_name);
	}
	/* -----------------------------------------------------------*/
function ameta_cachelogging_enable() {
	/* Create a cache logging register table if t does not exist */
		global $wpdb, $charset_collate;
		
		
		if (empty($charset_collate)) 
			$cachecollation = ' DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci ';
		else 
			$cachecollation = $charset_collate;
		
	/* 	if the cache table does not exist, then create it . be VERY VERY CAREFUL about editing this sql */
		$table_name = ameta_cachelogtable_name();
		if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
			$sql = "CREATE TABLE " . $table_name . " (
			  id bigint NOT NULL AUTO_INCREMENT,
			  eventtime datetime NOT NULL,
			  eventdescription text NOT NULL,
			  PRIMARY KEY  (id) )
			  ".$cachecollation. "
			;";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

			dbDelta($sql);
			
			if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
				error_log($table_name.' not created');
				return false;
			}
			else return true;

		}
		return true;
}
/* -----------------------------------------------------------*/
function ausers_bulk_actions() {
global $two;
	if (!(current_user_can('remove_users'))) return;

	$actions = array('delete'=>__('Delete')); // use wp translation

	if (!isset($two)) $two = '';

	echo PHP_EOL.'<div class="clear"> </div>';
	echo PHP_EOL.'<div><!--  bulk action -->';
	echo "<select name='action$two'>\n";
	echo "<option value='-1' selected='selected'>" . __( 'Bulk Actions' ) . "</option>\n";
	foreach ( $actions as $name => $title ) {
		$class = 'edit' == $name ? ' class="hide-if-no-js"' : '';

		echo "\t<option value='$name'$class>$title</option>\n";
	}
	echo "</select>\n";

	submit_button( 
		__( 'Apply' ), //text
		'button-secondary action', // type
		'dobulk'.$two, //name
		false, // wrap in p tag or not
		array( 'id' => "doaction$two" ) // other attributes
		);

	$two = '2';
	echo PHP_EOL.'</div><!-- end bulk action -->'.PHP_EOL;
}
/* -----------------------------------------------------------*/
function amr_is_ym_in_list ($list) {
	global $aopt;
	
	if (!is_admin() and !current_user_can('promote_users')) return false;
	if (empty($aopt['list'][$list]['selected'])) return false;
	
	foreach($aopt['list'][$list]['selected'] as $field => $col) {
		if (stristr($field, 'ym_')) // if there is at least one ym field
			return true;
	}	
	return false;
}
/* -----------------------------------------------------------*/
function amr_is_bulk_request ($type) {
	if (((isset($_REQUEST['dobulk']) and	($_REQUEST['dobulk'] == 'Apply'))
	 or (isset($_REQUEST['dobulk2']) and ($_REQUEST['dobulk2'] == 'Apply' ) ))
	and 
	((!empty($_REQUEST['action']) and ($_REQUEST['action'] == $type))
	or
	(!empty($_REQUEST['action2']) and ($_REQUEST['action2'] == $type)	))
	)
	return true;
	else return false;

}
/* -----------------------------------------------------------*/
function amr_redirect_if_delete_requested () { 
	if (amr_is_bulk_request ('delete'))	{
		if (function_exists('amr_ym_bulk_update') and isset($_REQUEST['ps']))
			$_REQUEST['users'] = $_REQUEST['ps'];  // 'ps is required by ym
	
		if (isset($_REQUEST['users'])) wp_redirect(
			add_query_arg(array(
			'users'=>$_REQUEST['users'] , 
			'action'=>'delete'
			),
			wp_nonce_url(network_admin_url('users.php'),'bulk-users')));
		else {
			echo 'No users selected';
		}
		exit;
	}	
}

add_action('admin_menu','amr_redirect_if_delete_requested');

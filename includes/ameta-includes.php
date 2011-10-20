<?php 
if (!function_exists('esc_textarea') ) {
	function esc_textarea( $text ) {
	$safe_text = htmlspecialchars( $text, ENT_QUOTES );
	}
}	

if (!(defined('AMR_NL'))) { /* for new lines in code, so we can switch off */
    define('AMR_NL',"\n");
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
/* ---------------------------------------------------------------------*/	
function amr_linktypes () {
	return (array (
		'none' => __('none', 'amr_users'),
		'edituser'=> __('edit user', 'amr_users'),
		'mailto'=> __('mail to', 'amr_users'),
		'postsbyauthor' => __('posts by author in admin', 'amr_users'),
		'authorarchive' => __('author archive', 'amr_users'),
		'commentsbyauthor' => __('comments by author (*)', 'amr_users'), // requires extra functionality
		'url' => __('users url', 'amr_users')
	
		));
	}
/* ---------------------------------------------------------------------------*/	
function amr_get_href ($field, $v, $u, $linktype) {

	switch ($linktype) { 
			case 'none': return '';
			case 'mailto': {
				if (!empty($u->user_email)) return ('mailto:'.$u->user_email);
				else return '';
				}
			case 'postsbyauthor': { // figure out which post type

				if (empty($v)) return( ' ');
				else {
					if (stristr($field, '_count')) { // it is a item count thing, but not a post count
						if (is_object($u) and isset ($u->ID) ) {
							$ctype = str_replace('_count', '', $field);
							$href=add_query_arg(array(
								'author'=>$u->ID, 
								'post_type'=>$ctype
								),
								network_admin_url('edit.php')
								);
							return ($href);	
						} // end if
					} // end if stristr
				}
				return '';
				}
			case 'edituser': {
				if (is_object($u) and isset ($u->ID) ) 
					return ( network_admin_url('user-edit.php?user_id='.$u->ID));
				else return '';
				}
			case 'authorarchive': {
				if (is_object($u) and isset ($u->ID) ) { 
					return(add_query_arg('author', $u->ID, home_url()));
					}
				else return '';
				}	
			case 'commentsbyauthor': {	
				if ((empty($v)) or (!($stats_url = ausers_get_option('stats_url')))) 
					return('');
				else return (add_query_arg('stats_author',$u->user_login, $stats_url));
			}
			case 'url': {
				if (!empty($u->user_url)) return($u->user_url);
			}	
			default: return('');
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
function ameta_defaultnicenames () {
global $orig_mk;

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
	'first_role' => __('First Role', 'amr-users'),
	'ausers_last_login' => __('Last Login', 'amr-users')
);

// no must only be real meta keys // foreach ($nicenames as $i=>$k)  $orig_mk[$i] = $i; 

return ($nicenames);
}
/* -------------------------------------------------------------------------------------------------------------*/	
function ameta_defaultoptions () { // defaulstlists
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
					'avatar' => 1, 
					'user_login' => 2, 
					'user_email' => 3,
					'first_name' => 4.1,
					'last_name' => 4.2,
					'user_registered' => 5,
					),
				'sortdir' => array ( /* some fields should always be sorted in a certain order, so keep that fact, even if not sorting by it*/
					'user_registered' => 'SORT_DESC',
					'post_count' => 'SORT_DESC'),
				'sortby' => array ( 
					'user_email' => '1'
					),
				'before' => array (    
					'last_name' => '<br />'
					),			
				'links' => array (    
					'user_email' => 'mailto',
					'user_login' => 'edituser', 	
					'user_url' => 'url', 	
					'avatar' => 'url',
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
					),
				'links' => array (    
						'user_login' => 'edituser',
						'user_url' => 'url'
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
				'links' => array (    
						'user_login' => 'edituser',
						'user_url' => 'url',
						'post_count' => 'postsbyauthor',
						'comment_count' => 'commentsbyauthor'
					),		
				'sortdir' => $sortdir					
				)
			)
//			,
//	'stats' => array ( '1' => 
//				array(
//					'selected' => $selected,
//					'totals' => array ( /* within the selected */
//						'ym_status' ,
//						'account_type'
//						)
//				),
//			)
		);

	return ($default);

}	
/* -------------------------------------------------------------------------------------------------------------*/	
function ameta_defaultmain () {
/* setup some defaults */

$default = array (
	'checkedpublic' => true, /* so message should only show up if we have retrieved options from DB and did not have this field - must have been an upgrade, not a reset, and not a new activation. */
    'rows_per_page' => 20,
    'no-lists' => 3,
	'avatar-size' => 16,
	'no_credit' => true,
	'csv_text' =>  ('<img src="'.plugins_url('amr-users/images/file_export.png').'" alt="'.__('Csv') .'"/>'),
	'refresh_text' =>  ('<img src="'.plugins_url('amr-users/images/rebuild.png').'" alt="'.__('Refresh user list cache') .'"/>'),
	//'givecreditmessage' => amr_users_random_message(),
	'sortable' =>	array ( '1' => true,
				'2' => true,
				'3' => true
				),
	'names' => 
		array ( '1' => __("Users: Details", 'amr-users'),
				'2' => __("Users: Member status and dates", 'amr-users'),
				'3' => __("Users: Post and Comment Counts", 'amr-users')
				)
				
	);
				
	return ($default);

}	
/* -------------------------------------------------------------------------------------------------------------*/	
function amr_check_for_upgrades () {
	// must be in admin and be admin
	if (!is_admin() or !(current_user_can('manage_options')) ) return;
			// handle a series of updates in order 
	$a = ausers_get_option ('amr-users-no-lists');
	if (empty($a)) // maybe just started;
		return;
	if ((!isset($a['version'])) or  
	 (version_compare($a['version'],'3.1','<'))) { // convert old options

		echo '<div class="updated"><p>';
		sprintf(__('Previous version was %s', 'amr-users'),$a['version'] );
		_e('New version activated. ', 'amr-users');
		_e('We need to process some updates. ', 'amr-users');
	
	 
		$a['version'] = AUSERS_VERSION;
		if (!isset($a['csv_text'])) $a['csv_text'] = ('<img src="'
				.plugins_url('amr-users/images/file_export.png')
				.'" alt="'.__('Csv') .'"/>');
		if (!isset($a['refresh_text'])) $a['refresh_text'] =  ('<img src="'
				.plugins_url('amr-users/images/rebuild.png')
				.'" alt="'.__('Refresh user list cache').'"/>');
		ausers_update_option('amr-users-no-lists',$a );	
		echo '<br />'.__('Image links updated.', 'amr-users');
		echo '<br />'.__('Now we need to rebuild the nice names.', 'amr-users');
		echo '<br />'.__('Relax ....', 'amr-users');
		ameta_rebuildnicenames ();
		
	}
	else return;
	echo '<br />'.__('Finished', 'amr-users');
	echo ' <a href="http://wordpress.org/extend/plugins/amr-users/changelog/">'
	.__('Please read the changelog','amr-users' ).'</a>';
	echo '<p></div>';
	
}
/* -------------------------------------------------------------------------------------------------------------*/	
function ameta_no_lists(){
/* Return an array of no lists ansd array of names - may need to convert for a while */
	if ($a = ausers_get_option ('amr-users-no-lists'))  {
		return($a)	;	
		}
	else { 
		if ($b = ausers_get_option ('amr-users')) { /* if we do not have the option, then it may be an older version, or an unsaved version */
			if (isset($b['no-lists']) ) {/* old version */
				$a['no-lists'] = $b['no-lists'];
				if (isset ($b['list'])) {
					foreach ($b['list'] as $i=>$l ) {
						$a['names'][$i] = $l['name'];
					}
					unset($b['list']);
				}
				ausers_update_option('amr-users'.'-no-lists',$a );
				ausers_update_option('amr-users',$b );
				return($a);
			}

			// end updates
		}
		else return ($a = ameta_defaultmain());	
	}
}
/* -------------------------------------------------------------------------------------------------------------*/	
function ausers_admin_url (){
	global $ausersadminurl;
	
	if (is_network_admin()) 
		$ausersadminurl = network_admin_url('settings.php?page=ameta-admin.php');
	else 
		if (!empty($ausersadminurl) ) return($ausersadminurl);
		else $ausersadminurl = admin_url('options-general.php?page=ameta-admin.php');
	return $ausersadminurl;
}
/* -------------------------------------------------------------------------------------------------------------*/
function ausers_get_option($option) { // allows user reports to be run either at site level and/or at blog level
global $ausersadminurl;
	$ausersadminurl = ausers_admin_url(); // will check if set 
	if (stristr($ausersadminurl,'network') == FALSE) 	
		$result = get_option($option);
	else 
		$result = get_site_option($option);	
	return($result);
}
/* -------------------------------------------------------------------------------------------------------------*/
function ausers_update_option($option, $value) { // allows user reports to be run either at site level and/or at blog level
global $ausersadminurl;
	if (stristr($ausersadminurl,'network') == FALSE) 	
		$result = update_option($option, $value);
	else 
		$result = update_site_option($option, $value);	
	return($result);
}
/* -------------------------------------------------------------------------------------------------------------*/
function ausers_delete_option($option) { // allows user reports to be run either at site level and/or at blog level
global $ausersadminurl;
	if (stristr($ausersadminurl,'network') == FALSE) 	
		$result = delete_option($option);
	else 
		$result = delete_site_option($option);	
	return($result);
}
/* -------------------------------------------------------------------------------------------------------------*/	
function ameta_options (){

global $aopt,
	$amain,
	$amr_nicenames, 
	$amr_your_prefixes,
	$excluded_nicenames,
	$ausersadminurl,
	$wpdb;

	$ausersadminurl = ausers_admin_url ();
	$default = ameta_defaultoptions();  // default list settings, not in db
	$amain = ameta_no_lists();
	
	if (!($amr_nicenames = ausers_get_option ('amr-users-nicenames')))
		$amr_nicenames = ameta_defaultnicenames();
	if (!($excluded_nicenames = ausers_get_option ('amr-users-nicenames-excluded')))
		$excluded_nicenames = array();
	foreach ($excluded_nicenames as $i=>$v)	{
		if ($v) unset ($amr_nicenames[$i]);
	}
	if (!($amr_your_prefixes = ausers_get_option('amr-users-prefixes-in-use')))
		$amr_your_prefixes = array();
	$num = ($amain['no-lists']); 

	/* chcek if we have options already in Database., if not, use default, else overwrite */
	if ($a = ausers_get_option ('amr-users')) {
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
	else return ucwords(str_replace('_',' ',$v));	
	/*** ideally check for table prefix and string out for newer wordpress versions ***/
}
/** -----------------------------------------------------------------------------------*/ 
function amr_excluded_userkey ($i) {
global $excluded_nicenames;
/* exclude some less than useful keys to reduce the list a bit */
		if (!empty($excluded_nicenames[$i])) { return (true);}

		if (stristr ($i, 'autosave_draft_ids')) return (true);
		if (stristr ($i, 'time')) return (false);  // maybe last login? or at least last time screen shown
		if (stristr ($i, 'user-settings')) return (true);
		if (stristr ($i, 'user_pass')) return (true);
		
//		if (stristr ($i, 'user_activation_key')) return (true); //shows if have done lost password
		if (stristr ($i, 'admin_color')) return (true);
		if (stristr ($i, 'meta-box-order_')) return (true);	
		if (stristr ($i, 'last_post_id')) return (true);	
		if (stristr ($i, 'nav_menu')) return (true);
//		if (stristr ($i, 'default_password_nag')) return (true);		//may want to use this to tell if they have reset their password

// DEPRECATED:
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
		if (stristr ($i, 'usersettings')) return (true);

		return (false);		
	}
/* -----------------------------------------------------------------------------------*/ 	
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
function amr_get_alluserdata( $list ) {

/*  get all user data and attempt to extract out any object values into arrays for listing  */

global $excluded_nicenames, 
	$aopt, // the list options (selected, included, excluded)
	$orig_mk; // original meta key mapping - nicename key to original metakey


	if (!($excluded_nicenames = ausers_get_option('amr-users-nicenames-excluded')))
		$excluded_nicenames = array();
	
		
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
// 	maybe use, but no major improvement for normal usage add_filter( 'pre_user_query', 'amr_add_where'); 
		
	if (!$orig_mk = ausers_get_option('amr-users-original-keys')) $orig_mk = array();
//	
//	track_progress ('Meta fields we could use to improve selection: '.print_r($orig_mk, true));

	$role = '';
	$mkeys = array();
	if (!empty($aopt['list'][$list]['included'])) { 
	
		// if we have fields that are in main user table, we could add - but unliket as selection criateria - morein search
		
	
		foreach ($aopt['list'][$list]['included'] as $newk=> $choose ) {

			if (isset ($orig_mk[$newk])) $keys[$orig_mk[$newk]] = true;
		
			if ($newk == 'first_role') {
				if (is_array($choose)) $role = array_pop($choose);
				else $role = $choose;
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

	foreach (array('selected','excludeifblank','includeifblank' ,'sortby' ) as $v)
	if (!empty($aopt['list'][$list][$v])) { 
		foreach ($aopt['list'][$list][$v] as $newk=> $choose ) {
			if (isset ($orig_mk[$newk])) {// ie it is FROM an original meta field
				$keys[$orig_mk[$newk]] = true;
			}
		}
	}
		
// fields - the fields we want
// include and exclude (by id only)
// role &role=subscriber
//meta_key - The meta_key in the wp_usermeta table for the meta_value to be returned. See get_userdata() for the possible meta keys.
//meta_value - The value of the meta key.
//meta_compare - Operator to test the 'meta_value'. Possible values are '!=', '>', '>=', '<', or '<='. Default value is '='.
//meta_query
/*	'meta_query' => array(
		array(
			'key' => 'price',
			'value' => array( 100, 200 ),
			'compare' => 'BETWEEN',
			'type' => 'numeric',
		),
		array(
			'key' => 'description',
			'value' => 'round',
			'compare' => 'NOT LIKE'
		)
*/
	
	$args = array();
	if (!empty ($role) ) $args['role'] = $role;
	if (!empty ($meta_query) ) $args['meta_query'] = $meta_query;
	//if (!empty ($fields) ) $args['fields'] = $fields;
	
	$args['fields'] = 'all_with_meta'; //might be too huge , but fast - DOES NOT GET META DATA ??
	
	//track_progress ('Simple meta selections to pass to query: '.print_r($args, true));

	if (is_network_admin() or amr_is_network_admin()
	) $args['blog_id'] = '0';
	$all = get_users($args); // later - add selection if possible here to reduce memeory requirements 
	
//	$all = get_users(array('blog_id'=>0));
	
	track_progress('after get users, we have '.count($all));
	
	foreach ($all as $i => $userobj) { 
// save the main data, toss the rest
		foreach ($main_fields as $i2=>$v2) {
			$users[$i][$v2] = $userobj->$v2;  
		}
// we just need to expand the meta data
		if (!empty($keys)) { // if some meta requeste
			foreach ($keys as $i2 => $v2) {	
				if (!isset($userobj->$i2)) {  // in some versions the overloading does not work
					$userobj->$i2 = get_user_meta($userobj->ID, $i2, true);
				}
				if (!empty($userobj->$i2)) { 
					$temp = maybe_unserialize ($userobj->$i2);
					$temp = objectToArray ($temp); /* *must do all so can cope with incomplete objects */
					$key = str_replace(' ','_', $i2); /* html does not like spaces in the names*/
					if (is_array($temp)) { 
						foreach ($temp as $i3 => $v3) {
							$key = $i2.'-'.str_replace(' ','_', $i3); /* html does not like spaces in the names*/
							$users[$i][$key] = $v3;
							}
						}
					else $users[$i][$key] = $temp;
					unset($temp);
					// we could add some include / exclude checking here?
				}	
			} /// end for each keys
		} // 
		unset($all[$i]);
	} // end for each all
	unset($all);
	track_progress('after get users meta check');
	if (empty($users)) return (false);
return ($users);	
}
/* -----------------------------------------------------------------------------------*/
if (!function_exists('auser_msort')) {
function auser_msort($array, $cols) {
	if (empty($array)) return (false);
	/* Example: $arr2 = array_msort($arr1, array('name'=>array(SORT_DESC,SORT_REGULAR), 'cat'=>SORT_ASC));*/
	    $colarr = array();
	    foreach ($cols as $col => $order) {
	        $colarr[$col] = array();
	        foreach ($array as $k => $row) { 
				if (!isset($row[$col])) $colarr[$col]['_'.$k] = '';
				else $colarr[$col]['_'.$k] = strtolower($row[$col]); 
			}
	    }
	    $params = array();
		
	    foreach ($cols as $col => $order) {
	        $params[] = &$colarr[$col];
			$order_array = &$order;
	        $params = array_merge($params, $order_array);  // php 5.3 wants these to be references
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
				if (!isset ($array[$k][$col])) $ret[$k][$col] = '';
	            else $ret[$k][$col] = $array[$k][$col];
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

	$lastpage = ceil($totalitems / $rowsperpage);
	if ($thispage > $lastpage) $thispage = $lastpage;
	$from = (($thispage-1) * $rowsperpage) + 1;
	$to = $from + $rowsperpage-1;
	if ($to > $totalitems) $to = $totalitems;
	$totalpages = ceil($totalitems / $rowsperpage);
	$base = remove_query_arg (array('refresh','listpage'));
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
				'end_size' 	=> 1,
				'mid_size' 	=> 1,
				'add_args' 	=> false
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
/* ---------------------------------------------------------------------*/	
function amr_csv_form($csv, $suffix) {
	/* accept a long csv string and output a form with it in the data - this is to keep private - avoid the file privacy issue */
	
	return (
//		  '<form method="post" action="" id="csvexp" ><fieldset >'.
		'<input type="hidden" name="suffix" value="'.$suffix . '" />'
		.'<input type="hidden" name="csv" value="'.htmlspecialchars($csv) . '" />'
		.  '<input style="font-size: 1.5em !important;" type="submit" name="reqcsv" value="'
		.__('Export to CSV','amr-users').'" class="button" />'
//		.  '</fieldset></form>'
		);
	}
/* ---------------------------------------------------------------------*/	
if (!function_exists('amr_check_memory')) {
function amr_check_memory() { /* */

	if (!function_exists('memory_get_peak_usage')) return(false);

		$html = '';

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
	if ($amr_users_column_headings = get_option('amr-users-custom-headings')) {
		unset($amr_users_column_headings[$ulist]); 
		$results = update_option('amr-users-custom-headings', $amr_users_column_headings);
	}
	else $results = true;
	return ($results);
}

/* ---------------------------------------------------------------------- */
function amr_users_store_column_headings ($ulist, $customcols ) {
	if (!($amr_users_column_headings = get_option('amr-users-custom-headings'))) 
		$amr_users_column_headings = array();
	
	$amr_users_column_headings[$ulist] = $customcols;
	$results = update_option('amr-users-custom-headings', $amr_users_column_headings);
	if ($results) {
		echo '<div id="message" class="updated fade"><p>'
		.__('Custom Column Headings Updated')
		.'</p></div>'."\n";
			
	}
	else echo '<div id="message" class="error fade"><p>'
		.__('Column headings not updated - no change or error.')
		.'</p></div>'."\n";
		
		return ($results);
}
/* ---------------------------------------------------------------------- */
function amr_users_get_column_headings ($ulist, $cols, $icols ) {
	global $amr_users_column_headings;
	
	if ($amr_users_column_headings = get_option('amr-users-custom-headings')) {
		if (!empty($amr_users_column_headings[$ulist]) ) {
			$customcols = $amr_users_column_headings[$ulist];
			foreach ($icols as $ic => $cv) { 
				if (isset($customcols[$cv])) 
					$cols[$ic] = $customcols[$cv];
			}
			return ($cols);	
		}
	};

	return ($cols);
}
/* ---------------------------------------------------------------------*/	
if (class_exists('adb_cache')) return;
{	global $wpdb;
	class adb_cache {
	var $table_name;
			
	/* A database table is used for the cacheing in order to keep the user data private - otherwise a csv file would be used */
	/* ---------------------------------------------------------------------- */
	function adb_cache() {
		global $wpdb, $tzobj;
		amr_getset_timezone (); // sets the global timezone
//		if ($tz = get_option ('timezone_string') ) $tzobj = timezone_open($tz);	
//		else $tzobj = timezone_open('UTC');'
		$network = ausers_job_prefix();
		//track_progress('Cache Class initiated Network='.$network);
		$this->table_name = 	$wpdb->prefix.$network."amr_reportcache";
		$this->eventlog_table = $wpdb->prefix.$network."amr_reportcachelogging";
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
		$status = ausers_get_option ('amr-users-cache-status');
		$this->peakmem = $status[$reportid]['peakmem'] = amr_check_memory();	
		return(ausers_update_option ('amr-users-cache-status', $status));
	}
	/* ---------------------------------------------------------------------- */
	function record_cache_headings ($reportid, $html) {
	/* record the peak memory usage */
		$status = ausers_get_option ('amr-users-cache-status');
		$this->headings = $status[$reportid]['headings'] = $html;	
		return(ausers_update_option ('amr-users-cache-status', $status));
	}
	/* ---------------------------------------------------------------------- */
	function get_cache_headings ($reportid) {
	/* record the peak memory usage */
		$status = ausers_get_option ('amr-users-cache-status');
		if (isset( $status[$reportid]['headings'])) $html = $status[$reportid]['headings'];	
		else $html = '';
		return($html);
	}
	/* ---------------------------------------------------------------------- */
	function record_cache_start ($reportid, $name) {
		$status = ausers_get_option ('amr-users-cache-status');
		unset ($status[$reportid]);
		unset ($this);
		$this->start = $status[$reportid]['start'] = time();
		$this->name = $status[$reportid]['name'] = $name;
		return(ausers_update_option ('amr-users-cache-status', $status));
	}
	/* ---------------------------------------------------------------------- */
	function record_cache_end ($reportid, $lines) {
		$status = ausers_get_option ('amr-users-cache-status');
		$this->end = $status[$reportid]['end'] = time();
		$this->lines = $status[$reportid]['lines'] = $lines;
		$this->timetaken = $this->end - $this->start;
		return(ausers_update_option ('amr-users-cache-status', $status));
	}
	/* ---------------------------------------------------------------------- */
	function cache_in_progress ($reportid) {
	global $tzobj;
	
		$status = ausers_get_option ('amr-users-cache-status');
		//var_dump($status);
		if ((isset($status[$reportid]['start'])) and 
			(!isset($status[$reportid]['end']))) {
			$now = time();
			$diff =  $now - $status[$reportid]['start'];
			if ($diff > 60*5) {
				$d = date_create(strftime('%c',$status[$reportid]['start']));
				date_timezone_set( $d, $tzobj );
				$text = sprintf(__('Report %s started %s ago','amr-users' ), $reportid, human_time_diff($status[$reportid]['start'], time()));
				$text .= ' '.__('Something may be wrong - delete cache status, try again, check server logs and/or memory limit');
					
				$this->log_cache_event($text);
				$fun = '<a href="http://upload.wikimedia.org/wikipedia/commons/1/12/Apollo13-wehaveaproblem_edit_1.ogg" >'.__('Houston, we have a problem','amr-users').'</a>';
				$text = '<div id="message" class="updated fade"><p>'.$fun.'<br/>'.$text.'</p></div>'."\n";
				echo $text;
				return(false);
			}
			else return (true);
		}
		else return(false);			
	}
	/* ---------------------------------------------------------------------- */
	function amr_say_when ($timestamp, $report='') {
	global $tzobj;
			$d = date_create(strftime('%C-%m-%d %H:%I:%S',$timestamp)); //do not use %c - may be locale issues
			if (is_object($d)) {
				if (!is_object($tzobj)) amr_getset_timezone ();
				date_timezone_set( $d, $tzobj );
				$timetext = $d->format(get_option('date_format').' '.get_option('time_format'));
				$text = sprintf(__('Cache already scheduled for %s, in %s time', 'amr-users'),
				$timetext.' '.timezone_name_get($tzobj),human_time_diff(time(),$timestamp));
				}
			else $text = 'Unknown error in formatting timestamp got next cache: '.$timestamp;	
	return ($text);		
}	
/* ---------------------------------------------------------------------- */	
	function cache_already_scheduled ($report) {	
	$network = ausers_job_prefix();
	$args['report'] = $report;
	if ($timestamp = wp_next_scheduled('amr_'.$network.'reportcacheing',$args)) {
		$text = $this->amr_say_when ($timestamp) ;

		return $text;
	}	

	if ($timestamp = wp_next_scheduled('amr_'.$network.'reportcacheing', array())) {
		$text = $this->amr_say_when ($timestamp) ;
		return $text;
	}
	return false;
}
	/* ---------------------------------------------------------------------- */
	function last_cache ($reportid) { /* the last successful cache */
	global $tzobj;
		$status = ausers_get_option ('amr-users-cache-status');
		if ((isset($status[$reportid]['start'])) and 
			(isset($status[$reportid]['end'])))
			return(strftime('%c',round($status[$reportid]['end'])));
		else return(false);			
	}
	/* ---------------------------------------------------------------------- */
	function get_column_headings ($reportid, $line, $csvcontent ) {
		global $wpdb;	
		$wpdb->show_errors();	
		
		$csvcontent = $wpdb->escape(($csvcontent));
		
		$sql = "UPDATE " . $this->table_name .
            ' SET csvcontent = "'. $csvcontent .'"
			  WHERE  reportid = "'.$reportid.'" 
			  AND line = "'. $line.'"';

		$results = $wpdb->query( $sql );
		
		if (is_wp_error($results)) {
			echo __('Error updating report headings.','amr-users').$results->get_error_message();
			die (__('Killing myself.. please check log and status and try again later.','amr-users'));
			
			}
		return ($results);
	}

	/* ---------------------------------------------------------------------- */
	function update_column_headings ($reportid,  $csvcontent ) {
		global $wpdb;	
		$wpdb->show_errors();	
		
		$csvcontent = $wpdb->escape(($csvcontent));
		
		$sql = "UPDATE " . $this->table_name .
            ' SET csvcontent = "'. $csvcontent .'"
			  WHERE  reportid = "'.$reportid.'" 
			  AND line = "2"';

		$results = $wpdb->query( $sql );
		
		if (is_wp_error($results)) {
			echo __('Error updating report headings.','amr-users').$results->get_error_message();
			die (__('Killing myself.. please check log and status and try again later.','amr-users'));
			
			}
		return ($results);
	}
	/* ---------------------------------------------------------------------- */
	function cache_report_line ($reportid, $line, $csvcontent ) {
		global $wpdb;	
		$wpdb->show_errors();	
		
		$csvcontent = $wpdb->escape(($csvcontent));
		
		$sql = "INSERT INTO " . $this->table_name .
            " ( reportid, line, csvcontent ) " .
            "VALUES ('" . $reportid . "','" . $line . "','" . $csvcontent . "')";

		$results = $wpdb->query( $sql );
		
		if (is_wp_error($results)) {
			echo __('Error inserting - maybe clashing with a background run?','amr-users').$results->get_error_message();
			die (__('Killing myself.. please check log and status and try again later.','amr-users'));
			
			}
		return ($results);
	}
		/* ---------------------------------------------------------------------- */
	function delete_all_logs () {
	global $wpdb;
		$sql = "DELETE FROM " . $this->eventlog_table ;
		$results = $wpdb->query( $sql );
		if ($results) $text = __('Logs deleted','amr-users');
		else $text =__('No logs or Error deleting Logs.','amr-users');
	    $text = '<div id="message" class="updated fade"><p>'.$text.'<br/>'
		.'<a href="">'.__('Return', 'amr_users').'</a>'.'</p></div>'."\n";
		echo $text;
		
		
		
	}
	/* ---------------------------------------------------------------------- */
	function log_cache_event($text) {

		global $wpdb, $blogid;	
		$network = ausers_job_prefix();
		$wpdb->show_errors();			
		$datetime = date_create('now', $this->tz);
		if (!empty($network)) $text = $network.' blogid='.$wpdb->blogid.' '.$text;
		
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
	/* ---------------------------------------------------------------------- */
	function clear_cache ($reportid ) {
	global $wpdb;		
      $sql = "DELETE FROM " . $this->table_name .
             " WHERE reportid = '" . $reportid . "'";

      $results = $wpdb->query( $sql );

	  $opt = ausers_get_option('amr-users-cache-status');
	  
	  //track_progress('Reportid = '.$reportid.' opt='.print_r($opt[$reportid], true));
	  
	  if (isset($opt[$reportid])) unset ($opt[$reportid]);
	  $result = ausers_update_option('amr-users-cache-status', $opt);	
	  
	  return ($results);
	}
	/* ---------------------------------------------------------------------- */
	function clear_all_cache () {
	global $wpdb;		
      $sql = "DELETE FROM " . $this->table_name;
      $results = $wpdb->query( $sql );
	  if ($results) $text = __('Cache cleared. ','amr-users');
	  else $text =__('Error clearing cache, or no cache to clear. ','amr-users');
	  $result = ausers_delete_option('amr-users-cache-status');
	  if ($result) $text .= __('Cache status in db cleared','amr-users');
	  else $text .=__('Error clearing cache in db, or no cache to clear','amr-users');
	  
	  $text = '<div id="message" class="updated fade"><p>'.$text.'<br/>'
	.'<a href="">'.__('Return', 'amr_users').'</a>'.'</p></div>'."\n";
	
		echo $text;
	  return ($results);
	}
	/* ---------------------------------------------------------------------- */
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
	/* -------------------------------------------------------------------------------------------------------------*/	
	function reportname ($i ) {
	global $amain;
		if (!($amain = get_site_option ('amr-users-no-lists'))) 
			ameta_options();
		return($amain['names'][$i]);
	}
	/* -------------------------------------------------------------------------------------------------------------*/
	function get_cache_totallines ($reportid ) {
		$status = ausers_get_option ('amr-users-cache-status');
		if (!isset($status[$reportid]['lines'])) return(''); /* maybe no cache */
		return($status[$reportid]['lines']); 
	}
	/* -------------------------------------------------------------------------------------------------------------*/
	function get_cache_report_lines ($reportid, $start=1,  $rowsperpage ) { /* we don't want the internal names in line 0, we just want the headings and the data from line 1 onwards*/
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
	/* -------------------------------------------------------------------------------------------------------------*/	
	function search_cache_report_lines ($reportid,   $rowsperpage, $searchtext ) { /* we don't want the internal names in line 0, we just want the headings and the data from line 1 onwards*/
		global $wpdb;	
		$start=2;  // there are two lines of headings - exclude both
		$wpdb->show_errors();		
		$sql = 'SELECT line, csvcontent FROM ' . $this->table_name
             .' WHERE reportid = "'. $reportid . '"'
			.' AND csvcontent LIKE "%'.$searchtext.'%" '
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

		$html = '';	
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
		$problem = false;
		
		if (is_admin()) {
			if (!($amain = ausers_get_option ('amr-users-no-lists'))) 	 $amain = ameta_defaultmain();
		
			$wpdb->show_errors();		
			$sql = 'SELECT DISTINCT reportid AS "rid", COUNT(reportid) AS "lines" FROM ' . $this->table_name.' GROUP BY reportid';
			$results = $wpdb->get_results( $sql, ARRAY_A );  /* Now e have a summary of what isin the cache table - rid, lines */

			if ( is_wp_error($results) )	{	
				echo '<h2>'.$results->get_error_message().'</h2>';		
				return (false);			}
			else {		
						

				if (!empty($results)) {
					foreach ($results as $i => $rpt) {
						$r = intval(substr($rpt['rid'],5));   /* *** skip the 'users' and take the rest */						
						$summary[$r]['rid'] =  $rpt['rid'];
						$summary[$r]['lines'] = $rpt['lines']  - 2; /* as first two liens are headers anyway*/
						$summary[$r]['name'] = $amain['names'][intval($r)];
						}
				}		
				else  echo adb_cache::get_error('nocacheany'); 

				$status = ausers_get_option ('amr-users-cache-status');	/* Now pickup the record of starts etc reportid, start   and reportid end*/	
				if (!empty($status)) 
					foreach ($status as $rd => $se) {
					$r = intval(substr($rd,5));   /* *** skip the 'users' and take the rest */						
					if (empty( $se['end'])) {
						$now = time();
						$diff =  $now - $se['start'];
						if ($diff > 60*5) { 
							$problem = true;
							$summary[$r]['end'] = __('Taking too long, may have been aborted... delete cache status, try again, check server logs and/or memory limit', 'amr-users');					
						}
						else $summary[$r]['end'] = sprintf(__('Started %s', 'amr-users'), human_time_diff($now,$se['start'] ));			
						
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
						$summary[$r]['time_since'] = human_time_diff ($se['end'],time()); /* the time that the last cache ended */		
						$summary[$r]['time_taken'] = $se['end'] - $se['start']; /* the time that the last cache ended */	
						$summary[$r]['peakmem'] = $se['peakmem'];
						$summary[$r]['headings'] = $se['headings'];
					}
				}	
				else if (!empty($summary)) foreach ($summary as $rd => $rpt) { 
					$summary[$rd]['time_since'] = $summary[$rd]['time_taken'] = $summary[$rd]['end'] = $summary[$rd]['peakmem'] = '';
				}		
				if (!empty($summary)) { 	
					echo  '<div class="wrap" style="padding-top: 20px;"><table class="widefat" style="width:auto; ">'
						.'<caption>'.__('Report Cache Status','amr_users').' </caption>'
						.'<thead><tr><th>'.__('Report Id', 'amr-users')
						.'</th><th>'.__('Name', 'amr-users')
						.'</th><th>'.__('Lines', 'amr-users')
						.'</th><th style="text-align: right;">'.__('Ended?', 'amr-users')
						.'</th><th style="text-align: right;">'.__('How long ago?', 'amr-users')
						.'</th><th style="text-align: right;">'.__('Seconds taken', 'amr-users')
						.'</th><th style="text-align: right;">'.__('Peak Memory', 'amr-users')
						.'</th><th style="text-align: right;">'.__('Details', 'amr-users')
						.'</th></tr></thead>';	
					foreach ($summary as $rd => $rpt) {
						If (!isset($rpt['headings'])) $rpt['headings'] =  ' ';
						If (!isset($rpt['lines'])) $rpt['lines'] =  ' ';
						If (isset($rpt['rid'])) {
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
					}
				
					echo '</table></div>';
					
				}
			}
			
		}
		else echo '<h3>not admin?</h3>';
		if ($problem) {
			$fun = '<a target="_blank" title="'.__('Link to audio file of the astronauts of Apollo 13 reporting a problem.', 'amr-users').'" href="http://upload.wikimedia.org/wikipedia/commons/1/12/Apollo13-wehaveaproblem_edit_1.ogg" >'.__('Houston, we have a problem','amr-users').'</a>';
			$text = __('The background job\'s may be having problems.', 'amr-users');
			$text .= '<br />'.__('Delete all the cache records and try again', 'amr-users');
			$text .= '<br />'.__('Check the server logs and your php wordpress memory limit.', 'amr-users');
			$text .= '<br />'.__('The TPC memory usage plugin may be useful to assess whether the problem is memory.', 'amr-users');
			$text = '<div id="message" class="updated fade"><p>'.$fun.'<br/>'.$text.'</p></div>'."\n";
			echo $text;
		}
		
	
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
//if (!function_exists('amr_mimic_meta_box')) {
function amr_mimic_meta_box($id, $title, $callback ) {
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
		echo '<div class="error">'.$text.'</div>';
	}
}
/* ---------------------------------------------------------------------*/	
if (function_exists('amr_message')) return;
else {
	function amr_message ($text) {
		echo '<div class="error">'.$text.'</div>';
	}
}
/* ---------------------------------------------------------------------*/
if (function_exists('amr_feed')) return;
else {
	function amr_feed($uri, 
		$num=5, 
		$text='Recent News',
		$icon="http://webdesign.anmari.com/images/amrusers-rss.png") {
	
	$feedlink = '<h3><a href="'.$uri.'">'.$text.'</a><img src="'.$icon.'" alt="Rss icon" style="vertical-align:middle;" /></h3>';	

	if (!function_exists ('fetch_feed')) { 
		echo $feedlink;
		return (false);
		}
	if (!empty($text)) {?>
		<div>
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
	</div>
	<?php }
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
		global $wpdb;
	/* 	if the cache table does not exist, then create it . be VERY VERY CAREFUL about editing this sql */

		$table_name = ameta_cachetable_name();
		
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
		global $wpdb;
	/* 	if the cache table does not exist, then create it . be VERY VERY CAREFUL about editing this sql */
		$table_name = ameta_cachelogtable_name();
		if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
			$sql = "CREATE TABLE " . $table_name . " (
			  id mediumint(9) NOT NULL AUTO_INCREMENT,
			  eventtime datetime NOT NULL,
			  eventdescription text NOT NULL,
			  PRIMARY KEY  (id)
			);";

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
	$actions = array('delete'=>__('Delete'));
	if (!isset($two)) $two = '';

	echo '<div class="clear">';
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
	echo "\n";
	$two = '2';
	echo '</div>';
}
/* -----------------------------------------------------------*/
function amr_is_bulk_request ($type) {
	if ((isset($_REQUEST['dobulk']) or isset($_REQUEST['dobulk2']))
	and
	(($_REQUEST['dobulk'] == 'Apply') or ($_REQUEST['dobulk2'] == 'Apply' ) )
	and 
	((!empty($_REQUEST['action']) and ($_REQUEST['action'] == $type))))
	return true;
	else return false;

}
/* -----------------------------------------------------------*/
function amr_redirect_if_delete_requested () {
	if (amr_is_bulk_request ('delete'))	{
		if (isset($_REQUEST['users'])) wp_redirect(
			add_query_arg(array(
			'users'=>$_REQUEST['users'] , 
			'action'=>'delete'
			),
			wp_nonce_url(network_admin_url('users.php'),'bulk-users')));
		else {
			var_dump($_REQUEST);
			var_dump($_POST);
			var_dump($_GET);
			echo 'No users selected';
		}
		exit;
	}	
}
add_action('admin_menu','amr_redirect_if_delete_requested');

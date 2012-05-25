<?php 
/* ---------------------------------------------------------------------*/	
if (!function_exists('amr_get_href_link')) {
	function amr_get_href_link ($field, $v, $u, $linktype) {  
	
	switch ($linktype) { 
			case 'none': return '';
			case 'mailto': {
				if (!empty($u->user_email)) return ('mailto:'.$u->user_email);
				else return '';
				}
			case 'postsbyauthor': { // figure out which post type ?
				if (empty($v) or !current_user_can('list_posts')) return( ' ');
				else {
					$href = network_admin_url('edit.php?author='.$u->ID);		
							
					if (stristr($field, '_count')) { // it is a item count thing, but not a post count
						if (is_object($u) and isset ($u->ID) ) {
							$ctype = str_replace('_count', '', $field);
							$href=add_query_arg(array(
								'post_type'=>$ctype
								),
								$href
								);
							
						} // end if
					} // end if stristr
					return ($href);	
				}
				return '';
			}
			case 'edituser': {
				if (current_user_can('edit_users') and is_object($u) and isset ($u->ID) ) 
					return ( network_admin_url('user-edit.php?user_id='.$u->ID));
				else return '';
				}
			case 'authorarchive': {  // should do on a post count only
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
			case 'wplist': { // for multisite
				if (current_user_can('edit_users') and is_object($u) and isset ($u->user_login) )
					return(network_admin_url('users.php?s='.$u->user_login));
			}	
			default: return(apply_filters('amr-users-linktype-function',
				$linktype, // the current value
				$u,
				$field)); // all the user values
	}
}
}
/* -----------------------------------------------------------------------------------*/
if (!function_exists('auser_multisort')) { // an update attempt // if works well in testing then move to pluggables
function auser_multisort($arraytosort, $cols) { // $ cols has $col (eg: first name) the $order eg: ASC or DESC

	if (empty($arraytosort)) 
		return (false);
	if (empty($cols)) 
		return $arraytosort;
		
	$cols['ID'] = SORT_ASC; // just in case, lets have this as a fallback
	
	/* Example: $arr2 = array_msort($arr1, array('name'=>array(SORT_DESC,SORT_REGULAR), 'cat'=>SORT_ASC));*/
	    $colarr = array();
	    foreach ($cols as $col => $order) {
	        $colarr[$col] = array(); // eg $colarr[firstname]  
	        foreach ($arraytosort as $k => $row) { 
				if (!isset($row[$col])) 
					$colarr[$col]['_'.$k] = '';
				else 
					$colarr[$col]['_'.$k] = strtolower($row[$col]); // to make case insenstice ?
			}			
	    }
		
	    foreach ($cols as $col => $order) {  
	        $dimensionarr[] = $colarr[$col];
			$orderarr[] = $order;			
	    }
		
		if (count($dimensionarr) < 2)
			array_multisort($dimensionarr[0], $orderarr[0],
							$arraytosort);
		elseif (count($dimensionarr) == 2)
			array_multisort($dimensionarr[0], $orderarr[0],
							$dimensionarr[1], $orderarr[1],
							$arraytosort);
		elseif (count($dimensionarr) == 3)
			array_multisort($dimensionarr[0], $orderarr[0],
							$dimensionarr[1], $orderarr[1],
							$dimensionarr[2], $orderarr[2],
							$arraytosort);
		elseif (count($dimensionarr) == 4)
			array_multisort($dimensionarr[0], $orderarr[0],
							$dimensionarr[1], $orderarr[1],
							$dimensionarr[2], $orderarr[2],
							$dimensionarr[3], $orderarr[3],
							$arraytosort);
		else
			array_multisort($dimensionarr[0], $orderarr[0],
							$dimensionarr[1], $orderarr[1],
							$dimensionarr[2], $orderarr[2],
							$dimensionarr[3], $orderarr[3],
							$dimensionarr[4], $orderarr[4],
							$arraytosort);
		return($arraytosort);

	}
}

/* -------------------------------------------------------------------------------------------------------------*/
if (!function_exists('ausers_format_ausers_last_login')) {
	function ausers_format_ausers_last_login($v, $u) {
		if (!empty($v))
			return (substr($v, 0, 16)); //2011-05-30-11:03:02 EST Australia/Sydney
		else return ('');	
	}
}
/* -------------------------------------------------------------------------------------------------------------*/
// not in use
function ausers_filter_get_avatar ($avatar, $id_or_email, $size, $default, $alt) {
	if (stristr($avatar,'default')) return '';
}
/* -------------------------------------------------------------------------------------------------------------*/
if (!function_exists('ausers_format_avatar')) {
	function ausers_format_avatar($v, $u) {
	global $amain,$amr_current_list;;
		if (!isset($amain['list_avatar_size'][$amr_current_list])) {
			if (!isset($amain['avatar_size'])) 
				$avatar_size = 16;
			else	
				$avatar_size = $amain['avatar_size'];
		}
		else $avatar_size = $amain['list_avatar_size'][$amr_current_list];
		if (!empty($u->user_email))
			return (get_avatar( $u->user_email, $avatar_size )); 
		else return ('');	
	}
}
/* -------------------------------------------------------------------------------------------------------------*/
if (!function_exists('ausers_format_timestamp')) {
	function ausers_format_timestamp($v) {  
		if (empty($v)) return ('');	
		$d = date('Y-m-d H:i:s e', (int) $v) ;
		if (!$d) $d = $v;
		return (	
			'<a href="#" title="'.$d.'">'
			.sprintf( _x('%s ago', 'indicate how long ago something happened','amr-users'),
			human_time_diff($v, current_time('timestamp'))))
			.'</a>';
	}
}
/* -------------------------------------------------------------------------------------------------------------*/
if (!function_exists('ausers_format_datestring')) {
	function ausers_format_datestring($v) {  // Y-m-d H:i:s
		if (empty($v)) return ('');	
		$ts = strtotime($v);
		return ( 
			'<a href="#" title="'.$v.'">'
			.sprintf( _x('%s ago', 'indicate how long ago something happened','amr-users'),
			human_time_diff($ts, strtotime(current_time('mysql')))))
			.'</a>';
	}
}
/* -------------------------------------------------------------------------------------------------------------*/
if (!function_exists('ausers_format_usersettingstime')) {  // why 2 similar - is one old or bbpress ?
	function ausers_format_usersettingstime($v, $u) {  
		return(ausers_format_timestamp($v));
	}
}
if (!function_exists('ausers_format_user_registered')) {  // why 2 similar
	function ausers_format_user_registered($v, $u) {  
		return(ausers_format_datestring($v));
	}
}
/* -------------------------------------------------------------------------------------------------------------*/
if (!function_exists('ausers_format_user_settings_time')) {  // why 2 similar
	function ausers_format_user_settings_time($v, $u) {  
		return(ausers_format_timestamp($v));
	}
}
/* -------------------------------------------------------------------------------------------------------------*/
if (!function_exists('amr_format_user_cell')) {
function amr_format_user_cell($i, $v, $u) {  // thefield, the value, the user object
global $aopt, $amr_current_list, $amr_your_prefixes;

	/* receive the key and the value and format accordingly - wordpress has a similar user function function - should we use that? */
	$title = '';
	$href = '';
	$text = '';  
	if (isset ($aopt['list'][$amr_current_list]) ) {
		$l = $aopt['list'][$amr_current_list];
	}
	else return false;
	
	if (isset ($aopt['list'][$amr_current_list]['links'][$i]) ) {
		$lt = $aopt['list'][$amr_current_list]['links'][$i];
		$href= amr_get_href_link($i, $v, $u, $lt );
		if (!empty($href)) {
		switch ($lt) {  // depending on link type
			case 'mailto': 	$title = __('Email the user','amr-users');
				break;
			case 'edituser': 	$title = __('Edit the user','amr-users');
				break;				
			case 'authorarchive':	$title = __('Go to author archive','amr-users');
				break;
			case 'url': 	$title = __('Go to users website','amr-users');
				break;
			case 'postsbyauthor': $title = __('View posts in admin','amr-users');
				break;
			case 'commentsbyauthor': $title = __('See comments by user','amr-users');
				break;
			case 'wplist': $title = __('Go to wp userlist filtered by user ','amr-users');
				break;	
			default: $title = '';
			}//end switch
		}
	}
	else { // old one for compatibility with saved options that do not have the link types - NO else will forc even if we do not wnat any

	switch ($i) {
			case 'user_email': {  
				$href = 'mailto:'.$v;
				break;
			}
			case 'user_login': {
				if (is_object($u) and isset ($u->ID) ) {
				$href= site_url().'/wp-admin/user-edit.php?user_id='.$u->ID;
				}
				break;				
			}
			case 'post_count': {
				if (empty($v)) return( ' ');
				else if (is_object($u) and isset ($u->ID) ) {
					$href=add_query_arg('author',$u->ID, site_url());
				}
				break;
			}
			case 'user_url': {
				$href=$v;
				break;
			}
			case 'comment_count': {  /* if they have wp stats plugin enabled */
				if ((empty($v)) or (!($stats_url = get_option('stats_url')))) $href='';
				else $href=add_query_arg('stats_author',$u->user_login, $stats_url);
				break;
			}
			default: {  $href= '';		
			}
		}//end switch	
	} //end else
	
	// now get the value if special formatting required
	$generic_i = str_replace('-','_',$i); // cannot have function with dashes, so any special function must use underscores

	// strip all prefixes out, will obviosluy only be one actaully there, but we may hev a sahred user db, so may have > 1
	foreach ($amr_your_prefixes as $ip=> $tp) {  
		$generic_i = str_replace($tp, '',$generic_i  );
	}
	//if (WP_DEBUG) echo '<br />Looking for custom function: for '.$generic_i;

	if (function_exists('ausers_format_'.$generic_i) ) { 
		
		$text =  (call_user_func('ausers_format_'.$generic_i, $v, $u));
	}
	else { 
		switch ($i) {
			case 'description': {  
				$text = (nl2br($v)); break;
			}
			default: { 
				if (is_array($v)) { 
					$text = implode(',',$v);
				}
				else $text = $v;
			}
		} // end switch
	}
	
	if (!empty($text)) { 
		if (!empty($href)) {
			if (!empty ($title)) 
				$title = ' title="'.$title.'"';
			$text = '<a '.$title.' href="'.$href.'" >'.$text.'</a>';
			}
	}
/*	unfortunately - due to fields being in columns and columns being what is cached, 
the before/after formatting is done before cacheing - not ideal, should rather be in final format  
	if (!empty($text)) {
		if (!empty($l['before'][$i]))
			$text = html_entity_decode($l['before'][$i]).$text;
		if (!empty($l['after'][$i]))
			$text = $text.html_entity_decode($l['after'][$i]);
	}
*/	
	
	$text = apply_filters('amr_users_format_value', $text, $generic_i, $v, $u);
	
	return($text);
}
}
/* -------------------------------------------------------------------------------------------------------------*/
if (!function_exists('amr_do_cell')) {
	function amr_do_cell($i, $k, $openbracket,$closebracket) {
		
		return ($openbracket.$i.$closebracket);
	}
}
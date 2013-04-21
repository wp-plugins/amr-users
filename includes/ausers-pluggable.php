<?php 
// is_plugin_active only gets declared by wp in admin and late it seems, lots of trouble tryingto use it apparemtly clashing
function amr_is_plugin_active( $plugin ) {
		if (function_exists(('is_plugin_active'))) 
			return (is_plugin_active($plugin));
		else 
			return in_array( $plugin, (array) get_option( 'active_plugins', array() ) );
}

/* -------------------------------------------*/	
if (!function_exists('amr_get_href_link')) {
	function amr_get_href_link ($field, $v, $u, $linktype) {  
	
	switch ($linktype) { 
			case 'none': return '';
			case 'mailto': {
				if (!empty($u->user_email)) return ('mailto:'.$u->user_email);
				else return '';
				}
			case 'postsbyauthor': { // figure out which post type ?
			
				if (empty($v) or !current_user_can('edit_others_posts')) return( '');
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
			case 'bbpressprofile' : {
				$slug 	= get_option('_bbp_user_slug');
				$forums = get_option('_bbp_root_slug');
				return (home_url('/'
				.__( $forums ,'bbpress')
				.'/'
				.__( $slug, 'bbpress')
				.'/'.$u->user_login
				));
			}	
			default: return(apply_filters('amr-users-linktype-function',
				$linktype, // the current value
				$u,
				$field)); // all the user values
	}
}
}
/* ---------------------------------------------------------*/
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
/* -----------------------------------------------------------------------------------*/
if (!function_exists('ausers_format_ausers_last_login')) {
	function ausers_format_ausers_last_login($v, $u) {
		if (!empty($v))
			return (substr($v, 0, 16)); //2011-05-30-11:03:02 EST Australia/Sydney
		else return ('');	
	}
}
/* -----------------------------------------------------------------------------------*/
// not in use
function ausers_filter_get_avatar ($avatar, $id_or_email, $size, $default, $alt) {
	if (stristr($avatar,'default')) return '';
}
/* -----------------------------------------------------------------------------------*/
if (!function_exists('ausers_format_avatar')) {
	function ausers_format_avatar($v, $u) {
	global $amain,$amr_current_list;
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
/* -----------------------------------------------------------------------------------*/
if (!function_exists('ausers_format_timestamp')) {
	function ausers_format_timestamp($v) {  
		if (empty($v)) return ('');	
		$d = date('Y-m-d H:i:s e', (int) $v) ;
		if (!$d) $d = $v;
		return (	
			'<a href="#" title="'.$d.'">'
			.sprintf( _x('%s ago', 'indicate how long ago something happened','amr-users'),
			human_time_diff($v, current_time('timestamp')))
			.'</a>');
	}
}
/* -----------------------------------------------------------------------------------*/
if (!function_exists('ausers_format_datestring')) {
	function ausers_format_datestring($v) {  // Y-m-d H:i:s
		if (empty($v)) return ('');	
		$ts = strtotime($v);  
		if ($ts < 0) return $v;
		return ( 
			'<a href="#" title="'.$v.'">'
			.sprintf( _x('%s ago', 'indicate how long ago something happened','amr-users'),
			human_time_diff($ts, strtotime(current_time('mysql')))))
			.'</a>';
	}
}
/* -----------------------------------------------------------------------------------*/
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
/* -----------------------------------------------------------------------------------*/
if (!function_exists('ausers_format_user_settings_time')) {  // why 2 similar
	function ausers_format_user_settings_time($v, $u) {  
		return(ausers_format_timestamp($v));
	}
}
/* -----------------------------------------------------------------------------------*/
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
			case 'mailto': 			$title = __('Email the user','amr-users');
				break;
			case 'edituser': 		$title = __('Edit the user','amr-users');
				break;				
			case 'authorarchive':	$title = __('Go to author archive','amr-users');
				break;
			case 'url': 			$title = __('Go to users website','amr-users');
				break;
			case 'postsbyauthor': 	$title = __('View posts in admin','amr-users');
				break;
			case 'commentsbyauthor': $title = __('See comments by user','amr-users');
				break;
			case 'wplist': 			$title = __('Go to wp userlist filtered by user ','amr-users');
				break;	
			default: 				$title = '';
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

	// strip all prefixes out, will obviously only be one actaully there, but we may hev a sahred user db, so may have > 1
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
					$text = implode(', ',$v);
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
	else $text = '&nbsp';  // else tables dont look right
/*	unfortunately - due to fields being in columns and columns being what is cached, 
the before/after formatting is done before cacheing - not ideal, should rather be in final format  
	if (!empty($text)) {
		if (!empty($l['before'][$i]))
			$text = html_entity_decode($l['before'][$i]).$text;
		if (!empty($l['after'][$i]))
			$text = $text.html_entity_decode($l['after'][$i]);
	}
*/	
	
	$text = apply_filters('amr_users_format_value', $text, $generic_i, $v, $u); // to allow for other unusual circumstances eg ym
	
	return($text);
}
}
/* -----------------------------------------------------------------------------------*/
if (!function_exists('amr_do_cell')) {
	function amr_do_cell($i, $k, $openbracket,$closebracket) {
		
		return ($openbracket.$i.$closebracket);
	}
}
//---------------------------------------------------------------------------- just one user 
if (!function_exists('amr_display_a_line')) {
	function amr_display_a_line ($line, $icols, $cols, $user, $ahtm) {

		$linehtml = '';
		
		foreach ($icols as $ic => $c) { 			
			$w = amr_format_user_cell($c, $line[$c], $user);
			if (($c == 'checkbox') )
				$linehtml .= $ahtm['td'].' class="check-column td">'.$w. $ahtm['tdc'];
			else
				$linehtml .= $ahtm['td'].' class="'.$c.' td td'.$ic.' ">'.$w. $ahtm['tdc'];
		}
		$html =  $ahtm['tr'].' class="vcard">'.$linehtml.$ahtm['trc'];
		return ($html);
		
	}
}
//---------------------------------------------------------------------------- just the lines on this page
if (!function_exists('amr_display_a_page')) {
	function amr_display_a_page ($linessaved, $icols, $cols, $ahtm ) {
		
		$html = '';
		
		foreach ($linessaved as $il =>$line) { /// have index at this point
				
			$id = $line['ID']; /*   always have the id - may not always print it  */
			$user = amr_get_userdata($id);
			$html.= amr_display_a_line ($line, $icols, $cols, $user, $ahtm);

		}

		return ($html);
	}
}
//---------------------------------------------------------------------------- now prepare for listing
if (!function_exists('amr_display_final_list')) {
	function amr_display_final_list (
		$linessaved, $icols, $cols,
		$page, $rowsperpage, $totalitems,
		$caption,
		$search, $ulist, $c, 
		$filtercol,
		$sortedbynow,
		$options = array()) {
	global $aopt,
		$amain,
		$amrusers_fieldfiltering,
		$amr_current_list,
		$amr_search_result_count,
		$ahtm;  // the display html structure to use
	global $amr_refreshed_heading;
		
		$amr_current_list = $ulist;	
		
		$html = $hhtml = $fhtml = '';
		$filterhtml 			= '';
		$filterhtml_separate 	= '';	
		$apply_filter_html 		= '';
		$filter_submit_html 	= '';
		$summary 				= '';
		$explain_filter 		= '';

		$adminoptions = array (  // forced defaults for admin
				'show_search' 		=> true,
				'show_perpage'		=> true,
				'show_pagination' 	=> true,
				'show_headings'		=>true,
				'show_csv'			=>true,
				'show_refresh'		=>true,
				);
				
		if (!is_admin() 
		//and !empty($amain['public'][$ulist])
		) {  // set public options to overrwite admin
			foreach ($adminoptions as $i => $opt) {
				if (isset ( $options[$i]))  
					$adminoptions[$i] = $options[$i];
				else 
					$adminoptions[$i] = '';
			}
		}
		
		//if (WP_DEBUG) var_dump($adminoptions);

		if ((!empty($_REQUEST['headings'])) or // ie in admin doing headings
			(!empty($_REQUEST['filtering']))) {
				$adminoptions['show_search'] = false;
				$adminoptions['show_csv'] = false;
				$adminoptions['show_refresh'] = false;
				$adminoptions['show_perpage'] = false;
				$adminoptions['show_headings'] = false;
				$adminoptions['show_pagination'] = true;
				$amain['filter_html_type'][$amr_current_list] = 'none';// if editingheadings, then no showingfilter
			}
			
		if ( ( is_admin() OR (!isset($amain['html_type'][$amr_current_list])) )
			 )  
			
			{  // must be after the check above, so will force table in admin
			$ahtm = amr_get_html_to_use ('table');

		}
		else {
			$ahtm = amr_get_html_to_use ($amain['html_type'][$amr_current_list]);	
			
		}	
		
		if (empty($linessaved))
			$saveditems = 0;
		else
			$saveditems = count($linessaved);

		if (is_array($caption))
			$caption =  '<h3 class="caption">'.implode(', ',$caption).'</h3>';

// now fix the icols and cols for any special functioning--------------------------
			
		if ((isset($icols[0])) and ($icols[0] == 'ID')) {  /* we only saved the ID so that we can access extra info on display - we don't want to always display it */
			unset ($icols[0]);unset ($cols[0]);
		}
			
		$icols = array_unique($icols);	// since may end up with two indices, eg if filtering and grouping by same value	
				
		foreach ($icols as $i=> $col) {   
			if (($col == 'index')) {  // we only saved the index so that we can access extra info on display - we don't want to display it 	
				if (!isset($aopt['list'][$amr_current_list]['selected']['index'])) {
					unset ($icols[$i]);
					unset ($cols[$i]);
				}	
			}
			else {
				if (!isset($cols[$i])) unset ($icols[$i]);
			}	
		}
// end fix icols and cols
		if (!empty($search)) {
			$searchselectnow = sprintf(
						__('%s Users found.','amr-users')
						,$amr_search_result_count);
			$searchselectnow .=	sprintf(
						__('Searching for "%s" in list','amr-users'),
						$search);
		}  // reset count if searching

		if (isset($amain['sortable']))
				$sortable = $amain['sortable'];
			else
				$sortable = false;
		
		if (!empty($adminoptions['show_headings'])) { //admin always has
				if (is_admin()) {
					if (!empty ($amr_refreshed_heading))
						$summary = $amr_refreshed_heading;
					else
						$summary = $c->get_cache_summary (amr_rptid($ulist)) ;
				}	
				if (!empty($sortedbynow))
					$summary = str_replace ('<li class="sort">',$sortedbynow, $summary  ) ;
				if (!empty($searchselectnow)) {
					$summary = str_replace ('<li class="selected">',
					'<li class="searched">'.$searchselectnow.'</li><li class="selected">',$summary);
				}
				if (!empty($filtercol)) { 
					$text = implode(', ',$filtercol);
					$summary =	str_replace (
					'<li class="selected">',
					'<li class="selected">'.__('Selected users with: ','amr-users').$text
					//		'<li class="selected">'.__('Selected users from main list of ',count($linessaved),'amr-users')
					.'</li><li class="selected">',
					$summary);
				}
				
		}		
		if ((!empty($linessaved)) and is_admin() and current_user_can('remove_users')
		and (empty($_REQUEST['filtering']) and (empty($_REQUEST['headings']))) ) {
				// ym ***
			if (function_exists('amr_ym_bulk_update')) 			
				$name = 'ps';
			else 
				$name = 'users';	
						
			array_unshift($icols, 'checkbox');
			array_unshift($cols, '<input type="checkbox">');
			foreach ($linessaved as $il =>$line) {
				if (!empty($line['ID']))
					$linessaved[$il]['checkbox'] =
					'<input class="check-column" type="checkbox" value="'.$line['ID'].'" name="'.$name.'[]" />';
				else
					$linessaved[$il]['checkbox'] = '&nbsp;';
			}					
		}
	//
		//$sortedbynow is set if maually resorted
					
		if 	((!isset($amain['html_type'][$amr_current_list]))  or //maybe old ?
			(!isset($amain['filter_html_type'])) or
			((isset($amain['filter_html_type'][$amr_current_list]) and 
			($amain['filter_html_type'][$amr_current_list] == "intableheader")))
			) { 
				
			if (function_exists('amr_show_filters')) {  // for pseudo compatability if unmatched versions
				$filterhtml 			= amr_show_filters ($cols,$icols,$ulist,$filtercol); // will have tr and th		
			}
		}	
		elseif (!empty($amain['filter_html_type'][$amr_current_list]) and $amain['filter_html_type'][$amr_current_list] == "above") { 
			if (function_exists('amr_show_filters_alt')) {			
				$filterhtml_separate 	= amr_show_filters_alt($cols,$icols,$ulist,$filtercol); 						
			}
		}

		if (!empty($filterhtml) or (!empty($filterhtml_separate))) {
				$apply_filter_html = amr_show_apply_filter_button ($ulist);
			}			
		
		$apply_filter_html = apply_filters('amr_users_apply_filter_html',$apply_filter_html);

		if ( amr_users_can_edit('headings')) {
					$hhtml = amr_allow_update_headings ($cols,$icols,$ulist, $sortable);
		}
		elseif (is_admin() and amr_users_can_edit('filtering')) {	// in admin  and plus function available etc					
					$explain_filter 	= amr_explain_filtering ();
					$hhtml 				= amr_offer_filtering ($cols,$icols,$ulist);
					$filter_submit_html	= amr_manage_filtering_submit(); //will only show if relevant
					}
		else { 
			if (!empty($adminoptions['show_headings'])) 	
				$hhtml = amr_list_headings ($cols,$icols,$ulist,$sortable,$ahtm);	
		}
			
	// footer
		$fhtml = $ahtm['tfoot']
				.$ahtm['tr'].'>';
		if (stristr($ahtm['th'],'<th')) { // if table
			$fhtml .= $ahtm ['th'].' colspan="'.count($icols).'">'
			.amr_users_give_credit()	;
		}
		else
			$fhtml .= $ahtm['th'].'>' ;
		
				
		$fhtml .=	
				$ahtm['thc']
				.$ahtm['trc']
				.$ahtm['tfootc']; /* setup the html for the table headings */

		if (!empty($linessaved)) {
			
			$html .= amr_display_a_page ($linessaved, $icols, $cols, $ahtm );

		}
	//

		if (!empty($adminoptions['show_search']) )
				$sformtext = alist_searchform($ulist);
			else
				$sformtext = '';
	//		
		if (!empty($adminoptions['show_csv']) ) {	
				$csvtext = amr_users_get_csv_link($ulist);
				}
			else
				$csvtext = '';
	//
		if (!empty($adminoptions['show_refresh']) ) {
				$refreshtext = amr_users_get_refresh_link($ulist);
				}
			else
				$refreshtext = '';
	//
			if (!empty($adminoptions['show_perpage']))
				$pformtext = alist_per_pageform($ulist);
			else
				$pformtext = '';
				
			if (!empty($amr_search_result_count)) {
				if ($rowsperpage > $amr_search_result_count)
					$rowsperpage  = $amr_search_result_count;	
				$totalitems = 	$amr_search_result_count;	
			}
			
			if (function_exists ('amr_custom_navigation')) {
				$custom_nav = amr_custom_navigation($ulist);
			}
			else $custom_nav = '';
			
			$moretext = '';
			if (!empty($adminoptions['show_pagination']))  // allows on to just show latest x
				$pagetext = amr_pagetext($page, $totalitems, $rowsperpage);
			else {	
				$pagetext = '';
				
			}

			if (!empty($filterhtml) or !empty($hhtml)) 	{
				$hhtml =
					$ahtm['thead'].$filterhtml.$hhtml.$ahtm['theadc'];
			}		
				
							
			$html = amr_manage_headings_submit() //will only show if relevant
				.$filter_submit_html //will only show if relevant
				.$sformtext
				.$explain_filter

				.$filterhtml_separate
				.$apply_filter_html
				.$custom_nav
				.$pagetext
				.PHP_EOL.'<div id="userslist'.$ulist.'"><!-- user list-->'.PHP_EOL
				.$ahtm['table']		
				.$caption
				.$hhtml
				.$fhtml
				.PHP_EOL
				.$ahtm['tbody'].$html.$ahtm['tbodyc']
				.'<!-- end user list body-->'.PHP_EOL
				.$ahtm['tablec'].'<!-- end user list table-->'.PHP_EOL
				.PHP_EOL.'</div><!-- end user list-->'.PHP_EOL
				.'<div class="userlistfooter">'
				.$pagetext	
				.$csvtext
				.$refreshtext
				.$pformtext
				.'</div>';
			if (is_admin() ) 
				$html = PHP_EOL.'<div class="wrap" >'.$html.'</div>'.PHP_EOL;
			$html = $summary.$html;

		return ($html);
	}
}
/* ----------------------------------------------------------------------------------- */
//---------------------------------------------------------------------------- now prepare for listing
if (!function_exists('amr_empty_start_list')) {
	function amr_empty_start_list (
		$linessaved, $icols, $cols,
		$page, $rowsperpage, $totalitems,
		$caption,
		$search, $ulist, $c, 
		$filtercol,
		$sortedbynow,
		$options = array()) {
	global $aopt,
		$amain,
		$amrusers_fieldfiltering,
		$amr_current_list,
		$amr_search_result_count,
		$ahtm;  // the display html structure to use
	global $amr_refreshed_heading;
		
		$amr_current_list = $ulist;	
		
		$html = $hhtml = $fhtml = '';
		$filterhtml 			= '';
		$filterhtml_separate 	= '';	
		$apply_filter_html 		= '';
		$filter_submit_html 	= '';
		$summary 				= '';
		$explain_filter 		= '';

		$adminoptions = array (  // forced defaults for admin
				'show_search' 		=> true,
				'show_perpage'		=> true,
				'show_pagination' 	=> true,
				'show_headings'		=>true,
				'show_csv'			=>true,
				'show_refresh'		=>true,
				);
				
		if (!is_admin() 
		//and !empty($amain['public'][$ulist])
		) {  // set public options to overrwite admin
			foreach ($adminoptions as $i => $opt) {
				if (isset ( $options[$i]))  
					$adminoptions[$i] = $options[$i];
				else 
					$adminoptions[$i] = '';
			}
		}
		
		//if (WP_DEBUG) var_dump($adminoptions);

		if ((!empty($_REQUEST['headings'])) or // ie in admin doing headings
			(!empty($_REQUEST['filtering']))) {
				$adminoptions['show_search'] = false;
				$adminoptions['show_csv'] = false;
				$adminoptions['show_refresh'] = false;
				$adminoptions['show_perpage'] = false;
				$adminoptions['show_headings'] = false;
				$adminoptions['show_pagination'] = true;
				$amain['filter_html_type'][$amr_current_list] = 'none';// if editingheadings, then no showingfilter
			}
			
		if ( ( is_admin() OR (!isset($amain['html_type'][$amr_current_list])) )
			 )  
			
			{  // must be after the check above, so will force table in admin
			$ahtm = amr_get_html_to_use ('table');

		}
		else {
			$ahtm = amr_get_html_to_use ($amain['html_type'][$amr_current_list]);	
			
		}	
		
		if (empty($linessaved))
			$saveditems = 0;
		else
			$saveditems = count($linessaved);

		if (is_array($caption))
			$caption =  '<h3 class="caption">'.implode(', ',$caption).'</h3>';

// now fix the icols and cols for any special functioning--------------------------
			
		if ((isset($icols[0])) and ($icols[0] == 'ID')) {  /* we only saved the ID so that we can access extra info on display - we don't want to always display it */
			unset ($icols[0]);unset ($cols[0]);
		}
			
		$icols = array_unique($icols);	// since may end up with two indices, eg if filtering and grouping by same value	
				
		foreach ($icols as $i=> $col) {   
			if (($col == 'index')) {  // we only saved the index so that we can access extra info on display - we don't want to display it 	
				if (!isset($aopt['list'][$amr_current_list]['selected']['index'])) {
					unset ($icols[$i]);
					unset ($cols[$i]);
				}	
			}
			else {
				if (!isset($cols[$i])) unset ($icols[$i]);
			}	
		}
// end fix icols and cols
		if (!empty($search)) {
			$searchselectnow = sprintf(
						__('%s Users found.','amr-users')
						,$amr_search_result_count);
			$searchselectnow .=	sprintf(
						__('Searching for "%s" in list','amr-users'),
						$search);
		}  // reset count if searching

		if (isset($amain['sortable']))
				$sortable = $amain['sortable'];
			else
				$sortable = false;
		
		if (!empty($adminoptions['show_headings'])) { //admin always has
				if (is_admin()) {
					if (!empty ($amr_refreshed_heading))
						$summary = $amr_refreshed_heading;
					else
						$summary = $c->get_cache_summary (amr_rptid($ulist)) ;
				}	
				if (!empty($sortedbynow))
					$summary = str_replace ('<li class="sort">',$sortedbynow, $summary  ) ;
				if (!empty($searchselectnow)) {
					$summary = str_replace ('<li class="selected">',
					'<li class="searched">'.$searchselectnow.'</li><li class="selected">',$summary);
				}
				if (!empty($filtercol)) { 
					$text = implode(', ',$filtercol);
					$summary =	str_replace (
					'<li class="selected">',
					'<li class="selected">'.__('Selected users with: ','amr-users').$text
					//		'<li class="selected">'.__('Selected users from main list of ',count($linessaved),'amr-users')
					.'</li><li class="selected">',
					$summary);
				}
				
		}		

	//
		//$sortedbynow is set if maually resorted
					
		if 	((!isset($amain['html_type'][$amr_current_list]))  or //maybe old ?
			(!isset($amain['filter_html_type'])) or
			((isset($amain['filter_html_type'][$amr_current_list]) and 
			($amain['filter_html_type'][$amr_current_list] == "intableheader")))
			) { 
				
			if (function_exists('amr_show_filters')) {  // for pseudo compatability if unmatched versions
				$filterhtml 			= amr_show_filters ($cols,$icols,$ulist,$filtercol); // will have tr and th		
			}
		}	
		elseif (!empty($amain['filter_html_type'][$amr_current_list]) and $amain['filter_html_type'][$amr_current_list] == "above") { 
			if (function_exists('amr_show_filters_alt')) {			
				$filterhtml_separate 	= amr_show_filters_alt($cols,$icols,$ulist,$filtercol); 						
			}
		}

		if (!empty($filterhtml) or (!empty($filterhtml_separate))) {
				$apply_filter_html = amr_show_apply_filter_button ($ulist);
			}			
			
	// footer
		$fhtml = $ahtm['tfoot']
				.$ahtm['tr'].'>';
		if (stristr($ahtm['th'],'<th')) { // if table
			$fhtml .= $ahtm ['th'].' colspan="'.count($icols).'">'
			.amr_users_give_credit()	;
		}
		else
			$fhtml .= $ahtm['th'].'>' ;
		
				
		$fhtml .=	
				$ahtm['thc']
				.$ahtm['trc']
				.$ahtm['tfootc']; /* setup the html for the table headings */

		if (!empty($linessaved)) {
			
			$html .= amr_display_a_page ($linessaved, $icols, $cols, $ahtm );

		}
	//

		if (!empty($adminoptions['show_search']) )
				$sformtext = alist_searchform($ulist);
			else
				$sformtext = '';
	//		
		if (!empty($adminoptions['show_csv']) ) {	
				$csvtext = amr_users_get_csv_link($ulist);
				}
			else
				$csvtext = '';
	//
		if (!empty($adminoptions['show_refresh']) ) {
				$refreshtext = amr_users_get_refresh_link($ulist);
				}
			else
				$refreshtext = '';
	//
			if (!empty($adminoptions['show_perpage']))
				$pformtext = alist_per_pageform($ulist);
			else
				$pformtext = '';
				
			if (!empty($amr_search_result_count)) {
				if ($rowsperpage > $amr_search_result_count)
					$rowsperpage  = $amr_search_result_count;	
				$totalitems = 	$amr_search_result_count;	
			}
			
			if (function_exists ('amr_custom_navigation')) {
				$custom_nav = amr_custom_navigation($ulist);
			}
			else $custom_nav = '';
			
			$moretext = '';
			if (!empty($adminoptions['show_pagination']))  // allows on to just show latest x
				$pagetext = amr_pagetext($page, $totalitems, $rowsperpage);
			else {	
				$pagetext = '';
				
			}

			if (!empty($filterhtml) or !empty($hhtml)) 	{
				$hhtml =
					$ahtm['thead'].$filterhtml.$hhtml.$ahtm['theadc'];
			}		
				
							
			$html = amr_manage_headings_submit() //will only show if relevant
				.$filter_submit_html //will only show if relevant
				.$sformtext
				.$explain_filter

				.$filterhtml_separate
				.$apply_filter_html
				.$custom_nav
				.$pagetext
				.PHP_EOL.'<div id="userslist'.$ulist.'"><!-- user list-->'.PHP_EOL
				.$ahtm['table']		
				.$caption
				.$hhtml
				.$fhtml
				.PHP_EOL
				.$ahtm['tbody'].$html.$ahtm['tbodyc']
				.'<!-- end user list body-->'.PHP_EOL
				.$ahtm['tablec'].'<!-- end user list table-->'.PHP_EOL
				.PHP_EOL.'</div><!-- end user list-->'.PHP_EOL
				.'<div class="userlistfooter">'
				.$pagetext	
				.$csvtext
				.$refreshtext
				.$pformtext
				.'</div>';
			if (is_admin() ) 
				$html = PHP_EOL.'<div class="wrap" >'.$html.'</div>'.PHP_EOL;
			$html = $summary.$html;

		return ($html);
	}
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
	
	if (!empty($_REQUEST['filter'])) {
		unset($_POST['su']); unset($_REQUEST['su']); // do not do search and filter at same time.
		
		 
		$argstoadd = $_POST;
		foreach ($argstoadd as $i => $value) {
			if (empty($value)) unset($argstoadd[$i]);
		};
		//unset($argstoadd['fieldvaluefilter']);
		$base = add_query_arg($argstoadd, $base);
		//var_dump($base); 
	}	
	if (!empty($_REQUEST['su'])) {  
		$search = filter_var ($_REQUEST['su'], FILTER_SANITIZE_STRING );
		//$search = strip_tags ($_REQUEST['su']);
		$base = add_query_arg('su',$search ,$base);
	}
	if (!empty($_REQUEST['rows_per_page'])) 

		$base = add_query_arg('rows_per_page',(int) $_REQUEST['rows_per_page'],$base);  // int will force to a number
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
/* -----------------------------------------------------------------------------------*/	
?>
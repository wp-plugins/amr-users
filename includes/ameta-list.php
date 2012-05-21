<?php

function get_commentnumbers_by_author(  ) {
     global $wpdb;
	 /*** Rudimentary - if going to be used frequently (eg outside of admin area , then could do with optimistaion / cacheing */

	$approved = "comment_approved = '1'";
	$c = array();
	$comments = $wpdb->get_results(
	"SELECT user_id, comment_author_email, count(1) as \"comment_count\" FROM $wpdb->comments WHERE $approved AND user_id > 0 GROUP BY user_id, comment_author_email;" );
	foreach ($comments as $i => $v) {
		$c[$v->user_id] = $v->comment_count;
	}
	unset ($comments);
    return $c;

}
/* -------------------------------------------------------------------------------------------------------------*/
function amr_rows_per_page($rpp){  //check if rows_per_page were requested or changed, set default if nothing passed
	if (!empty($_REQUEST['rows_per_page'])) {

		return ((int) ($_REQUEST['rows_per_page']));
	}
	else if (!empty($rpp)) return($rpp);
	else return(50);
}
/* -------------------------------------------------------------------------------------------------------------*/
function amr_count_user_posts($userid, $post_type) {  // wordpress function does not allow for custom post types
    global $wpdb;
	if (!post_type_exists( $post_type )) return (false);
    $where = get_posts_by_author_sql($post_type, true, $userid);

    $count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts $where" );

    return apply_filters('get_usernumposts', $count, $userid);
	}
/* -------------------------------------------------------------------------------------------------------------*/
function amr_allow_count () { //used to allow the counting function to cost posts
	return ('read_private_posts'); //will allows us to count the taxonmies then
}
/* -------------------------------------------------------------------------------------------------------------*/
function amr_need_the_field($ulist,$field) {
global $aopt;
	$l = $aopt['list'][$ulist]; /* *get the config */

	if ((isset ($l['selected'][$field])) or
	   (isset ($l['included'][$field])) or
	   (isset ($l['excluded'][$field])) or
	   (isset ($l['includeifblank'][$field])) or
	   (isset ($l['excludeifblank'][$field])) or
	   (isset ($l['sortby'][$field])) or 
	   ($field == 'ID') or // always need the id
	   ($field == 'index')
	)
	return true;
	else
	return false;
}
/* -------------------------------------------------------------------------------------------------------------*/
function amr_rptid ($ulist) {
	if ($ulist < 10) $rptid = 'user-0'.$ulist;
	else $rptid = 'user-'.$ulist;
	return $rptid;
}
/* -------------------------------------------------------------------------------------------------------------*/
function amr_build_user_data_maybe_cache($ulist='1') {  //returns the lines of data, including the headings

	/* Get the fields to use for the chosen list type */

global $aopt, $amrusers_fieldfiltering;
global $amain;
global $wp_post_types;
global $time_start;
global $cache;
global $amr_current_list;
	$amr_current_list = $ulist;

	if (get_transient('amr_users_cache_'.$ulist)) {
		track_progress('Stop - run for '.$ulist.' in progress already according to transient');
		return false;
	}
	else track_progress('Set in progress flag for '.$ulist);
	set_transient('amr_users_cache_'.$ulist,true, 10); // 10 seconds allowed for now
	
	$network = ausers_job_prefix();
//	track_progress('Getting data for network='.$network);
	register_shutdown_function('amr_shutdown');
	set_time_limit(200);
	$time_start = microtime(true);

	ameta_options();

	$date_format = get_option('date_format');
	$time_format = get_option('time_format');

	add_filter('pub_priv_sql_capability', 'amr_allow_count');// checked by the get_posts_by_author_sql
	if (!isset($amrusers_fieldfiltering)) 
		$amrusers_fieldfiltering = false;
	if (function_exists('amr_check_for_realtime_filtering'))
		amr_check_for_realtime_filtering($ulist);
	if (empty($aopt['list'][$ulist])) {
		track_progress('No configuration for list '.$ulist);
		return false;
	}
	$l = $aopt['list'][$ulist]; /* *get the config  with any additional filtering */

	$rptid = amr_rptid($ulist);
	if (!$amrusers_fieldfiltering) { // then do cache stuff
		/* now record the cache attempt  */
		$cache = new adb_cache();
		$r = $cache->clear_cache($rptid);
//		If (!($r)) echo '<br />Cache does not exist or not cleared for '.$rptid;
		$r = $cache->record_cache_start($rptid, $amain['names'][$ulist]);
//		If (!($r)) echo '<br />Cache start not recorded '.$rptid;
//		$cache->log_cache_event(sprintf(__('Started cacheing report %s','amr-users'),$rptid));
	}// end cache

		track_progress('before get all users needed');
		$list = amr_get_alluserdata($ulist); /* keyed by user id, and only the non excluded main fields and the ones that we asked for  */
		$total = count($list);
		track_progress('after get all user data'.$total);
		
		$head = '';
		$tablecaption = '';

		if ($total > 0) {
			if (isset ($l['selected']) and (count($l['selected']) > 0))  {

				$head .= PHP_EOL.'<div class="wrap" style ="clear: both; text-align: center; font-size:largest;"><!-- heading wrap -->'
				.PHP_EOL.'<strong>'
				.$amain['names'][$ulist].'</strong>';
				/* to look like wordpress */
				$tablecaption .= '<caption> '.$amain['names'][$ulist].'</caption>';
				$head .= '<ul class="report_explanation" style="list-style-type:none;">';
		/* check for filtering */

				if (isset ($l['excluded']) and (count($l['excluded']) > 0)) {/* do headings */
					$head .= '<li><em>'.__('Excluding where:','amr-users').'</em> ';
					foreach ($l['excluded'] as $k=>$ex) {
						if (is_array($ex)) 
							$head .= ' '.agetnice($k).'='.implode(__(' or ','amr-users'),$ex).',';
						else 
							$head .= ' '.agetnice($k).'='.$ex.', ';
						foreach ($list as $iu=>$user) {
							if (isset ($user[$k])) { /* then we need to check the values and exclude the whole user if necessary  */
								if (is_array($ex)) {
									if (in_array($user[$k], $ex)) {								
										unset ($list[$iu]);
									}
								}
								else {
									if ($user[$k] == $ex) {
										unset ($list[$iu]);
									}
								}
							}
						}
					}
					$head = rtrim($head,',');
					$head .='</li>';

				}

				if (isset ($l['excludeifblank']) and (count($l['excludeifblank']) > 0)) 	{
					$head .= '<li><em>'.__('Exclude if blank:','amr-users').'</em> ';
					foreach ($l['excludeifblank'] as $k=>$tf) {
						$head .= ' '.agetnice($k).',';
						foreach ($list as $iu=>$user) { /* now check each user */

							if (empty($user[$k])) { /* if does not exists or empty then we need to check the values and exclude the whole user if necessary  */
								unset ($list[$iu]);
							}
						}
					}
					$head = rtrim($head,',');
					$head .='</li>';
				}
				track_progress('after excluding users');
				if (isset ($l['includeonlyifblank']) and (count($l['includeonlyifblank']) > 0)) 	{
					$head .= '<li><em>'.__('Include only if blank:','amr-users').'</em> ';
					foreach ($l['includeonlyifblank'] as $k=>$tf) {
						$head .= ' '.agetnice($k).',';
						foreach ($list as $iu=>$user) { /* now check each user */
							if (!empty($user[$k])) { /* if does not exists or empty then we need to check the values and exclude the whole user if necessary  */
								unset ($list[$iu]);
							}
						}
					}
					$head = rtrim($head,',');
					$head .='</li>';
				}

				if (isset ($l['included']) and (count($l['included']) > 0)) {
					$head .= '<li><em>'.__('Including where:','amr-users').'</em> ';
					foreach ($l['included'] as $k=>$in) {
						
						$inc = implode(__(' or ','amr-users'),$in);
						$head .= ' '.agetnice($k).'='.$inc.',';
						if (!empty($list)) {
							foreach ($list as $iu => $user) { /* for each user */
								if (isset ($user[$k])) {/* then we need to check the values and include the  user if a match */
									if (!(in_array($user[$k], $in))) {
										unset ($list[$iu]);
									}
								}
								else unset ($list[$iu]);
							}
						}
					}
					$head = rtrim($head,',');
					$head .='</li>';
				}
				//track_progress('after checking includes '.$ulist);
				if (isset ($l['sortby']) and (count($l['sortby']) > 0)) {
					$head .= '<li class="sort"><em>'.__(' Cache sorted by: ','amr-users').'</em>';			/* class used to replace in the front end sort info */
					asort ($l['sortby']);  // sort the sortbys first, so that $cols is in right order
					
					
					$cols= array();
					foreach ($l['sortby'] as $sbyi => $sbyv) {
						if (isset($l['sortdir'][$sbyi]))
							//$cols[$sbyi] = array(SORT_DESC);  20111214
							$cols[$sbyi] = SORT_DESC;
						else
							//$cols[$sbyi] =  array(SORT_ASC);  20111214
							$cols[$sbyi] =  SORT_ASC;
						$head .= agetnice($sbyi).',';
					}
					//track_progress('after sortby '.$ulist);
					$head = rtrim($head,',');
					$head .='</li>';
					
					//track_progress('before msort cols =  '.count($cols));
					if (!empty($cols)) 
						$list = auser_multisort($list, $cols );
					//track_progress('after msort '.$ulist);	

				}
				
				unset($cols);
				if (empty($list))
					$tot = 0;
				else				
					$tot = count($list);
				track_progress('after sorting '.$tot.' users');
				
				
				if ($tot === $total)
					$text = sprintf(__('All %1s Users processed.', 'amr-users'), $total);
				else
					$text = sprintf( __('%1s Users processed from total of %2s', 'amr-users'),$tot, $total);
				$head .=  '<li class="selected">'.$text.'</li></ul>'.
				PHP_EOL.
				'</div><!-- heading wrap -->'.PHP_EOL;
				$html = $head;

				$count = 0;

				//now make the fields into columns

				if ($tot > 0) { //if (empty($list)) echo '<br />1What happened list is empty ';
					$sel = ($l['selected']);  
					asort ($sel); /* get the selected fields in the display  order requested */

					foreach ($sel as $s2=>$sv) {
						if ($sv > 0) $s[$s2] = $sv;
					}

					// here we can jump in and save the filter values, if we are NOT already doing a real timefilter
					// if do filtering , then build up filter for values now
					if (!$amrusers_fieldfiltering  and function_exists('amr_save_filter_fieldvalues')) {
						$combofields = amr_get_combo_fields($ulist);
						if (empty($list)) echo '<br />What happened list is empty ';
						amr_save_filter_fieldvalues($ulist, $list, $combofields);
					}


					/* get the col headings ----------------------------*/
					$lines[0] = amr_build_cols ($s); // tech headings
					$lines[1] = amr_build_col_headings ($s);

					// the headings lines
					foreach ($lines[1] as $jj => $kk) {
							if (empty($kk)) 
								$lines[1][$jj] = '""'; /* there is no value */
							else 
								$lines[1][$jj] = '"'.str_replace('"','""',$kk).'"'; /* Note for csv any quote must be doubleqouoted */
					}

					if (!$amrusers_fieldfiltering) { // then do cache stuff
					/* cache the col headings ----------------------------*/

						//$csv = implode (",", $iline);
						$cache->cache_report_line($rptid,0,$lines[0]); /* cache the internal column headings */
//					$cols = amr_users_get_column_headings  ($ulist, $line, $iline);
						//$csv = implode (",", $line);
						$cache->cache_report_line($rptid,1,$lines[1]); /* cache the column headings */
						//unset($cols);
						//unset($line);unset($iline);
						unset($lines);

						track_progress('before cacheing list');

					}
					

					$count = 1;
										
					
					if (!empty($list)) {
											
						foreach ($list as $j => $u) {
							//if (WP_DEBUG) echo '<br />Building list add: '.$j; var_dump($u);;
							$count  = $count +1;
							unset ($line);
							if (!empty($u['ID'])) 
								$line[0] = $u['ID']; /* should be the user id */
							else 
								$line[0] = '';

								
							foreach ($s as $is => $v) {  /* defines the column order */
							
								$colno = (int) $v;
								if (!(isset($u[$is])))
									$value = ''; /* there is no value */
								else
									$value =  $u[$is];
									
								/* unfortunately for fields, this must be done here */	
								if (!empty($value)) {
									if (!empty($l['before'][$is]))
										$value = html_entity_decode($l['before'][$is]).$value;
									if (!empty($l['after'][$is]))
										$value = $value.html_entity_decode($l['after'][$is]);
								}
								
								if (!empty($line[$colno]))
									$line[$colno] .= $value;
								else
									$line[$colno] = $value;
							}
							/* ******  PROBLEM - ok now? must be at end*/								
							if (!empty($u['index'])) 
								$line[99999] = $u['index']; /* should be the user id */
							else 
								$line[99999] = '';
							$lines[$count] = $line;
							unset ($line);

						}
					}	
					if (empty($lines)) {echo '<br / >Problem - no lines';}
					//else if (WP_DEBUG) {echo '<br />'; var_dump($lines);}

					unset($list); // do not need list, we got the lines now


				if (!$amrusers_fieldfiltering) { // then do cache stuff
					$cache->cache_report_lines($rptid, 2, $lines);
				}

				}
				else $html .= sprintf( __('No users found for list %s', 'amr-users'), $ulist);
			}
			else 
				$html .=  '<h2 style="clear:both; ">'.sprintf( __('No fields chosen for display in settings for list %s', 'amr-users'), $ulist).'</h2>';
		}
		else $html .= __('No users in database! - que pasar?', 'amr-users');
		unset($s);
		track_progress('nearing end');

		if (!$amrusers_fieldfiltering) { // if we are not just doing a real time filtering where we will not have full data then do cache stuff
			$cache->record_cache_end($rptid, $count-1);
			$cache->record_cache_peakmem($rptid);
			$cache->record_cache_headings($rptid, $html);
			$time_end = microtime(true);
			$time = $time_end - $time_start;
			$cache->log_cache_event('<em>'
			.sprintf(__('Completed %s in %s microseconds', 'amr-users'),$rptid, number_format($time,2))
			.'</em>');
		}

		if (!empty($amain['public'][$ulist])) { // returns url if set to go to file
			$csvurl = amr_generate_csv($ulist, true, false,'csv','"',',',chr(13).chr(10), true );
		}

		delete_transient('amr_users_cache_'.$ulist); // so another can run
		track_progress('Release in progress flag for '.$ulist);
		delete_transient('amr-users-html-for-list-'.$ulist); // to force use of new one
		if (!empty($lines)) return ($lines);
		else return false;
}
/* -------------------------------------------------------------------------------------------------------------*/
function amr_try_build_cache_now ($c, $i, $rptid) { // the cache object, the report id, the list number
global $amain;
		if ($c->cache_in_progress($rptid)) {
			echo ( '<div style="clear:both;"><strong>'.$amain['names'][$i].' ('.$rptid.') '.$c->get_error('inprogress').'</strong></div>');
			return (false);
		}
		else {
//			echo '<div class="loading" style="clear:both;">'
//			.__('Realtime filtering or Refresh of cache needed or requested. Please be patient.').'</div>';
//			flush();
			if (is_admin())
				return amr_rebuild_in_realtime_with_info($i);
			else
				return amr_build_user_data_maybe_cache($i);

//			amr_loading_message_js();
			return true;
		}
}
/* -------------------------------------------------------------------------------------------------------------*/
function alist_one($type='user', $ulist=1 ) {

//options  can be headings, csv, show_search, show_perpage
	/* Get the fields to use for the chosen list type */
global $aopt,
	$amain,
	$amrusers_fieldfiltering,
	$amr_current_list,
	$amr_search_result_count;

	
	do_action('amr-add-criteria-to-list', $ulist);   
	// allows one to force criteria into the request field for example (eg: show only logged in user)
	
	if (!is_admin() and amr_first_showing ()) { // no filters, no search, no sort, nothing special happening
		$html = get_transient('amr-users-html-for-list-'.$ulist );
		if (!empty($html)) {
			if (current_user_can('administrator')) {
				echo '<br /><a href="'.add_query_arg('refresh','1').'" title="'.__('Note to logged in admin only: Now using temporary saved html (transient) for frontend.  Click to refresh.').'">!</a>';
			}
			return( $html);
		}	
	}

	$caption 	= '';
	$sortedbynow = '';
	if (empty ($aopt['list'][$ulist])) {
		printf(__('No such list: %s','amr-users'),$ulist); 
		return;
		}
	$l = $aopt['list'][$ulist]; /* *get the config */

	if (empty($amain['list_rows_per_page'][$ulist]))  
		$amain['list_rows_per_page'][$ulist] = $amain['rows_per_page'];
	$rowsperpage = amr_rows_per_page($amain['list_rows_per_page'][$ulist]); // will check request
	if (!empty ($_REQUEST['listpage']))
		$page = (int) $_REQUEST['listpage'];
	else
		$page=1;

// figure out what we are doing - searching, filtering -------------------------------------------------------


	$search = '';	
	
	if (!empty($_REQUEST['su']))
		$search = filter_var ($_REQUEST['su'], FILTER_SANITIZE_STRING );
	elseif (isset($_REQUEST['clear_filtering'])) { 	// we do not need these then
		unset($_REQUEST['fieldnamefilter']);
		unset($_REQUEST['fieldvaluefilter']);
		unset($_REQUEST['filter']);
		//do we neeed to unset the individual cols? maybe not
	}

//
	$amrusers_fieldfiltering = false;
	if (!empty($_REQUEST['filter'])) { 
		//if (WP_DEBUG) {echo '<h1>Filtering</h1>';}
		foreach (array('fieldnamefilter', 'fieldvaluefilter') as $i=> $filtertype) {
			if (isset($_REQUEST[$filtertype])) {  
				foreach ($_REQUEST[$filtertype] as $i => $col) {
					if (empty($_REQUEST[$col])) {//ie showing all
						unset($_REQUEST[$filtertype][$i]);
						unset($_REQUEST[$col]);
					}
					else $amrusers_fieldfiltering = true;  // set as we are maybe doing realtime filtering flag
				};
			}
		}	
	}
	
	$c = new adb_cache();
	$rptid = $c->reportid($ulist, $type);

	if ($amrusers_fieldfiltering) {
		$lines = amr_build_user_data_maybe_cache($ulist); // since we are filtering, we will run realtime, but not save, else we would lose the normal report
		if (empty($lines)) return;
		$totalitems = count($lines);
		//if (WP_DEBUG) echo '<br /> field filtering & $totalitems='.$totalitems;
	}
	else { 
		if ((!($c->cache_exists($rptid))) or (isset($_REQUEST['refresh']))) {
			if (WP_DEBUG) _e('Either refresh requested OR no cache exists.  A rebuild will be initiated .... ');
			$success = amr_try_build_cache_now ($c, $ulist, $rptid) ;
			//$lines = amr_build_user_data_maybe_cache($ulist);  
			$totalitems = $c->get_cache_totallines($rptid);
			//now need the lines, but first, paging check will tell us how many
			$amrusers_fieldfiltering = false; // already done if it must be
		}
		else {
			$totalitems = $c->get_cache_totallines($rptid);
			
		}
	}

	//---------- setup paging variables
	if ($totalitems < 1) {
				_e('No lines found.','amr-users');
				echo amr_users_get_refresh_link($ulist);
				return;
		}
	if ($rowsperpage > $totalitems)
		$rowsperpage  = $totalitems;

	$lastpage = ceil($totalitems / $rowsperpage);
	if ($page > $lastpage) 
		$page = $lastpage;
	if ($page == 1)
		$start = 1;
	else
		$start = 1 + (($page - 1) * $rowsperpage);
	

	$filtercol = array();

	
//------------------------------------------------------------------------------------------		get the data
		if (!$amrusers_fieldfiltering) { // because already have lines if were doing field level filtering	
			$headinglines = $c->get_cache_report_lines ($rptid, 0, 2); /* get the internal heading names  for internal plugin use only */  /* get the user defined heading names */

			if (!defined('str_getcsv'))
				$icols = amr_str_getcsv( ($headinglines[0]['csvcontent']), ',','"','\\');
			else
				$icols = str_getcsv( $headinglines[0]['csvcontent'], ',','"','\\');

			if (!defined('str_getcsv'))
				$cols = amr_str_getcsv( $headinglines[1]['csvcontent'], '","','"','\\');
			else
				$cols = str_getcsv( $headinglines[1]['csvcontent'], ',','"','\\');

			if (isset($_REQUEST['filter']) or !empty($_REQUEST['sort']) or (!empty($_REQUEST['su']))) {
				$lines = amr_get_lines_to_array ($c, $rptid, 2, $totalitems+1 , $icols /* the controlling array */); 			}
			else {
				$lines = amr_get_lines_to_array($c, $rptid, $start+1, $rowsperpage, $icols );
			}
			//echo '<br />Not field filtering so far we have :'.count($lines).'<br />';
		}
		else {
			unset ($lines[0]); // the tech lines and the headings line
			unset ($lines[1]);
			
			$totalitems = count($lines); // must be here, only reset for field filtering
			$s = $l['selected'];
			asort ($s); /* get the selected fields in the display  order requested */
			$cols 	= amr_build_col_headings($s);
			$icols 	= amr_build_cols ($s);

			foreach ($lines as $i => $j) {
				$lines[$i] = amr_convert_indices ($j, $icols);
			}
		}
		
//------------------------------------------------------------------------------------------		display time filter check
		if (isset($_REQUEST['filter'])) {
		// then we are filtering
			//if (WP_DEBUG) echo '<br />Check for filtering at display time <br />'; //var_dump($icols);

			foreach ($icols as $cindex => $col) {
				if (!empty ($_REQUEST[$col]) ) {
					if ((!(isset ($_REQUEST['fieldnamefilter']) and in_array($col, $_REQUEST['fieldnamefilter']))) and
					   (!(isset ($_REQUEST['fieldvaluefilter']) and in_array($col, $_REQUEST['fieldvaluefilter'])))) {
					
						$filtercol[$col] = esc_attr($_REQUEST[$col]);

					}
				}
			}
			if (isset($_REQUEST['index'])) {
				$filtercol['index'] = strip_tags($_REQUEST['index']);
			}
			if (!$amrusers_fieldfiltering and empty($filtercol) and current_user_can('manage_options')) {  //nlr or perhaps only if by url?
				echo '<p>';
				_e('This Message shows to admin only!','amr_users');
				echo '<br />';
				_e('Filter requested.','amr_users');_e('No valid filter column given.','amr_users');
				echo '<br />';	_e('Column filter Usage is :','amr_users');	
				echo '<br /><strong>';
				echo '?filter=hide&column_name=value<br />';
				echo '?filter=show&column_name=value</br> ';
				echo '?filter=1&column_name=value';  
				echo '</strong></br> ';
				_e('Note: Hide only works if the column is currently being displayed.' );
				_e('For this list, expecting column_name to be one of ','amr_users');
				echo '<br />'.implode('<br />',$icols);
				echo '</p>';
			}

			if (!empty($filtercol)) { // for each of the filter columns that are not field filters
				foreach ($filtercol as $fcol => $value) {
					//if (WP_DEBUG) {echo '<hr>Apply filters for field "'.$fcol. '" against... '; }
					foreach ($lines as $i=> $line) {
						//if (WP_DEBUG) {echo '<br>line=';  var_dump($line);}
						if ($value === '*') {
							if (empty($line[$fcol]) ) unset ($lines[$i]);
							else {}
						}
						elseif ($value === '-') {
							if (!empty($line[$fcol]) ) 
								unset ($lines[$i]);
							else {}
						}
						elseif (empty($line[$fcol]) ) 	{
							unset ($lines[$i]);
						}
						else if (!strstr($line[$fcol],$value )) {// fuzzy filtering
						
							unset ($lines[$i]);
						
						}
						//else if (!($line[$fcol] == $value)) {  strisstr will catch these ?
						//}

						if (($_REQUEST['filter'] == 'hide') ) {  
							unset($lines[$i][$fcol]);
						}
					} // if hiding, delete that column
					if (($_REQUEST['filter'] == 'hide') ) {
						foreach ($icols as $cindex=> $col) {
							
							if ($fcol == $col) {
								unset ($icols[$cindex]);
								unset ($cols[$cindex]);
							}
						}
					} // end delete col
					//if (WP_DEBUG) echo '<br />Lines left '.count($lines);
				}
//-----------------------------------------------------------------------------
				$amr_search_result_count = count($lines);
				
				$totalitems = $amr_search_result_count;
				// slice the right section of the returned values based on rowsperpage and currentpage
				// update the paging variables
				if (($amr_search_result_count > 0) and ($rowsperpage > $amr_search_result_count))
					$rowsperpage  = $amr_search_result_count;

				$lastpage = ceil($amr_search_result_count / $rowsperpage);
				if ($page > $lastpage)
					$page = $lastpage;
				if ($page == 1)
					$start = 1;
				else {
					$start = 1 + (($page - 1) * $rowsperpage);
						
					}
					
			}
			//echo '<br />count lines = '.$amr_search_result_count. ' '.$start. ' '. $rowsperpage;
			$lines = array_slice($lines, $start-1, $rowsperpage,true);	
		}  //end if

//------------------------------------------------------------------------------------------	 check for sort or search
		if (!empty($_REQUEST['sort']) or (!empty($search))) {
		/* then we want to sort, so have to fetch ALL the lines first and THEN sort.  Keep page number in case workingthrough the list  ! */
		// if searching also want all the lines first so can search within and do pagination correctly

			if ($lines) { 
				$linesunsorted = amr_check_for_sort_request ($lines);
				$linesunsorted = array_values($linesunsorted); /* reindex as our indexing is stuffed and splice will not work properly */
				//if (!empty($search)) $totalitems = count($linesunsorted);	//save total here before splice
				$lines = array_splice($linesunsorted, $start-1, $rowsperpage );
				unset($linesunsorted); // free up memory?

				/* now fix the cache headings*/
				$sortedbynow = '';
				if (!empty($_REQUEST['sort'])) {
					foreach ($icols as $i=>$t) {
						if ($t == $_REQUEST['sort'])
							$sortedbynow = strip_tags($cols[$i]) ;
					}
					$sortedbynow = '<li><em>'
						.__('Sorted by:','amr-users').'</em>'.$sortedbynow.'</li><li class="sort">';
				}
				
			}
		}

//------------------------------------------------------------------------------------------------------------------finished filtering and sorting

		$html = amr_display_final_list (
			$lines, $icols, $cols,
			$page, $rowsperpage, $totalitems,
			$caption,
			$search, $ulist, $c, $filtercol,
			$sortedbynow);

		$expiration = (empty($amain['transient_expiration']) ? 60 : $amain['transient_expiration']);	
		if (!is_admin() and amr_first_showing ()) { // no filters, no search, no sort, nothing special happening
		//do not uset transint in admin else may show the offer filtering too when in front end
			set_transient('amr-users-html-for-list-'.$ulist, $html ,$expiration );
			track_progress('Transient set for html for list '.$ulist);
		}
		
			
		return $html;
}
//-----------------------------------------------------------------------------------------------
function amr_first_showing() {
	if ((!isset($_REQUEST['filter'])) and
		(!isset($_REQUEST['su'])) and
		(!isset($_REQUEST['clear_filtering'])) and
		(!isset($_REQUEST['listpage'])) and
		(!isset($_REQUEST['rows_per_page'])) and
		(!isset($_REQUEST['refresh'] ))and
		(!isset($_REQUEST['dir'])) and
		(!isset($_REQUEST['sort'])) 

	)
	return true;  // ie then we can try using the transient
	else {
		return false;
	}
}
//----------------------------------------------------------------------------     now prepare for listing
function amr_display_final_list ($linessaved, $icols, $cols,
	$page, $rowsperpage, $totalitems,
	$caption,
	$search, $ulist, $c, $filtercol,
	$sortedbynow) {
global $aopt,
	$amain,
	$amrusers_fieldfiltering,
	$amr_current_list,
	$amr_search_result_count,
	$ahtm;  // the display html structure to use
	$amr_current_list = $ulist;	
	
	$html = $hhtml = $fhtml = '';
	$filterhtml 			= '';
	$filterhtml_separate 	= '';	
	$apply_filter_html 		= '';
	$filter_submit_html 	= '';
	$summary 				= '';
	$explain_filter 		= '';

	$adminoptions = array (  // forced defaults for admin
			'show_search' => true,
			'show_perpage' => true,
			'show_headings'=>true,
			'show_csv'=>true,
			'show_refresh'=>false,
			);
			
			
	if (!is_admin() 
	//and !empty($amain['public'][$ulist])
	) {  // set public options to overrwite admin
		foreach ($adminoptions as $i => $opt) {
			if (isset ( $amain[$i][$ulist]))  
				$adminoptions[$i] = $amain[$i][$ulist];
			else 
				$adminoptions[$i] = '';
		}
	}

	if ((!empty($_REQUEST['headings'])) or
		(!empty($_REQUEST['filtering']))) {
			$adminoptions['show_search'] = false;
			$adminoptions['show_csv'] = false;
			$adminoptions['show_perpage'] = false;
			$adminoptions['show_headings'] = false;
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

		
	if ((isset($icols[0])) and ($icols[0] == 'ID')) {  /* we only saved the ID so that we can access extra info on display - we don't want to always display it */
			unset ($icols[0]);unset ($cols[0]);
	}
	foreach ($icols as $i=> $col) {
		if ($col == 'index') {  /* we only saved the index so that we can access extra info on display - we don't want to display it */
				unset ($icols[$i]);unset ($cols[$i]);
				
		}
	}
	//var_dump();

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
			if (is_admin()) 
				$summary = $c->get_cache_summary (amr_rptid($ulist)) ;
			if (!empty($sortedbynow))
				$summary = str_replace ('<li class="sort">',$sortedbynow, $summary  ) ;
			if (!empty($searchselectnow)) {
				$summary = str_replace ('<li class="selected">',
				'<li class="searched">'.$searchselectnow.'</li><li class="selected">',$summary);
			}
			if (!empty($filtercol)) { 
				$summary =	str_replace ('<li class="selected">','<li class="selected">'.__('Selected users from main list of ',count($linessaved),'amr-users'),$summary);
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
			$hhtml = amr_table_headings ($cols,$icols,$ulist,$sortable,$ahtm);	
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
			

	//	}
	
	if (!empty($linessaved)) {
			
			foreach ($linessaved as $il =>$line) { /// have index at this point
			
				$id = $line['ID']; /*   always have the id - may not always print it  */
				$user = amr_get_userdata($id);
				$linehtml = '';
				foreach ($icols as $ic => $c) { 			
					
					$w = amr_format_user_cell($c, $line[$c], $user);
					
					if (($c == 'checkbox') )
						$linehtml .= $ahtm['td'].' class="check-column td">'.$w. $ahtm['tdc'];
					else
						$linehtml .= $ahtm['td'].' class="'.$c.' td td'.$ic.' ">'.$w. $ahtm['tdc'];
				}
				$html .=  $ahtm['tr'].' class="vcard">'.$linehtml.$ahtm['trc'];
			}
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
		
		$pagetext = amr_pagetext($page, $totalitems, $rowsperpage);

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
			.$pagetext
			.$csvtext
			.$refreshtext
			.$pformtext;
		if (is_admin() ) 
			$html = PHP_EOL.'<div class="wrap" >'.$html.'</div>'.PHP_EOL;
		$html = $summary.$html;

	return ($html);
}
/* --------------------------------------------------------------------------------------------*/
function amr_get_lines_to_array ($c, $rptid, $start, $rows, $icols /* the controlling array */) {
global $amr_search_result_count;

	if (!empty($_REQUEST['su'])) {		// check for search request
		$s = filter_var ($_REQUEST['su'], FILTER_SANITIZE_STRING );
		$lines = $c->search_cache_report_lines ($rptid, $rows, $s);
		$amr_search_result_count = count($lines);
	}
	else {
		$lines = $c->get_cache_report_lines ($rptid, $start, $rows );
	}

	if (!($lines>0)) {amr_flag_error($c->get_error('norecords'));	return (false);	}
	foreach ($lines as $il =>$l) {
		if (!defined('str_getcsv'))
			$lineitems = amr_str_getcsv( ($l['csvcontent']), '","','"','\\'); /* break the line into the cells */
		else
			$lineitems = str_getcsv( $l['csvcontent'], ',','"','\\'); /* break the line into the cells */

		$linehtml = '';
		$linessaved[$il] = amr_convert_indices ($lineitems, $icols);

	}
	unset($lines);
	return ($linessaved);
}
/* --------------------------------------------------------------------------------------------*/
function amr_convert_indices ($lineitems, $icols) {

		foreach ($icols as $ic => $c) { /* use the icols as our controlling array, so that we have the internal field names */

			if (isset($lineitems[$ic])) {
				$w = $lineitems[$ic];
			}
			else $w = '';
			$line[$c] = stripslashes($w);
		}
		return ($line);

}
/* --------------------------------------------------------------------------------------------*/
function amr_check_for_sort_request ($list, $cols=null) {
/* check for any sort request and then sort our cache by those requests */
	$dir=SORT_ASC;
	if ((!empty($_REQUEST['dir'])) and ($_REQUEST['dir'] === 'SORT_DESC' ))  $dir=SORT_DESC;
	//20111214
	if (!empty($_REQUEST['lastsort'])) { $lastsort = esc_attr($_REQUEST['lastsort']); }
	else $lastsort = 'ID';
	if (!empty($_REQUEST['lastdir'])) { $lastdir = esc_attr($_REQUEST['lastdir']); }
	else $lastdir = SORT_ASC;
	//..20111214
	if (!empty($_REQUEST['sort'])) {
		//$cols = array($_REQUEST['sort'] => array($dir), 'ID' => array($dir) );   20111214
		$cols = array($_REQUEST['sort'] => $dir, $lastsort => $lastdir );
		$list = auser_multisort($list, $cols );
		return($list);
	}
	else return($list);
}
/* --------------------------------------------------------------------------------------------*/
function alist_one_widget ($type='user', $i=1, $do_headings=false, $do_csv=false, $max=10){
/* a widget version of alist one*/
	/* Get the fields to use for the chosen list type */
global $aopt;
global $amain;

	$c = new adb_cache();
	$rptid = $c->reportid($i, $type);

		$line = $c->get_cache_report_lines ($rptid, '0', '2'); /* get the internal heading names  for internal plugin use only */  /* get the user defined heading names */

		if (!defined('str_getcsv')) $icols = amr_str_getcsv( $line[0]['csvcontent'], ',','"','\\');
		else $icols = str_getcsv( $line[0]['csvcontent'], ',','"','\\');
//		if (!defined('str_getcsv')) $cols = amr_str_getcsv( $line[1]['csvcontent'], '","','"','\\');
//		else $cols = str_getcsv( $line[1]['csvcontent'], ',','"','\\');

		foreach ($icols as $ic => $cv) { /* use the icols as our controlling array, so that we have the internal field names */
				$v = $cols[$ic];

				$html .= '<th>'.$v.'</th>';
			}
		$hhtml = '<thead><tr>'.$html.'</tr></thead>'; /* setup the html for the table headings */
		$fhtml = '<tfoot><tr>'.$html.'</tr>'

		.'</tfoot>'; /* setup the html for the table headings */

		$html='';
		$totalitems = $c->get_cache_totallines($rptid);
		$lines = $c->get_cache_report_lines ($rptid, $start+1, $max );


		if (!($lines>0)) {
			amr_flag_error($c->get_error('numoflists'));
			return (false);
		}
		foreach ($lines as $il =>$l) {

			$id = $lineitems[0]; /*  *** pop the first one - this should always be the id */

			$user = amr_get_userdata($id);
			unset($linehtml);
			foreach ($icols as $ic => $c) { /* use the icols as our controlling array, so that we have the internal field names */

			
				$v = $lineitems[$ic];
				$linehtml .= '<td>'.amr_format_user_cell($c, $v, $user). '</td>';
			}
			$html .=  PHP_EOL.'<tr>'.$linehtml.'</tr>';
		}

//		$html = '<div class="wrap" style="clear:both;">'
		$html = '<table>'.$hhtml.$fhtml.'<tbody>'.$html.'</tbody></table>';

	return ($html);
}
/* --------------------------------------------------------------------------------------------*/
function amr_list_user_meta(){   /* Echos out the paginated version of the requested list */
global $aopt;
global $amain;
global $amr_nicenames;
global $thiscache;

	if (isset($_POST['info_update']) or amr_is_bulk_request ('ym_update')) {
		amr_ym_bulk_update();
		return;
	}
	ameta_options();
	if (!isset ($aopt['list'])) {
		_e ("No lists Defined", 'amr-users');
		return false;
		}
	if (isset ($_REQUEST['ulist'])) {
		$l = (int) $_REQUEST['ulist'];
	}
	else {
		if (isset($_REQUEST['page']))  { /*  somehow needs to be ? instead of & in wordpress admin, so we don't get as separate  */
			$param = 'ulist=';
			$l = substr (stristr( $_REQUEST['page'], $param), strlen($param));
			}
		else {
			//echo '<br />what is happening ?';
			//var_dump($_REQUEST);
			}
	}
	if ($l < 1) $l = 1;	/* just do the first list */
	//if (WP_DEBUG) echo '<br /> List requested  ='.$l;
	$thiscache = new adb_cache();  // nlr?

	amr_list_user_admin_headings($l);	// will only do if in_admin


	echo ausers_form_start();
	
	if (empty($_REQUEST['filtering']) and (empty($_REQUEST['headings']))) 
		ausers_bulk_actions();	// will check capabilities

	echo alist_one('user',$l);  /* list the user list with the explanatory headings */

	if (empty($_REQUEST['filtering']) and (empty($_REQUEST['headings']))) 
		ausers_bulk_actions(); // will check capabilities
	
	if (function_exists('amr_ym_bulk_update_form') and amr_is_ym_in_list ($l)) // only show form if we have a ym field
		amr_ym_bulk_update_form();
		
	echo ausers_form_end();

	return;
}
/* ----------------------------------------------------------------------------------- */
?>
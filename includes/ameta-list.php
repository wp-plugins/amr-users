<?php 
/* -------------------------------------------------------------------------------------------------------------*/
function amr_list_user_headings($l){

global $amain;
global $ausersadminurl;

if ( !is_admin() ) return;
echo '<div class="wrap"><div id="icon-users" class="icon32"><br /></div><h2>';
echo $amain['names'][$l]; 
echo '</h2><div class="filter" >'.
	'<ul class="subsubsub" style="float:left; white-space:normal;">';

		$t = __('CSV Export','amr-users');
		$n = $amain['names'][$l];
		if (current_user_can('list_users') or current_user_can('edit_users')) {
			echo '<li style="display:block; float:left;">'
				.au_csv_link($t, $l, $n.__(' - Standard CSV.','amr-users')).'</li>';
			echo '<li style="display:block; float:left;"> |'.au_csv_link(__('Txt Export','amr-users'),
						$l.'&amp;csvfiltered',
						$n.__('- a .txt file, with CR/LF filtered out, html stripped, tab delimiters, no quotes ','amr-users')).'</li>';
			}
		if (current_user_can('manage_options')) {	
			echo '<li style="display:block; float:left;"> | <a style="color:#D54E21;" href="'.$ausersadminurl.'">'.__('Main Settings','amr-users').'</a></li>';
			echo '<li style="display:block; float:left;"> | '
			.au_configure_link(__('Configure this list','amr-users'), $l,$n).'</li>';
		}	
		echo '<li style="display:block; float:left;"> | '
			.au_buildcache_link(__('Rebuild cache now','amr-users'),$l,$n)
			.'</li>';
		echo '</ul>
</div> <!-- end of filter-->
<div class="clear"></div>
</div>';

}
/* -------------------------------------------------------------------------------------------------------------*/
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
function amr_convert_mem($size) {
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),4).' '.$unit[$i];
 }
/* -------------------------------------------------------------------------------------------------------------*/
function track_progress($text) {
global $time_start;
global $cache;
	//**** return;
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
	$t = 'At '.number_format($diff,3). ' seconds,  peak mem= '.number_format($mem,1) .' - '.$text;
	
	if (WP_DEBUG or isset($_REQUEST['mem'])) { echo '<br />'.$t;
		error_log($t);  //debug only
		}
	if (!empty ($cache)) $cache->log_cache_event($t);
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
	   (isset ($l['sortby'][$field])) 
	)
	return true;
	else 
	return false;
}
/* -------------------------------------------------------------------------------------------------------------*/
function amr_build_cache_for_one($ulist) {

	/* Get the fields to use for the chosen list type */

global $aopt;
global $amain;
global $wp_post_types;
global $time_start;
global $cache;
	$network = ausers_job_prefix();
	track_progress('In Build cache Network='.$network);

	register_shutdown_function('amr_shutdown');
	set_time_limit(200);
	$time_start = microtime(true);
	/* set up a report id so we know it is a userlist and which one*/
	if ($ulist < 10) $rptid = 'user-0'.$ulist;
	else $rptid = 'user-'.$ulist;
	
	ameta_options();  
	
	$l = $aopt['list'][$ulist]; /* *get the config */
		
	$date_format = get_option('date_format');
	$time_format = get_option('time_format');
	
	$option = array();
	$post_types=get_post_types();
	add_filter('pub_priv_sql_capability', 'amr_allow_count');// checked by the get_posts_by_author_sql
	$cache = new adb_cache();	

		/* now record the cache attempt  */
		$r = $cache->clear_cache($rptid);
//		If (!($r)) echo '<br />Cache does not exist or not cleared for '.$rptid;
		$r = $cache->record_cache_start($rptid, $amain['names'][$ulist]);
		If (!($r)) echo '<br />Cache start not recorded '.$rptid;
		$cache->log_cache_event(sprintf(__('Started cacheing report %s','amr-users'),$rptid));		
		track_progress('before get all user');
		$list = amr_get_alluserdata($ulist); /* keyed by user id */		
		track_progress('after get all user');
		
		/* get the extra count data */
		if (amr_need_the_field($ulist,'comment_count')) 
			$c = get_commentnumbers_by_author();
		else $c= array();	
	
		track_progress('after get comments check');
		if (!empty($list)) foreach ($list as $iu => $u) {
		// do the comments
			if (isset ($c[$u['ID']])) {
				$list[$iu]['comment_count'] = $c[$u['ID']]; /*** would like to cope with situation of no userid */
				}
		// do the post counts		
			foreach ( $post_types as $post_type ) {		
				if (amr_need_the_field($ulist,$post_type.'_count')) {
					
					$list[$iu][$post_type.'_count'] = amr_count_user_posts($u['ID'], $post_type);
//					if ()WP_DEBUG) echo '<br />**'.$post_type.' '.$list[$iu][$post_type.'_count'];
//					$list[$iu]['post_count'] = get_usernumposts($u['ID']); /* wordpress function */
					if ($list[$iu][$post_type.'_count'] == 0) unset($list[$iu][$post_type.'_count']);
				}
				
			}
			if (amr_need_the_field($ulist,'first_role')) {

				$user_object = new WP_User($u['ID']);

				if (!empty($user_object->roles)) $list[$iu]['first_role'] = amr_which_role($user_object); 

				if (empty ($list[$iu]['first_role'] )) unset($list[$iu]['first_role']);
			}
		}
		track_progress('after post types and roles:');
		unset($c);
		$total = count($list);
		$head = '';
		$tablecaption = '';

		if ($total > 0) {	
			if (isset ($l['selected']) and (count($l['selected']) > 0))  {
				$sel = ($l['selected']);						
				asort ($sel); /* get the selected fields in the display  order requested */
				foreach ($sel as $s2=>$sv) { 
					if ($sv > 0) $s[$s2] = $sv; 
				}
				$head .= '<div class="wrap" style ="clear: both; text-align: center; font-size:largest;"><strong>'
				.$amain['names'][$ulist].'</strong>'; 
				/* to look like wordpress */
				$tablecaption .= '<caption> '.$amain['names'][$ulist].'</caption>';
				$head .= '<ul class="report_explanation" style="list-style-type:none;">';
		/* check for filtering */

				if (isset ($l['excluded']) and (count($l['excluded']) > 0)) {/* do headings */
					$head .= '<li><em>'.__('Excluding where:','amr-users').'</em> ';
					foreach ($l['excluded'] as $k=>$ex) { 
						$head .= ' '.agetnice($k).'='.implode(__(' or ','amr-users'),$ex).',';
						foreach ($list as $iu=>$user) { 
							if (isset ($user[$k])) { /* then we need to check the values and exclude the whole user if necessary  */
								if (in_array($user[$k], $ex)) {
									unset ($list[$iu]);
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
						$head .= ' '.agetnice($k).'='.implode(__(' or ','amr-users'),$in).',';
						if (!empty($list)) foreach ($list as $iu => $user) { /* for each user */			
							if (isset ($user[$k])) {/* then we need to check the values and include the  user if a match */
								if (!(in_array($user[$k], $in))) {
									unset ($list[$iu]);
								}	
							}
						}	
					}
					$head = rtrim($head,',');
					$head .='</li>';
				}
			
				if (isset ($l['sortby']) and (count($l['sortby']) > 0)) { 
					$head .= '<li class="sort"><em>'.__(' Cache sorted by: ','amr-users').'</em>';			/* class used to replace in the front end sort info */
					asort ($l['sortby']); 
					$cols= array();
					foreach ($l['sortby'] as $sbyi => $sbyv) {
						if (isset($l['sortdir'][$sbyi])) 
							$cols[$sbyi] = array(SORT_DESC);
						else 
							$cols[$sbyi] =  array(SORT_ASC);
						$head .= agetnice($sbyi).',';
					}
					$head = rtrim($head,',');
					$head .='</li>';
					$list = auser_msort($list, $cols );
					
				}
				unset($cols);
				track_progress('after sorting');
				
				$tot = count($list);
				if ($tot === $total) 
					$text = sprintf(__('All %1s Users selected.', 'amr-users'), $total);
				else 
					$text = sprintf( __('%1s Users selected from total of %2s', 'amr-users'),$tot, $total);
				$head .=  '<li class="selected">'.$text.'</li></ul></div>';					
				$html = $head; 		
				
				$count = 0;		
				
				if ($tot > 0) { 

				/* get the col headings */
					$line[0] = 'ID';
					$iline[0] = 'ID';
					foreach ($s as $is => $v) { 
						$colno = (int) $v;
						if (!empty($iline[$colno])) $iline[$colno] .= $is; else $iline[$colno] = $is;
						$value = agetnice($is); 
//						if (!empty($value)) {
//							if (!empty($l['before'][$is])) 	$value = $l['before'][$is].$value;
//							if (!empty($l['after'][$is])) 	$value = $value.$l['after'][$is];
//						}
						if (!empty($line[$colno])) $line[$colno] = $line[$colno].'&nbsp;'.$value; else $line[$colno] = $value;
						}
					foreach ($line as $jj => $kk) {
							if (empty($kk)) $line[$jj] = '""'; /* there is no value */
							else $line[$jj] = '"'.$kk.'"'; /* Note for csv any quote must be doubleqouoted */
							//else $line[$jj] = '"'.str_replace('"','""',$kk).'"'; /* Note for csv any quote must be doubleqouoted */
						}						
						
				/* cache the col headings */
					$csv = implode (",", $iline);	unset($iline);
					$cache->cache_report_line($rptid,0,$csv); /* cache the internal column headings */		
					$csv = implode (",", $line);	unset($line);
					$cache->cache_report_line($rptid,1,$csv); /* cache the column headings */	

					track_progress('before cacheing lines');
					$count = 1;
					if (!empty($list)) foreach ($list as $j => $u) {	
						$count  = $count +1;
						$line[0] = $u['ID']; /* should be the user id */
						foreach ($s as $is => $v) {  /* defines the column order */
							$colno = (int) $v; 
							if (!(isset($u[$is])))	$value = ''; /* there is no value */
							else $value =  $u[$is];
							if (!empty($value)) {
								if (!empty($l['before'][$is])) 	$value = $l['before'][$is].$value;
								if (!empty($l['after'][$is])) 	$value = $value.$l['after'][$is];
							}
							if (!empty($line[$colno])) 	$line[$colno] .= $value;
							else 						$line[$colno] = $value;	
						}	
						foreach ($line as $jj => $kk) {
							if (empty($kk)) $line[$jj] = '""'; /* there is no value */
//							else $line[$jj] = '"'.str_replace('"','""',$kk).'"'; /* Note for csv any quote must be doubleqouoted  - BUT NOT YET */
							else $line[$jj] = '"'.$kk.'"'; 
						}
						$csv = implode (",", $line); 
						unset($line); 
						$cache->cache_report_line($rptid, $count, $csv);
						unset($csv);	
					}
					unset($list);


				}
				else $html .= sprintf( __('No users found for list %s', 'amr-users'), $ulist);				
			}
			else $html .=  '<h2 style="clear:both; ">'.sprintf( __('No fields chosen for display in settings for list %s', 'amr-users'), $ulist).'</h2>';
		}
		else $html .= __('No users in database! - que pasar?', 'amr-users');
		unset($s);
		track_progress('nearing end');
		$cache->record_cache_end($rptid, $count-1);
		$cache->record_cache_peakmem($rptid);
		$cache->record_cache_headings($rptid,$html);

		$time_end = microtime(true);
		$time = $time_end - $time_start;
		
		$cache->log_cache_event('<em>'
		.sprintf(__('Completed %s in %s microseconds', 'amr-users'),$rptid, number_format($time,2))
		.'</em>');		
		/* Echo some information */

/*		$result = '<h3>'.sprintf(__('Cache results for %s', 'amr-users'),$cache->name).'</h3>'
			.'<ul><li>'.__('Lines cached: ','amr-users').$cache->lines.'</li>'
			.'<li>'.__('Peak memory: ','amr-users').$cache->peakmem.'</li>'
			.'<li>'.__('Execution time in seconds: ').number_format($cache->timetaken,4).'</li>'
			.'</ul>'
			.au_view_link(__('View the list','amr-users'), $i, $rptid); */
		$result = '';	
		
		if (!empty($amain['public'][$ulist])) { // returns url if set to go to file
			$csvurl = amr_generate_csv($ulist, true, false,'csv','"',',',chr(13).chr(10), true );
		}
		
		
		return ($result);
}
/* -------------------------------------------------------------------------------------------------------------*/
function alist_searchform ($i) {
global $amain;
	if (!is_rtl()) $style= ' style="float:right;" ';
	else $style= '';

	$text = '';
	$text .= '<div class="search-box" '.$style.'>'
//	.'<input type="hidden"  name="page" value="ameta-list.php"/>'
	.'<input type="hidden"  name="ulist" value="'.$i.'"/>';
//	echo '<label class="screen-reader-text" for="post-search-input">'.__('Search Users').'</label>';
	$text .= '<input type="text" id="search-input" name="su" value=""/>
	<input type="submit" name="search" id="search-submit" class="button" value="'.__('Search Users').'"/>';
	$text .= '</div><div style="clear:both;"><br /></div>';
//	$text .= '</form>';
	return ($text);
}	
/* -------------------------------------------------------------------------------------------------------------*/
function alist_per_pageform ($i) {
global $amain;

	$rowsperpage = amr_rows_per_page($amain['rows_per_page']);  // will check for request

	$text = '';
	$text .= '<p class="perpage-box" style="text-align: center; margin: auto;">'
	.'<input type="hidden"  name="ulist" value="'.$i.'"/>';

	$text .= '<label for="rows_per_page">'.__('Per page');
	$text .= '<input type="text" name="rows_per_page" id="rows_per_page" size="3" value="'.$rowsperpage.'">';
	$text .= '</label>';
	$text .= '<input type="submit" name="refresh" id="perpage-submit" class="button" value="'.__('Apply').'"/>';
	$text .= '</p>';
//	$text .= '</form>';
	return ($text);
}		
/* -------------------------------------------------------------------------------------------------------------*/
function amr_try_build_cache_now ($c, $i, $rptid) { // the cache object, the report id, the list number 
global $amain;
		if ($c->cache_in_progress($rptid)) {
			echo '<div style="clear:both;"><strong>'.$amain['names'][$i].' ('.$rptid.') '.$c->get_error('inprogress').'</strong>';
			return (false);
		}
		else {
			echo '<div class="hidelater" style="clear:both;"><strong>'
			.$amain['names'][$i].' ('.$rptid.') '
			.__('Refresh of cache requested. Be patient.').'</strong>';
			flush();
			if (is_admin()) 
				amr_rebuild_in_realtime_with_info($i);
			else 
				amr_build_cache_for_one($i);
			echo '</div>';	

			return true;
		}	
}
/* -------------------------------------------------------------------------------------------------------------*/
function alist_one($type='user', $ulist=1, $options ) {

//options  can be headings, csv, show_search, show_perpage
	/* Get the fields to use for the chosen list type */
global $aopt;
global $amain;
global $amr_current_list; // needed for formatting

	$amr_current_list = $ulist;
	$headings = '';
	$caption = '';
	$l = $aopt['list'][$ulist]; /* *get the config */
	
	$c = new adb_cache();
	$rptid = $c->reportid($ulist, $type);
	$rowsperpage = amr_rows_per_page($amain['rows_per_page']); // will check request
	if (!empty ($_REQUEST['listpage'])) 
		$page = (int) $_REQUEST['listpage'];
	else 
		$page=1;
			
	if (!empty($_REQUEST['su'])) 
		$search = filter_var ($_REQUEST['su'], FILTER_SANITIZE_STRING );
//		
	//if (isset($_GET['refresh']))
	if ((!($c->cache_exists($rptid))) or (isset($_GET['refresh'])) )  {			
			$success = amr_try_build_cache_now ($c, $ulist, $rptid) ;
	}
	else $success = true;

	if ($success) {  
		if (isset($amain['sortable'][$ulist])) 
			$sortable = $amain['sortable'][$ulist];
		else 
			$sortable = false;

		$line = $c->get_cache_report_lines ($rptid, '0', '2'); /* get the internal heading names  for internal plugin use only */  /* get the user defined heading names */
				
		if (!defined('str_getcsv')) 
			$icols = amr_str_getcsv( ($line[0]['csvcontent']), ',','"','\\');
		else 
			$icols = str_getcsv( $line[0]['csvcontent'], ',','"','\\');
		if (!defined('str_getcsv')) 
			$cols = amr_str_getcsv( $line[1]['csvcontent'], '","','"','\\');
		else 
			$cols = str_getcsv( $line[1]['csvcontent'], ',','"','\\');

			$html = $hhtml = $fhtml = '';	

		$totalitems = $c->get_cache_totallines($rptid);
		if ($totalitems < 1) {	
			_e('No lines found','amr-users');
			return;
		}

		if ($rowsperpage > $totalitems)  
			$rowsperpage  = $totalitems;
			
		$lastpage = ceil($totalitems / $rowsperpage);
		if ($page > $lastpage) 
			$page = $lastpage;
		
		if ($page === 1) 
			$start = 1;
		else 
			$start = 1 + (($page - 1) * $rowsperpage);
		
//------------------------------------------------------------------------------------------		
		if (!empty($_REQUEST['sort']) or (!empty($search))) {
		/* then we want to sort, so have to fetch ALL the lines first and THEN sort.  Keep page number in case workingthrough the list  ! */	
		// if searching also want all the lines first so can search within and do pagination correctly  
			$lines = amr_get_lines_to_array ($c, $rptid, 2, $totalitems+1 , $icols /* the controlling array */) ;
			if ($lines) {
				$lines = amr_check_for_sort_request ($lines);
				$lines = array_values($lines); /* reindex as our indexing is stuffed and splice will not work properly */
				if (!empty($search)) $totalitems = count($lines);	//save total here before splice	
				$linessaved = array_splice($lines, $start-1, $rowsperpage );
			}
			else {
				$totalitems = 0;
				$linessaved = array();
			}

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
		
//------------------------------------------------------------------------------------------			
		elseif (isset($_REQUEST['filter'])) {  // then we are filteringecho '<br />Check for filtering'; var_dump($icols);	
				foreach ($icols as $cindex => $col) {
					if (isset ($_REQUEST[$col]) ) {
						$filtercol[$col] = esc_attr($_REQUEST[$col]);
//						if (WP_DEBUG) 
						$caption =  '<h3>'.$cols[$cindex].' : '.$filtercol[$col].'</h3>';
					}
				}
				if (empty($filtercol)) {
					echo '<p>';
					_e('No valid filter column given.','amr_users');
					echo '<br />';
					_e('Usage is :','amr_users');
					echo '<br /><strong>?filter=hide&column_name=value<br />';
					echo '?filter=show&column_name=value</strong></br> ';
					printf(__('Expecting column_name to be one of : %s','amr_users'),implode(', ',$icols));
					echo '</p>';
				}

				$lines = amr_get_lines_to_array ($c, $rptid, 2, $totalitems+1 , $icols /* the controlling array */) ;
			
				if (!empty($filtercol)) foreach ($filtercol as $fcol => $value) {					
					foreach ($lines as $i=> $line) {						
						if (!($line[$fcol] === $value)) {							
							unset ($lines[$i]);
						}
						else 
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
				}
										
						
	
				$linessaved = $lines; unset($lines);

			
//------------------------------------------------------------------------------------------					
			}  //end if 		
		else {
			$linessaved = amr_get_lines_to_array($c, $rptid, $start+1, $rowsperpage, $icols );				
		}
		
	
		if (empty($linessaved) or count($linessaved) < 1) {	
			return(__('No users found','amr-users')) ;
		} 
		
		if (!empty($search)) {  
				$searchselectnow = sprintf(
					__('%s Users found.','amr-users')
					,$totalitems);
				$searchselectnow .=	sprintf(
					__('Searching for "%s" in list','amr-users'),
					$search);
				}  // reset count if searching		
		unset($lines); // free up memory?		
		if ($icols[0] = 'ID') {  /* we only saved the ID so that we can access extra info on display - we don't want to always display it */
			unset ($icols[0]);unset ($cols[0]);
		} 

		if (!empty($options['show_headings'])) {
//		if ($do_headings) {
			if (is_admin()) $headings = $c->get_cache_headings ($rptid) ;
			if (!empty($sortedbynow)) 
				$headings = str_replace ('<li class="sort">',$sortedbynow, $headings  ) ;
			if (!empty($searchselectnow)) {
				$headings = str_replace ('<li class="selected">',
				'<li class="searched">'.$searchselectnow.'</li><li class="selected">',$headings);		
			}			
//		
		if (is_admin() and current_user_can('remove_users')) {
				array_unshift($icols, 'checkbox'); 
				array_unshift($cols, '<input type="checkbox">');
				foreach ($linessaved as $il =>$line) { 
					if (!empty($line['ID']))
						$linessaved[$il]['checkbox'] = 
						'<input class="check-column" type="checkbox" value="'.$line['ID'].'" name="users[]">';
					else 
						$linessaved[$il]['checkbox'] = '&nbsp;';
				}
			}				
			//$sortedbynow is set if maually resorted

			foreach ($icols as $ic => $cv) { /* use the icols as our controlling array, so that we have the internal field names */
					if ($sortable) $v = amr_make_sortable($cv,$cols[$ic]);  
					else $v = $cols[$ic];
					if ($cv === 'comment_count') 
						$v 	.= '<a title="'.__('Explanation of comment total functionality','amr-users')
							.'"href="http://wpusersplugin.com/1822/comment-totals-by-authors/">**</a>';
					//$v .= amr_indicate_sort_priority ($cv, 
					//	(empty($l['sortby'][$cv])? null : $l['sortby'][$cv]));
					$html 	.= '<th>'.$v.'</th>';
			}
			$hhtml = '<thead><tr>'.$html.'</tr></thead>'; /* setup the html for the table headings */	
			$fhtml = '<tfoot><tr>'.$html.'</tr>'
					.'<tr class="credits"><th colspan="'.count($icols).'">'
		.amr_users_give_credit()
		.'</th></tr>'
			.'</tfoot>'; /* setup the html for the table headings */	
			$html = '';
			
		}	

	
		if (empty($linessaved) or count($linessaved) < 1) {	
			return(__('No users found','amr-users')) ;
		}
		
		foreach ($linessaved as $il =>$line) {	
			$id = $line['ID']; /*   always have the id - may not always print it  */
			$user = amr_get_userdata($id);  
			$linehtml = '';
			foreach ($icols as $ic => $c) { 		
			
//				if (!empty($line[$c]))   
// do not check emptiness here - we m,ay want to format even if empty - we may have a way....
					$w = amr_format_user_cell($c, $line[$c], $user);
//				else 
//					$w = '&nbsp;';
				$linehtml .= '<td>'.$w. '</td>';
			}
			$html .=  AMR_NL.'<tr>'.$linehtml.'</tr>';	
		}
//		
		if (!empty($options['show_search']) )
			$sformtext = alist_searchform($ulist);
		else 
			$sformtext = '';
//		
		if (!empty($options['show_csv']) ) { 
			$csvtext = amr_users_show_csv_link($ulist);
			}
		else 
			$csvtext = '';
//
//		
		if (!empty($options['show_csv']) ) { 
			$refreshtext = amr_users_show_refresh_link($ulist);
			}
		else 
			$refreshtext = '';
//	
		if (!empty($options['show_perpage'])) 	
			$pformtext = alist_per_pageform($ulist);
		else 	
			$pformtext = '';
			
		$pagetext = amr_pagetext($page, $totalitems, $rowsperpage);
//		$html = '<div class="wrap" style="clear:both;">'
		if (is_admin()) 
			$class="widefat"; 
		else $class='';
		$html = $headings.'<div class="wrap" >'
		.$sformtext

		.$pagetext		
		.'<table id="userlist'.$ulist.'" class="userlist '.$class.'">'.$caption.$hhtml.$fhtml.'<tbody>'.$html.'</tbody></table>'
		.$pagetext
		.$csvtext
		.$refreshtext
		.$pformtext


		.'</div>';
	return ($html);					
	}
}
/* --------------------------------------------------------------------------------------------*/	
function amr_indicate_sort_priority ($colname, $orig_sort) {
	if ((!empty($_REQUEST['sort'])) and ($_REQUEST['sort'] === $colname)) {
		return (' <a style="color: green;" href="" title="'
		.sprintf(
			_x('Sorted 1%s','Indicates sort priority',  'amr-users' )
			,'1')
		.'">&uarr&darr</a>' )	;	

	}
	
	if (!empty($orig_sort)) { 
		return(' <a style="color: green;" href="" title="'
		.sprintf(
			_x('Sorted %s','Indicates sort priority',  'amr-users' )
			,$orig_sort)
		.'">&uarr;&darr;</a>' )	;

	}
	return '';
}
/* --------------------------------------------------------------------------------------------*/	
function amr_get_lines_to_array ($c, $rptid, $start, $rows, $icols /* the controlling array */) {	

	if (!empty($_REQUEST['su'])) {		// check for search request	

		$s = filter_var ($_REQUEST['su'], FILTER_SANITIZE_STRING );
		$lines = $c->search_cache_report_lines ($rptid, $rows, $s);
	}
	else {
		$lines = $c->get_cache_report_lines ($rptid, $start, $rows );
	}
				
	if (!($lines>0)) {amr_flag_error($c->get_error('numoflists'));	return (false);	}
	foreach ($lines as $il =>$l) {
		if (!defined('str_getcsv')) 
			$lineitems = amr_str_getcsv( ($l['csvcontent']), '","','"','\\'); /* break the line into the cells */
		else 
			$lineitems = str_getcsv( $l['csvcontent'], ',','"','\\'); /* break the line into the cells */

		$linehtml = '';
		foreach ($icols as $ic => $c) { /* use the icols as our controlling array, so that we have the internal field names */
			if (isset($lineitems[$ic])) {
				$w = $lineitems[$ic];  
			}
			else $w = '&nbsp;';
			$linessaved[$il][$c] = stripslashes($w); 
		}
	}
	unset($lines);
	return ($linessaved);
}
/* --------------------------------------------------------------------------------------------*/	
function amr_make_sortable($colname, $colhead) { /* adds a link to the column headings so that one can resort against the cache */
	$dir = 'SORT_ASC';	

	if ((!empty($_REQUEST['sort'])) and ($_REQUEST['sort'] === $colname)) {
		if (!empty($_REQUEST['dir'])) {
			if ($_REQUEST['dir'] === 'SORT_ASC' ) 
				$dir = 'SORT_DESC';
			else 	
				$dir = 'SORT_ASC';
			
		}		
	}
	$link = remove_query_arg(array('refresh'));
	$link = add_query_arg('sort', $colname, $link);
	$link = add_query_arg('dir',$dir,$link);
	if (!empty($_REQUEST['rows_per_page'])) 
	$link = add_query_arg('rows_per_page',(int) $_REQUEST['rows_per_page'],$link);	
	return('<a title="'.
	__('Click to sort.  Click again to change direction.','amr-users')
	.'" href="'.htmlentities($link).'">'.$colhead.'</a>');
}
/* --------------------------------------------------------------------------------------------*/	
function amr_check_for_sort_request ($list, $cols=null) {
/* check for any sort request and then sort our cache by those requests */
	$dir=SORT_ASC;	
	if ((!empty($_REQUEST['dir'])) and ($_REQUEST['dir'] === 'SORT_DESC' ))  $dir=SORT_DESC;
	if (!empty($_REQUEST['sort'])) {
		$cols = array($_REQUEST['sort'] => array($dir), 'ID' => array($dir) );  		
		$list = auser_msort($list, $cols );
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
			$html .=  AMR_NL.'<tr>'.$linehtml.'</tr>';			
		}

//		$html = '<div class="wrap" style="clear:both;">'
		$html = '<table>'.$hhtml.$fhtml.'<tbody>'.$html.'</tbody></table>';

	return ($html);	
}	
/* --------------------------------------------------------------------------------------------*/	
function amr_generate_csv($ulist,$strip_endings, $strip_html = false, $suffix, $wrapper, $delimiter, $nextrow, $tofile=false) {
	
/* get the whole cached file - write to file? but security / privacy ? */
/* how big */
	
	$c = new adb_cache();
	$rptid = $c->reportid($ulist);
	$total = $c->get_cache_totallines ($rptid );
	$lines = $c->get_cache_report_lines($rptid,1,$total+1); /* we want the heading line (line1), but not the internal nameslines (line 0) , plus all the data lines, so neeed total + 1 */
	if (isset($lines) and is_array($lines)) $t = count($lines);
	else $t = 0;
	$csv = '';
	if ($t > 0) {
		if ($strip_endings) {
			foreach ($lines as $k => $line) 
				$csv .= apply_filters( 'amr_users_csv_line', $line['csvcontent'] ).$nextrow;
		}
		else {
			foreach ($lines as $k => $line) 
			$csv .= $line['csvcontent'].$nextrow;

			}
		$csv = str_replace ('","', $wrapper.$delimiter.$wrapper, $csv);	/* we already have in std csv - allow for other formats */
		$csv = str_replace ($nextrow.'"', $nextrow.$wrapper, $csv);
		$csv = str_replace ('"'.$nextrow, $wrapper.$nextrow, $csv);
		if ($csv[0] == '"') $csv[0] = $wrapper; 
	}
	echo '<br /><h3>'.$c->reportname($ulist).'</h3>'
	.'<h4>'.sprintf(__('%s lines found, 1 heading line, the rest data.','amr-users'),$t).'</h4><br />';
	
	if ($tofile) {
		$csvfile = amr_users_to_csv($ulist, $csv, $suffix);
		//$csvurl = amr_users_get_csv_url($csvfile);
		echo amr_users_show_csv_link($ulist);
		//return ($csvurl);
		//echo '<br />'.__('Public user list csv file: ','amr-users' )	.'<a href="'.$csvurl.'">'.$rptid.'<a/>';
	}
	else {
		echo amr_csv_form($csv, $suffix);
	}
}
/* --------------------------------------------------------------------------------------------*/				
function amr_list_user_meta(){   /* Echos out the paginated version of the requested list */
global $aopt;
global $amain;
global $amr_nicenames;
global $thiscache;

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
			echo '<br />what is happening ?';
			var_dump($_REQUEST);	
			}
	}
	if ($l < 1) $l = 1;	/* just do the first list */
	//if (WP_DEBUG) echo '<br /> List requested  ='.$l;
	$thiscache = new adb_cache();
	amr_list_user_headings($l);	
	$options = array (
			'show_headings'=>true, 
			'show_csv'=>true,
			'show_search' => true,
			'show_perpage' => true		
			);	

		echo ausers_form_start();	
		wp_nonce_field();
		ausers_bulk_actions();	// will check capabilities
		echo alist_one('user',$l, $options);  /* list the user list with the explanatory headings */
		ausers_bulk_actions(); // will check capabilities
		echo ausers_form_end();
		
	return;
}
/* ----------------------------------------------------------------------------------- */
function ausers_form_end() {
	echo '</form>';
}
/* ----------------------------------------------------------------------------------- */
function ausers_form_start() {
	$base = remove_query_arg(array('refresh', 'listpage'));
	if (!empty($_REQUEST['rows_per_page'])) 
	$base = add_query_arg('rows_per_page',(int) $_REQUEST['rows_per_page'],$base);
	return ('<form  action="'.$base.'" method="post">');
	
}	
/* ----------------------------------------------------------------------------------- */
function amr_to_csv ($csv, $suffix) {
/* create a csv file for download */
	if (!isset($suffix)) $suffix = 'csv';
	$file = 'userlist-'.date('YmdHis').'.'.$suffix;
	header("Content-Description: File Transfer");
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=$file");
	header("Pragma: no-cache");
	header("Expires: 0");
	echo $csv;
	exit(0);   /* Terminate the current script sucessfully */	
}
/* -------------------------------------------------------------------------------------------------------------*/
	if (( isset ($_POST['csv']) ) and (isset($_POST['reqcsv']))) {	
	/* since data passed by the form, a security check here is unnecessary, since it will just create headers for whatever is passed .*/
		if ((isset ($_POST['suffix'])) and ($_POST['suffix'] == 'txt')) $suffix = 'txt';
		else $suffix = 'csv';
		amr_to_csv (htmlspecialchars_decode($_POST['csv']),$suffix); 
/*		amr_to_csv (html_entity_decode($_POST['csv'])); */
	}

?>
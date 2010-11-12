<?php 
/* -------------------------------------------------------------------------------------------------------------*/

function amr_list_user_headings($l){

global $amain;

if ( !is_admin() ) return;
?>
<div class="wrap"><div id="icon-users" class="icon32"><br /></div><h2><?php echo $amain['names'][$l]; ?></h2>
	<div class="filter" ><?php
	?>
	<ul class="subsubsub" style="float:left; white-space:normal;">
<?php 	
		$t = __('CSV Export','amr-users');
		$n = $amain['names'][$l];
		if (current_user_can('list_users') or current_user_can('edit_users')) {
			echo '<li style="display:block; float:left;">'
				.au_csv_link($t, $l, $n.__(' - Standard CSV with as is wp.','amr-users')).'</li>';
			echo '<li style="display:block; float:left;"> |'.au_csv_link(__('Txt Export','amr-users'),
						$l.'&amp;csvfiltered',
						$n.__('- a .txt file, with CR/LF filtered out, html stripped, tab delimiters, no quotes ','amr-users')).'</li>';
			}
		if (current_user_can('manage_options')) {	
			echo '<li style="display:block; float:left;"> | <a style="color:#D54E21;" href="options-general.php?page=ameta-admin.php">'.__('Main Settings','amr-users').'</a></li>';
			echo '<li style="display:block; float:left;"> | '
			.au_configure_link(__('Configure this list','amr-users'), $l,$n).'</li>';
		}	
		echo '<li style="display:block; float:left;"> | '
			.au_buildcache_link(__('Rebuild cache now','amr-users'),$l,$n)
			.'</li>';
		?>
</ul>
</div>
</div>
<?php
}


/* -------------------------------------------------------------------------------------------------------------*/

function get_commentnumbers_by_author(  ) {
     global $wpdb;
	 /*** Rudimentary - if going to be used frequently (eg outside of admin area , then could do with optimistaion / cacheing */

	$approved = "comment_approved = '1'";
	$comments = $wpdb->get_results( 
	"SELECT user_id, comment_author_email, count(1) as \"comment_count\" FROM $wpdb->comments WHERE $approved AND user_id > 0 GROUP BY user_id, comment_author_email;" );
	foreach ($comments as $i => $v) {
		$c[$v->user_id] = $v->comment_count;
	}
	unset ($comments);
     return $c;
}
/* -------------------------------------------------------------------------------------------------------------*/
function rows_per_page($rpp){
	if (!empty($_REQUEST['rows_per_page'])) return ((int) ($_REQUEST['rows_per_page']));
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
function track_progress($text) {
global $time_start;
global $cache;
	$diff = (time() - $time_start);
	$t = 'after '.$diff. ' peak mem '.memory_get_peak_usage(true) .' - '.$text;
	$cache->log_cache_event($t);
	echo '<br />'.$t;
	error_log($t);
	

}
/* -------------------------------------------------------------------------------------------------------------*/
function amr_build_cache_for_one($i) {

	/* Get the fields to use for the chosen list type */

global $aopt;
global $amain;
global $wp_post_types;
global $time_start;
global $cache;

	register_shutdown_function('amr_shutdown');
	set_time_limit(200);
	$time_start = microtime(true);
	/* set up a report id so we know it is a userlist and which one*/
	if ($i < 10) $rptid = 'user-0'.$i;
	else $rptid = 'user-'.$i;
	
	ameta_options();  
	
	$l = $aopt['list'][$i]; /* *get the config */
		
	$date_format = get_option('date_format');
	$time_format = get_option('time_format');
	$option = array();
	$post_types=get_post_types();
	add_filter('pub_priv_sql_capability', 'amr_allow_count');// checked by the get_posts_by_author_sql
	$cache = new adb_cache();	

		/* now record the cache attempt  */
		$r = $cache->clear_cache($rptid);
//		If (!($r)) echo '<br />Cache does not exist or not cleared for '.$rptid;
		$r = $cache->record_cache_start($rptid, $amain['names'][$i]);
		If (!($r)) echo '<br />Cache start not recorded '.$rptid;
		$cache->log_cache_event(sprintf(__('Started cacheing report %s','amr-users'),$rptid));		
		
		$list = amr_get_alluserdata(); /* keyed by user id */
		
		track_progress('after get all user');
		
		/* get the extra count data */
		if ((isset ($l['selected']['comment_count'])) or
		    (isset ($l['included']['comment_count']))) 
		$c = get_commentnumbers_by_author();
	
		track_progress('after get comments check');
		foreach ($list as $iu => $u) {
			if (isset ($c[$u['ID']])) {
				$list[$iu]['comment_count'] = $c[$u['ID']]; /*** would like to cope with situation of no userid */
				}
				
			foreach ( $post_types as $post_type ) {
			
				
				if ((isset ($l['selected'][$post_type.'_count'])) or
			    (isset ($l['included'][$post_type.'_count']))) {
					
					$list[$iu][$post_type.'_count'] = amr_count_user_posts($u['ID'], $post_type);
//					$list[$iu]['post_count'] = get_usernumposts($u['ID']); /* wordpress function */
					if ($list[$iu][$post_type.'_count'] == 0) unset($list[$iu][$post_type.'_count']);
				}
				
			}
			if ((isset ($l['selected']['first_role'])) or
		    (isset ($l['included']['first_role']))) {

				$user_object = new WP_User($u['ID']);

				if (!empty($user_object->roles)) $list[$iu]['first_role'] = amr_which_role($user_object); 

				if (empty ($list[$iu]['first_role'] )) unset($list[$iu]['first_role']);
			}
		}
		track_progress('after post types and roles:');
		$total = count($list);
		$head = '';
		$tablecaption = '';

		if (count($list) > 0) {	
			if (isset ($l['selected']) and (count($l['selected']) > 0))  {
				$sel = ($l['selected']);						
				asort ($sel); /* get the selected fields in the display  order requested */
				foreach ($sel as $s2=>$sv) { 
					if ($sv > 0) $s[$s2] = $sv; 
				}
				$head .= '<div class="wrap" style ="clear: both; text-align: center; font-size:largest;"><strong>'.$amain['names'][$i].'</strong>'; 
				/* to look like wordpress */
				$tablecaption .= '<caption> '.$amain['names'][$i].'</caption>';
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
						foreach ($list as $iu => $user) { /* for each user */			
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
						else $cols[$sbyi] =  array(SORT_ASC);
						$head .= agetnice($sbyi).',';
					}
					$head = rtrim($head,',');
					$head .='</li>';
					$list = auser_msort($list, $cols );
					
				}
				track_progress('after sorting');
				$tot = count($list);
				$head .=  '<li>'.sprintf( __('%1s Users selected from total of %2s', 'amr-users'),$tot, $total).'</li></ul></div>';					
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
					foreach ($list as $j => $u) {	
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
					}


				}
				else $html .= sprintf( __('No users found for list %s', 'amr-users'), $i);				
			}
			else $html .=  '<h2 style="clear:both; ">'.sprintf( __('No fields chosen for display in settings for list %s', 'amr-users'), $i).'</h2>';
		}
		else $html .= __('No users in database! - que pasar?', 'amr-users');
		
		track_progress('nearing end');
		$cache->record_cache_end($rptid, $count-1);
		$cache->record_cache_peakmem($rptid);
		$cache->record_cache_headings($rptid,$html);


		$time_end = microtime(true);
		$time = $time_end - $time_start;
		
		$cache->log_cache_event('<em>'.sprintf(__('Completed %s in %s microseconds', 'amr-users'),$rptid, number_format($time,2)).'</em>');		
		/* Echo some information */

/*		$result = '<h3>'.sprintf(__('Cache results for %s', 'amr-users'),$cache->name).'</h3>'
			.'<ul><li>'.__('Lines cached: ','amr-users').$cache->lines.'</li>'
			.'<li>'.__('Peak memory: ','amr-users').$cache->peakmem.'</li>'
			.'<li>'.__('Execution time in seconds: ').number_format($cache->timetaken,4).'</li>'
			.'</ul>'
			.au_view_link(__('View the list','amr-users'), $i, $rptid); */
		$result = '';	
		return ($result);
		}
/* -------------------------------------------------------------------------------------------------------------*/
if (!function_exists('amr_do_cell')) {
	function amr_do_cell($i, $k, $openbracket,$closebracket) {
		return ($openbracket.$i.$closebracket);
	}
}
/* -------------------------------------------------------------------------------------------------------------*/
function amr_format_user_cell($i, $v, $u) {
/* receive the key and the value and format accordingly - wordpress has a similar user function function - should we use that? */
	switch ($i) {
		case 'user_email': {
			return('<a href="mailto:'.$v.'">'.$v.'</a>');
			break;
		}
		case 'user_login': {
			if (is_object($u) and isset ($u->ID) ) return('<a href="'.WP_SITEURL.'/wp-admin/user-edit.php?user_id='.$u->ID.'">'.$v.'</a>');
			break;
			
		}
		case 'post_count': {
			if (empty($v)) return( ' ');
			else if (is_object($u) and isset ($u->ID) ) return('<a href="'.add_query_arg('author',$u->ID, get_bloginfo('siteurl')).'">'.$v.'</a>');
			break;
		}
		case 'user_url': {
			return('<a href="'.$v.'">'.$v.'</a>');
			break;
		}
		case 'comment_count': {  /* if they have wp stats plugin enabled */
			if ((empty($v)) or (!($stats_url = get_option('stats_url')))) return($v);
			else return( '<a href="'.add_query_arg('stats_author',$u->user_login, $stats_url).'">'.$v.'</a>');
			break;
		}
		case 'description': {  
			return((nl2br($v))); break;
		}
		default: {
			if (isset ($v)) return($v);
			else return(' ');
		}
	}
	return('');
}
/* -------------------------------------------------------------------------------------------------------------*/
function alist_one($type='user', $i=1, $do_headings, $do_csv=false){
	/* Get the fields to use for the chosen list type */
global $aopt;
global $amain;

	$headings = '';
	$caption = '';
	$c = new adb_cache();
	$rptid = $c->reportid($i, $type);
	$rowsperpage = rows_per_page($amain['rows_per_page']);
	if (!empty ($_REQUEST['listpage'])) $page = (int) $_REQUEST['listpage'];
	else $page=1;
	if (!($c->cache_exists($rptid)))  {
		if ($c->cache_in_progress($rptid)) 
			echo '<div style="clear:both;"><strong>'.$amain['names'][$i].' ('.$rptid.') '.$c->get_error('inprogress').'</strong>';
		else 
			echo '<div style="clear:both;"><strong>'.$amain['names'][$i].' ('.$rptid.') '.$c->get_error('nocache').'</strong>';
		$s = get_option('amr-users-cache-status');	
		return (false);
		}
	else {
		if (isset($amain['sortable'][$i])) $sortable = $amain['sortable'][$i];
		else $sortable = false;
		$line = $c->get_cache_report_lines ($rptid, '0', '2'); /* get the internal heading names  for internal plugin use only */  /* get the user defined heading names */
		if (!defined('str_getcsv')) $icols = amr_str_getcsv( ($line[0]['csvcontent']), ',','"','\\');
		else $icols = str_getcsv( $line[0]['csvcontent'], ',','"','\\');
		if (!defined('str_getcsv')) $cols = amr_str_getcsv( $line[1]['csvcontent'], '","','"','\\');
		else $cols = str_getcsv( $line[1]['csvcontent'], ',','"','\\');
		$html = $hhtml = $fhtml = '';	

		$totalitems = $c->get_cache_totallines($rptid);

		if ($rowsperpage > $totalitems)  $rowsperpage  = $totalitems;
		$lastpage = $totalitems / $rowsperpage;
		if ($page > $lastpage) $page = $lastpage;
		
		if ($page === 1) $start = 1;
		else $start = 1 + (($page - 1) * $rowsperpage);
		if (!empty($_REQUEST['sort'])) {/* then we want to sort, so have to fetch all the lines first and THEN sort.  Keep page number in case workingthrough the list  ! */	
			$lines = amr_get_lines_to_array ($c, $rptid, 2, $totalitems+1 , $icols /* the controlling array */) ;
			$lines = amr_check_for_sort_request ($lines);
			$lines = array_values($lines); /* reindex as our indexing is stuffed and splice will not work properly */
			$linessaved = array_splice($lines, $start-1, $rowsperpage );
			/* now fix the cache headings*/
			foreach ($icols as $i=>$t) { if ($t == $_REQUEST['sort']) $sortedbynow = strip_tags($cols[$i]) ;}
			$sortedbynow = '<li><em>'
				.__('Sorted by:','amr-users').'</em>'.$sortedbynow.'</li><li class="sort">';
		}		
		else 
			$linessaved = amr_get_lines_to_array($c, $rptid, $start+1, $rowsperpage, $icols );
		if ($icols[0] = 'ID') { unset ($icols[0]);unset ($cols[0]);}  /* we only saved the ID so that we can access extra info on display - we don't want to always display it */
		//$dump = array_shift ($icols); echo '<br />'. $dump; var_dump($icols);	
		if ($do_headings) {
			if (is_admin()) $headings = $c->get_cache_headings ($rptid) ;
			if (!empty($sortedbynow)) $headings = str_replace ('<li class="sort">',$sortedbynow, $headings  ) ;
			
			foreach ($icols as $ic => $cv) { /* use the icols as our controlling array, so that we have the internal field names */
					if ($sortable) $v = amr_make_sortable($cv,$cols[$ic]);  
					else $v = $cols[$ic];
					if ($cv === 'comment_count') 
						$v 	.= '<a title="'.__('Explanation of comment total functionality','amr-users')
							.'"href="http://webdesign.anmari.com/comment-totals-by-authors/">**</a>';
					$html 	.= '<th>'.$v.'</th>';
				}
			$hhtml = '<thead><tr>'.$html.'</tr></thead>'; /* setup the html for the table headings */	
			$fhtml = '<tfoot><tr>'.$html.'</tr></tfoot>'; /* setup the html for the table headings */	
			$html = '';
			
		}	
		foreach ($linessaved as $il =>$l) {	
			$id = $l['ID']; /*   always have the id - may not always print it  */
			$user = get_userdata($id);
			$linehtml = '';
			foreach ($icols as $ic => $c) {
				if (!empty($l[$c])) $w = amr_format_user_cell($c, $l[$c], $user);
				else $w = '&nbsp;';
				$linehtml .= '<td>'.$w. '</td>';
			}
			$html .=  AMR_NL.'<tr>'.$linehtml.'</tr>';	
		}
		$pagetext = amr_pagetext($page, $totalitems, $rowsperpage);
//		$html = '<div class="wrap" style="clear:both;">'
		if (is_admin()) $class="widefat"; else $class='';
		$html = $headings.'<div class="wrap" >'
		.$pagetext
		.'<table id="userlist'.$i.'" class="userlist '.$class.'">'.$caption.$hhtml.$fhtml.'<tbody>'.$html.'</tbody></table>'
		.$pagetext.'</div>';
	return ($html);					
	}
}
/* --------------------------------------------------------------------------------------------*/	
function amr_get_lines_to_array ($c, $rptid, $start, $rows, $icols /* the controlling array */) {	
	$lines = $c->get_cache_report_lines ($rptid, $start, $rows );
	if (!($lines>0)) {amr_flag_error($amr_errors->get_error('numoflists'));	return (false);	}
	foreach ($lines as $il =>$l) {
		if (!defined('str_getcsv')) $lineitems = amr_str_getcsv( ($l['csvcontent']), '","','"','\\'); /* break the line into the cells */
		else $lineitems = str_getcsv( $l['csvcontent'], ',','"','\\'); /* break the line into the cells */

		$linehtml = '';
		foreach ($icols as $ic => $c) { /* use the icols as our controlling array, so that we have the internal field names */
			if (isset($lineitems[$ic])) {
				$w = $lineitems[$ic];  
			}
			else $w = '&nbsp;';
			$linessaved[$il][$c] = stripslashes($w); 
		}
	}
	return ($linessaved);
}
/* --------------------------------------------------------------------------------------------*/	
function amr_make_sortable($colname, $colhead) { /* adds a link to the column headings so that one can resort against the cache */
	$dir = 'SORT_ASC';	
	if ((!empty($_REQUEST['sort'])) and ($_REQUEST['sort'] === $colname)) {
		if ((!empty($_REQUEST['dir'])) and ($_REQUEST['dir'] === 'SORT_ASC' )) {
			$dir = 'SORT_DESC';
			
		}
	}	
	$link = add_query_arg('sort',$colname);
	$link = add_query_arg('dir',$dir,$link);
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
		$cols = array($_REQUEST['sort'] => $dir );
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
		$fhtml = '<tfoot><tr>'.$html.'</tr></tfoot>'; /* setup the html for the table headings */	
		$html='';
		$totalitems = $c->get_cache_totallines($rptid);
		$lines = $c->get_cache_report_lines ($rptid, $start+1, $max );


		if (!($lines>0)) {
			amr_flag_error($amr_errors->get_error('numoflists'));
			return (false);
		}
		foreach ($lines as $il =>$l) {
		
			$id = $lineitems[0]; /*  *** pop the first one - this should always be the id */

			$user = get_userdata($id);
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
function amr_generate_csv($i,$strip_endings, $strip_html = false, $suffix, $wrapper, $delimiter, $nextrow) {
	
/* get the whole cached file - write to file? but security / privacy ? */
/* how big */

	$c = new adb_cache();
	$rptid = $c->reportid($i);
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
// THIS CODE DOES NOT WORK :
//				$linearray = explode (',', $line['csvcontent'] );
//				foreach ($line as $l => $value) {
//					$v = trim($value, '"'); var_dump($v); echo '<br/>';
//					if ($strip_endings) $v = preg_replace( '@\r\n@Usi', ' ', $v );
//					if ($strip_html)    $v = wp_kses($v, ''); /* not html allowed */
//					if ($suffix == 'csv') $v = str_replace('"', '""', $value); /* force the double quoting  */
//					$csvline[] = $wrapper . $v. $wrapper;
//				}
//				$csv .= implode ($delimiter, $csvline).$nextrow;
//				var_dump($csv);
//				$csvline = array();
			}
		$csv = str_replace ('","', $wrapper.$delimiter.$wrapper, $csv);	/* we already have in std csv - allow for other formats */
		$csv = str_replace ($nextrow.'"', $nextrow.$wrapper, $csv);
		$csv = str_replace ('"'.$nextrow, $wrapper.$nextrow, $csv);
		if ($csv[0] == '"') $csv[0] = $wrapper; 
	}
	echo '<br /><h3>'.$c->reportname($i).'</h3>'
	.'<h4>'.sprintf(__('%s lines found, 1 heading line, the rest data.','amr-users'),$t).'</h4><br />';
	
	echo amr_csv_form($csv, $suffix);
}		
						
/* --------------------------------------------------------------------------------------------*/				

function amr_list_user_meta(){   /* Echos out the paginated version of the requested list */
global $aopt;
global $amain;
global $amr_nicenames;
global $thiscache;

	ameta_options(); 
	if (isset ($aopt['list'])) {
		if (isset($_REQUEST['page']))  { /*  somehow needs to be ? instead of & in wordpress admin, so we don't get as separate  */
			$param = 'ulist=';
			$l = substr (stristr( $_REQUEST['page'], $param), strlen($param));
			}
		else $l = 1;	/* just do the first list */
		$thiscache = new adb_cache();
		amr_list_user_headings($l);			
		echo alist_one('user',$l, true, true);  /* list the user list with the explanatory headings */
		}
	else _e ("No lists Defined", 'amr-users');

	return;
}	
/* ----------------------------------------------------------------------------------- */

	if (( isset ($_POST['csv']) ) and (isset($_POST['reqcsv']))) {	
	/* since data passed by the form, a security check here is unnecessary, since it will just create headers for whatever is passed .*/
		if ((isset ($_POST['suffix'])) and ($_POST['suffix'] == 'txt')) $suffix = 'txt';
		else $suffix = 'csv';
		amr_to_csv (htmlspecialchars_decode($_POST['csv']),$suffix); 
/*		amr_to_csv (html_entity_decode($_POST['csv'])); */
	}

	
?>
<?php 

/* -------------------------------------------------------------------------------------------------------------*/

function amr_list_user_headings($l){

global $amain;

?>
<div class="wrap"><div id="icon-users" class="icon32"><br></div><h2><?php echo $amain['names'][$l]; ?></h2>
	<div class="filter" ><ul class="subsubsub" style="float:left;">
<?php	for ($i = 1; $i <= $amain['no-lists']; $i++)	{
			$n = &$amain['names'][$i];
			$t = sprintf(__('View cached list: %s', 'amr-users'),$n);
			unset ($cache);
			$cache = new adb_cache();
			$rid = $cache->reportid($i);
			echo '<li style="display:block; float:left;">';
			if ($l == $i) echo '<strong>'. au_view_link($n, $i, $t).'</strong>';
			else echo au_view_link($n, $i, $t);
			echo '('.$cache->get_cache_totallines($rid).')';
			echo ' | </li>';
			}

		if ( is_admin() and current_user_can('edit_users')) {
			$t = __('CSV Export','amr-users');
			echo '<li style="display:block; float:left;">'.au_csv_link($t, $l, $n).'</li>';
			echo '<li style="display:block; float:left;"> | <a style="color:#D54E21;" href="options-general.php?page=ameta-admin.php">'.__('Settings','amr-users').'</a></li>';
			echo '<li style="display:block; float:left;"> | '
			.au_configure_link(__('Configure this list','amr-users'), $l,$n).'</li>';
			echo '<li style="display:block; float:left;"> | '
			.au_buildcache_link(__('Rebuild cache now','amr-users'),$l,$n)
			.'</li>';
		}?>
</ul></div></div><?php
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
	if (!empty($_REQUEST['rows_per_page'])) return (int ($_REQUEST['rows_per_page']));
	else if (!empty($rpp)) return($rpp);
	else return(50);  
}	


/* -------------------------------------------------------------------------------------------------------------*/
function amr_build_cache_for_one($i) {
	/* Get the fields to use for the chosen list type */

global $aopt;
global $amain;

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

	$cache = new adb_cache();	

		/* now record the cache attempt  */
		$r = $cache->clear_cache($rptid);
//		If (!($r)) echo '<br />Cache does not exist or not cleared for '.$rptid;
		$r = $cache->record_cache_start($rptid, $amain['names'][$i]);
		If (!($r)) echo '<br />Cache start not recorded '.$rptid;
		$cache->log_cache_event(sprintf(__('Started cacheing %s','amr-users'),$rptid));		
		
		$list = amr_get_alluserdata(); /* keyed by user id */
		
		/* get the extra count data */
		if ((isset ($l['selected']['comment_count'])) or
		    (isset ($l['included']['comment_count']))) 
		$c = get_commentnumbers_by_author();

		
		foreach ($list as $iu => $u) {
			if (isset ($c[$u['ID']])) {
				$list[$iu]['comment_count'] = $c[$u['ID']]; /*** would like to cope with situation of no userid */
				}
			if ((isset ($l['selected']['post_count'])) or
		    (isset ($l['included']['post_count']))) {
				$list[$iu]['post_count'] = get_usernumposts($u['ID']); /* wordpress function */
				if ($list[$iu]['post_count'] == 0) unset($list[$iu]['post_count']);
			}
			if ((isset ($l['selected']['first_role'])) or
		    (isset ($l['included']['first_role']))) {

				$user_object = new WP_User($u['ID']);

				if (!empty($user_object->roles)) {
					$list[$iu]['first_role'] = amr_which_role($user_object); 
//var_dump($user_object );									
				}

				if (empty ($list[$iu]['first_role'] )) unset($list[$iu]['first_role']);
			}
		}
		$total = count($list);
		$head = '';
		$tablecaption = '';

		if (count($list) > 0) {	
			if (isset ($l['selected']) and (count($l['selected']) > 0))  {
				$sel = ($l['selected']);
				asort ($sel); /* get the selected fields in the display  order requested */
				foreach ($sel as $s2=>$sv) { if ($sv > 0) $s[$s2] = $sv; }

				$head .= '<div class="wrap" style ="text-align: center; font-size:largest;"><strong>'.$amain['names'][$i].'</strong>'; 
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
					$head .='</li>';
				}
			
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
					$head .='</li>';
				}
			
				if (isset ($l['sortby']) and (count($l['sortby']) > 0)) { 
					$head .= '<li><em>'.__(' Sorting by: ','amr-users').'</em>';			
					asort ($l['sortby']); 
					$cols= array();
					foreach ($l['sortby'] as $sbyi => $sbyv) {
						if (isset($l['sortdir'][$sbyi])) 
							$cols[$sbyi] = array(SORT_DESC);
						else $cols[$sbyi] =  array(SORT_ASC);
						$head .= agetnice($sbyi).',';
					}
					$head .='</li>';
					$list = auser_msort($list, $cols );
				}
				
				$tot = count($list);
				$head .=  '<li>'.sprintf( __('%1s Users selected from total of %2s', 'amr-users'),$tot, $total).'</li></ul>';					
				$html = $head; 
				
//				if (($do_headings) or is_admin()) $html .= $head;
//				$html .=  '<table class="widefat meta" style="margin: auto; width: auto">';		
//				if (!$do_headings) $html .= $tablecaption;						
				if ($tot > 0) { 

				/* get the col headings */
					$line[] = '"ID"';
					$iline[] = 'ID';
					foreach ($s as $is => $v) { 
						$iline[] = $is;
						$line[] = '"'.str_replace('"','""',agetnice($is)).'"'; /* Note for csv any quote must be doublequoted */
						}
				/* cache the col headings */
					$csv = implode (",", $iline);	unset($iline);
					$cache->cache_report_line($rptid,0,$csv); /* cache the internal column headings */		
					$csv = implode (",", $line);	unset($line);
					$cache->cache_report_line($rptid,1,$csv); /* cache the column headings */	

					$count = 1;
					foreach ($list as $j => $u) {	
						$count  = $count +1;
						$line[] = '"'.$u['ID'].'"'; /* should be the user id */
						foreach ($s as $is => $v) {  /* defines the column order */
							if (!(isset($u[$is])))  {
								$line[] = '""'; /* there is no value */
							}
							else $line[] = '"'.str_replace('"','""',$u[$is]).'"'; /* Note for csv any quote must be doubleqouoted */
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
		
		
		$cache->record_cache_end($rptid, $count-1);
		$cache->record_cache_peakmem($rptid);
		$cache->record_cache_headings($rptid,$html);


		$time_end = microtime(true);
		$time = $time_end - $time_start;
		
		$cache->log_cache_event('Completed '.$rptid.' in '.number_format($time,2));		
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
		case 'comment_count': {  /* if they have wp stats plugin enabled */
			if ((empty($v)) or (!($stats_url = get_option('stats_url')))) return($v);
			else return( '<a href="'.add_query_arg('stats_author',$u->user_login, $stats_url).'">'.$v.'</a>');
			break;
		}
		default: {
			if (isset ($v)) return($v);
			else return(' ');
		}
	}
	return('');
}
/* -------------------------------------------------------------------------------------------------------------*/
function alist_one($type='user', $i=1, $do_headings=false, $do_csv=false){
	/* Get the fields to use for the chosen list type */
global $aopt;
global $amain;



	$c = new adb_cache();
	$rptid = $c->reportid($i, $type);
	
	$rowsperpage = rows_per_page($amain['rows_per_page']);
	
	if (!empty ($_REQUEST['listpage'])) $page = (int) $_REQUEST['listpage'];
	else $page=1;

	if (!($c->cache_exists($rptid)))  {
		if ($c->cache_in_progress($rptid)) {
			echo '<div style="clear:both;"><strong>'.$amain['names'][$i].' ('.$rptid.') '.$c->get_error('inprogress').'</strong>';
			}
		else {
			echo '<div style="clear:both;"><strong>'.$amain['names'][$i].' ('.$rptid.') '.$c->get_error('nocache').'</strong>';
			}

		$s = get_option('amr-users-cache-status');	


		return (false);
		}
	else {
			
		/* we will be alwasy saving the id anyway as the first column, but the user may also have requestred that it be displayed. */
		if (isset ($aopt[$i]['selected']['ID'])) $display_the_id = true;
		else $display_the_id = false;
	
		$line = $c->get_cache_report_lines ($rptid, '0', '2'); /* get the internal heading names  for internal plugin use only */  /* get the user defined heading names */
		
		if (!defined('str_getcsv')) $icols = amr_str_getcsv( $line[0]['csvcontent'], ',','"','\\');
		else $icols = str_getcsv( $line[0]['csvcontent'], ',','"','\\');
		if (!defined('str_getcsv')) $cols = amr_str_getcsv( $line[1]['csvcontent'], '","','"','\\');
		else $cols = str_getcsv( $line[1]['csvcontent'], ',','"','\\');

		$html = $hhtml = '';	
		foreach ($icols as $ic => $cv) { /* use the icols as our controlling array, so that we have the internal field names */
				$v = $cols[$ic];  
				if ($cv === 'comment_count') $v .= '<a title="'.__('Explanation of comment total functionality','amr-users').'"href="http://webdesign.anmari.com/comment-totals-by-authors/">**</a>';
				$hhtml .= '<th>'.$v.'</th>';
			}
		$hhtml = '<thead><tr>'.$hhtml.'</tr></thead>'; /* setup the html for the table headings */	
		$fhtml = '<tfoot><tr>'.$hhtml.'</tr></tfoot>'; /* setup the html for the table headings */	
		if ($page === 1) $start = 1;
		else $start = 1 + (($page - 1) * $rowsperpage);
		$totalitems = $c->get_cache_totallines($rptid);

		$lines = $c->get_cache_report_lines ($rptid, $start+1, $rowsperpage );


		if (!($lines>0)) {
			amr_flag_error($amr_errors->get_error('numoflists'));
			return (false);
		}
		
		foreach ($lines as $il =>$l) {

			if (!defined('str_getcsv')) $lineitems = amr_str_getcsv( $l['csvcontent'], '","','"','\\'); /* break the line into the cells */
			else $lineitems = str_getcsv( $l['csvcontent'], ',','"','\\'); /* break the line into the cells */
//				echo '<br />';
//				var_dump($lineitems);
		
			$id = $lineitems[0]; /*  *** pop the first one - this should always be the id */
//				echo '<br />';
//				var_dump($id);
			$user = get_userdata($id);
			$linehtml = '';
			foreach ($icols as $ic => $c) { /* use the icols as our controlling array, so that we have the internal field names */
				if (isset($lineitems[$ic])) {
					$v = $lineitems[$ic];  
					$linehtml .= '<td>'.amr_format_user_cell($c, $v, $user). '</td>';
				}
				else $linehtml .= '<td>&nbsp;</td>';
			}
			$html .=  AMR_NL.'<tr>'.$linehtml.'</tr>';			
		}
		$pagetext = amr_pagetext($page, $totalitems, $rowsperpage);

//		$html = '<div class="wrap" style="clear:both;">'
		$html = '<div class="wrap" >'
		.$pagetext
		.'<table class="widefat">'.$hhtml.'<tbody>'.$html.'</tbody>'.$fhtml.'</table>'
		.$pagetext.'</div>';
		
		/* offer a link to prepare a csv option to echo back if requested */
		if ($do_csv) { 
/* **** need a link to csv page? */
//amr_generate_csv($i);
				}
				

	return ($html);	
				
	}

}

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

				$hhtml .= '<th>'.$v.'</th>';
			}
		$hhtml = '<thead><tr>'.$hhtml.'</tr></thead>'; /* setup the html for the table headings */	
		$fhtml = '<tfoot><tr>'.$hhtml.'</tr></tfoot>'; /* setup the html for the table headings */	
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
		$html = '<table>'.$hhtml.'<tbody>'.$html.'</tbody>'.$fhtml.'</table>';


	return ($html);	
				
	

}
		
/* --------------------------------------------------------------------------------------------*/	
function amr_generate_csv($i=1) {
/* get the whole cached file - write to file? but security / privacy ? */
/* how big */
	$c = new adb_cache();
	$rptid = $c->reportid($i);
	$total = $c->get_cache_totallines ($rptid );
	$lines = $c->get_cache_report_lines($rptid,1,$total+1); /* we want the heading line (line1), but not the internal nameslines (line 0) , plus all the data lines, so neeed total + 1 */
	if (isset($lines) and is_array($lines)) $t = count($lines);
	else $t = 0;
	$csv = '';
	if ($t > 0) foreach ($lines as $k => $line) {
		$csv .= $line['csvcontent']."\r\n";
	}
	echo '<br /><h3>'.$c->reportname($i).'</h3>'
	.'<h4>'.sprintf(__('%s lines found, 1 heading line, the rest data.','amr-users'),$t).'</h4><br />';
	
	echo amr_csv_form($csv);
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
		amr_to_csv (htmlspecialchars_decode($_POST['csv'])); 
/*		amr_to_csv (html_entity_decode($_POST['csv'])); */
	}

	
?>
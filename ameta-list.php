<?php 
require_once ('ameta-includes.php');

/* -------------------------------------------------------------------------------------------------------------*/

function amr_list_user_headings($l){
global $amr_lists;
global $aopt;
		echo '<div class="postbox" >';
		echo '<ul class="subsubsub" style="float:left;">';
		for ($i = 1; $i <= $amr_lists['no-lists']; $i++)	{
			$n = &$amr_lists['names'][$i];
			echo '<li style="display:block; float:left;">';
			if ($l == $i) echo '<strong>'. amrmeta_view_link($n, $i, $n).'</strong>';
			else echo amrmeta_view_link($n, $i, $n);
			echo ' | </li>';
			}
		echo '<li style="display:block; float:left;"><a href="#csvbutton">'.__('Jump to CSV Export',AMETA_NAME).'</a></li>';
		if ( is_admin() ) {
			echo '<li style="display:block; float:left;"> | <a style="color:#D54E21;" href="options-general.php?page=ameta-admin.php">'.__('Configure all lists',AMETA_NAME).'</a></li>';
		echo '<li style="display:block; float:left;"> | '
			.amrmeta_configure_link(__('Configure this list',AMETA_NAME), $l,$n)
			.'</li>';
		}
		echo '</ul>';		
		echo '</div>';
}


/* -------------------------------------------------------------------------------------------------------------*/

function get_commentnumbers_by_author(  ) {
     global $wpdb;
	 /*** Rudimentary - if going to be used frequently (eg outside of admin area , then could do with optimistaion / cacheing */

	$approved = "comment_approved = '1'";
	$comments = $wpdb->get_results( 
	"SELECT user_id,  count(1) as \"comment_count\" FROM $wpdb->comments WHERE $approved AND user_id > 0 GROUP BY user_id;" );
	foreach ($comments as $i => $v) {
		$c[$v->user_id] = $v->comment_count;
	}
     return $c;
}
/* -------------------------------------------------------------------------------------------------------------*/
function alist_one($i, $do_headings=false, $do_csv=false){
	/* Get the fields to use for the chosen list type */
global $aopt;
global $amr_lists;

	$l = $aopt['list'][$i];
	$list = amr_get_alluserdata();
	
	/* get the extra count data */
	if ((isset ($l['selected']['comment_count'])) or
	    (isset ($l['included']['comment_count']))) 
	$c = get_commentnumbers_by_author();
	
	foreach ($list as $iu => $u) {
		if (isset ($c[$u['ID']])) $list[$iu]['comment_count'] = $c[$u['ID']];
		if ((isset ($l['selected']['post_count'])) or
	    (isset ($l['included']['post_count']))) {
			$list[$iu]['post_count'] = get_usernumposts($u['ID']); /* wordpress function */
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

			$head .= '<div class="wrap" style ="text-align: center; font-size:largest;"><h2>'.$amr_lists['names'][$i].'</h2>'; 
			/* to look like wordpress */
			$tablecaption .= '<caption> '.$amr_lists['names'][$i].'</caption>';
			$head .= '<ul class="report_explanation" style="list-style-type:none;">';
	/* check for filtering */

			if (isset ($l['excluded']) and (count($l['excluded']) > 0)) {/* do headings */
				$head .= '<li><em>'.__('Excluding where:',AMETA_NAME).'</em> ';
				foreach ($l['excluded'] as $k=>$ex) { 
					$head .= ' '.agetnice($k).'='.implode(__(' or ',AMETA_NAME),$ex).',';
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
				$head .= '<li><em>'.__('Exclude if blank:',AMETA_NAME).'</em> ';
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
				$head .= '<li><em>'.__('Include only if blank:',AMETA_NAME).'</em> ';
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
				$head .= '<li><em>'.__('Including where:',AMETA_NAME).'</em> ';
				foreach ($l['included'] as $k=>$in) { 
					$head .= ' '.agetnice($k).'='.implode(__(' or ',AMETA_NAME),$in).',';
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
				$head .= '<li><em>'.__(' Sorting by: ',AMETA_NAME).'</em>';			
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
			$head .=  '<li>'.sprintf( __('%1s Users selected from total of %2s', AMETA_NAME),$tot, $total).'</li></ul>';
			$head .= '</div>'; 
			$tablecaption .= '</caption>'; 
			
			if (($do_headings) or is_admin()) $html .= $head;
			$html .=  '<table class="widefat meta" style="margin: auto; width: auto">';		
			if (!$do_headings) $html .= $tablecaption;
			
			
			if ($tot > 0) { 
				$html .=  '<thead><tr class="thead">'; 
				foreach ($s as $is => $v) { 
					$html .=  AMR_NL.'<th>'.agetnice($is).'</th>';
					$line[] = '"'.str_replace('"','""',agetnice($is)).'"'; /* Note for csv any quote must be doublequoted */
					}
				$html .=  '</tr></thead>';	

				$csv[] = implode (",", $line);
				unset($line);
				
				$html .=  '<tfoot><tr class="thead">';
				foreach ($s as $is => $v) {$html .=  AMR_NL.'<th>'.agetnice($is).'</th>';}
				$html .=  '</tr></tfoot>';		
				
				foreach ($list as $j => $u) {
					$html .=  AMR_NL.'<tr>';
					$first = true;
					foreach ($s as $k => $v) {  /* selected should be presorted */
						$html .=  '<td>';
						switch ($k) {
							case 'user_email': {
								$html .= '<a href="mailto:'.$u[$k].'">'.$u[$k].'</a>';
								break;
							}
							case 'user_login': {
								$html .= '<a href="'.get_bloginfo('url').'/wp-admin/user-edit.php?user_id='.$u['ID'].'">'.$u[$k].'</a>';
								break;
							}
							case 'post_count': {
								$html .= '<a href="'.add_query_arg('author',$u['ID'], get_bloginfo('url')).'">'.$u[$k].'</a>';
								break;
							}
							default: {
								if (isset ($u[$k])) $html .=  $u[$k];
								else $html .=  ' ';
							}
						}
						$html .=  '</td>';
						/* prepare csv values if requested */
						$line[] = '"'.str_replace('"','""',$u[$k]).'"'; /* Note for csv any quote must be doubleqouoted */
					}	
					/* prepare csv values if requested */
					if ($do_csv) { $csv[] = implode (",", $line); 	unset($line); }
					$html .=  AMR_NL.'</tr>';
				}	
				$html .=  AMR_NL.'</table>'.AMR_NL;
				/* prepare a csv option to echo back if requested */
				if ($do_csv) { 
					$csv2 = implode("\r\n", $csv);
					$html .=  '<a name="csvbutton"></a>';
					$html .=  '<form method="post" action="" id="csvexp" ><fieldset >';
					$html .=  '<input type="hidden" name="csv" value="'.htmlentities($csv2) . '" />'.AMR_NL;

					$html .=  '<input style="font-size: 1.5em !important;" type="submit" name="reqcsv" value="'.__('Export to CSV',AMETA-NAME).'" class="button" />';
					$html .=  '</fieldset></form>';
				}
			}
			else $html .= sprintf( __('No users found for list %s', AMETA_NAME), $i);
			
		}
		else $html .=  '<h2 style="clear:both; ">'.sprintf( __('No fields chosen for display in settings for list %s', AMETA_NAME), $i).'</h2>';
	}
	else $html .= __('No users in database! - que pasar?', AMETA_NAME);
	
	return ($html);
}


/* -------------------------------------------------------------------------------------------------------------*/

function amr_list_user_meta(){
global $aopt;
global $amr_lists;
global $amr_nicenames;

	$aopt = ameta_options(); 
	if (isset ($aopt['list'])) {
		if (isset($_REQUEST['page']))  { /*  somehow needs to be ? instead of & in wordpress admin, so we don't get as separate  */
			$param = 'am_ulist=';
			$l = substr (stristr( $_REQUEST['page'], $param), strlen($param));
			}
		else $l = 1;	/* just do the first list */
		amr_list_user_headings($l);			
		echo alist_one($l, true);  /* list the list with the explanatory headings */
		}
	else _e ("No lists Defined", AMETA_NAME);

	return;
}	
/* ----------------------------------------------------------------------------------- */

	if (( isset ($_POST['csv']) ) and (isset($_POST['reqcsv']))) {	
	/* since data passed by the form, a security check here is unnecessary, since it will just create headers for whatever is passed .*/
		amr_to_csv (htmlspecialchars_decode($_POST['csv']));
	}

?>
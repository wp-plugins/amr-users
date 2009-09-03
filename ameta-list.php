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
		if ( is_admin() ) {
			echo '<li style="display:block; float:left;"><a href="options-general.php?page=ameta-admin.php">'.__('Configure all lists',AMETA_NAME).'</a> | </li>';
			}
		echo '<li style="display:block; float:left;"><a href="#csvbutton">'.__('Jump to CSV Export',AMETA_NAME).'</a>&nbsp;&nbsp;&nbsp;</li>';
		echo '</ul>';
		
		if ( is_admin() ) echo '<span class="button" style="float:right; padding-right: 2em;">'
			.amrmeta_configure_link(__('Configure this list',AMETA_NAME), $l,$n)
			.'</span>';
		
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
function alist_one($i){
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

	if (count($list) > 0) {	
		if (isset ($l['selected']) and (count($l['selected']) > 0))  {
			$sel = ($l['selected']);
			asort ($sel); /* get the selected fields in the display  order requested */
			foreach ($sel as $s2=>$sv) { if ($sv > 0) $s[$s2] = $sv; }
			echo '<div class="wrap" style ="text-align: center"><h2 style="padding: 1em 0 0.2em 0;" >'.$amr_lists['names'][$i].'</h2>'; 
			echo '<ul><li>';;
	/* check for filtering */

			if (isset ($l['excluded']) and (count($l['excluded']) > 0)) {/* do headings */
				echo '&nbsp;<em>'.__('Excluding where:',AMETA_NAME).'</em> ';
				foreach ($l['excluded'] as $k=>$ex) { 
					echo ' '.agetnice($k).'='.implode(__(' or ',AMETA_NAME),$ex).',';
					foreach ($list as $iu=>$user) { 
						if (isset ($user[$k])) { /* then we need to check the values and exclude the whole user if necessary  */
							if (in_array($user[$k], $ex)) {
								unset ($list[$iu]);
							}	
						}
					}	
				}
			}
	
			if (isset ($l['included']) and (count($l['included']) > 0)) {
				echo '&nbsp;<em>'.__('Including where:',AMETA_NAME).'</em> ';
				foreach ($l['included'] as $k=>$in) { 
					echo ' '.agetnice($k).'='.implode(__(' and ',AMETA_NAME),$in).',';
					foreach ($list as $iu => $user) { /* for each user */
						if (isset ($user[$k])) {/* then we need to check the values and include the  user if a match */
							if (!(in_array($user[$k], $in))) {
								unset ($list[$iu]);
							}	
						}
					}	
				}
			}
		
			if (isset ($l['sortby']) and (count($l['sortby']) > 0)) { 
				echo '&nbsp;<em>'.__(' Sorting by: ',AMETA_NAME).'</em>';			
				asort ($l['sortby']); 
				$cols= array();
				foreach ($l['sortby'] as $sbyi => $sbyv) {
					if (isset($l['sortdir'][$sbyi])) 
						$cols[$sbyi] = array(SORT_DESC);
					else $cols[$sbyi] =  array(SORT_ASC);
					echo agetnice($sbyi).',';
				}
				$list = auser_msort($list, $cols );
			}
			
			$tot = count($list);
			echo '</li><li>'.sprintf( __('%1s Users selected from total of %2s', AMETA_NAME),$tot, $total).'</li></ul></div>';
			if ($tot > 0) { 

				echo '<table class="widefat meta" style="margin: auto; width: auto">';	
				echo '<thead><tr class="thead">'; 
				foreach ($s as $is => $v) { echo AMR_NL.'<th>'.agetnice($is).'</th>';}
				echo '</tr></thead>';	
				echo '<tfoot><tr class="thead">';
				foreach ($s as $is => $v) {echo AMR_NL.'<th>'.agetnice($is).'</th>';}
				echo '</tr></tfoot>';		
				
				foreach ($list as $j => $u) {
					echo AMR_NL.'<tr>';
					$first = true;
					foreach ($s as $k => $v) {  /* selected should be presorted */
						echo '<td>';
						if ($first) {
							echo '<a href="'.WP_SITEURL.'/wp-admin/user-edit.php?user_id='.$u['ID'].'">'.$u[$k].'</a>';
							$first = false;
						}
						else if (isset ($u[$k])) echo $u[$k];
						else echo ' ';
						echo '</td>';
						/* prepare csv values if requested */
						$line[] = '"'.str_replace('"','""',$u[$k]).'"'; /* Note for csv any quote must be doubleqouoted */
					}	
					/* prepare csv values if requested */
					$csv[$j] = implode (",", $line);
					unset($line);
					echo AMR_NL.'</tr>';
				}	
				echo AMR_NL.'</table>'.AMR_NL;
				/* prepare a csv option to echo back if requested */
				$csv2 = implode("\r\n", $csv);
				echo '<a name="csvbutton"></a>';
				echo '<form method="post" action="" id="csvexp" ><fieldset >';
				echo '<input type="hidden" name="csv" value="'.htmlentities($csv2) . '" />'.AMR_NL;
				echo '<input style="font-size: 1.5em !important;" type="submit" name="reqcsv" value="'.__('Export to CSV',AMETA-NAME).'" class="button" />';
				echo '</fieldset></form>';
			}
			else printf( __('No users found for list %s', AMETA_NAME), $i);
			
		}
		else echo '<h2 style="clear:both; ">'.sprintf( __('No fields chosen for display in settings for list %s', AMETA_NAME), $i).'</h2>';
	}
	else _e('No users in database! - que pasar?', AMETA_NAME);
}


/* -------------------------------------------------------------------------------------------------------------*/

function amr_list_user_meta(){
global $aopt;
global $amr_lists;
global $amr_nicenames;

	if (!isset ($amr_lists) ) $amr_lists = ameta_no_lists();
	$amr_nicenames = ameta_nicenames();
	$aopt = ameta_options(); 
	if (isset ($aopt['list'])) {
		if (isset($_REQUEST['page']))  { /*  somehow needs to be ? instead of & in wordpress admin, so we don't get as separate  */
			$param = 'am_ulist=';
			$l = substr (stristr( $_REQUEST['page'], $param), strlen($param));
			}
		else $l = 1;	/* just do the first list */
		amr_list_user_headings($l);			
		alist_one($l);
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
<?php 
require_once ('ameta-includes.php');
//require_once (ABSPATH.'wp-includes/pluggable.php');


/* -------------------------------------------------------------------------------------------------------------*/

function amr_list_user_meta(){
global $aopt;
	$aopt = ameta_options();
	echo '<h2>Lists</h2>';
	
	if (isset ($aopt['list'])) {
		echo '<ul class="subsubsub">';
		foreach ($aopt['list'] as $i => $v){
		echo '<li><a href="'. htmlspecialchars(add_query_arg ('list', $i)).'" >'
			.$v['name'].'</a>&nbsp;&nbsp;&nbsp;</li>';
			}
		echo '<li><a href="#csvbutton">'.__('Jump to CSV Export',AMETA_NAME).'</a></li></ul>';
	}
	else _e ("No lists Defined", AMETA_NAME);
	
//	echo '<div class="tablenav">';
//	echo 'Additional Filter options will go here in time.';
// 	echo '</div>';

	if (isset($_REQUEST['list']))  { /*  */
		$l = (int) $_REQUEST['list'];
		alist_one($l);
	}
	else  		alist_one(1);

	return;
}
/* -------------------------------------------------------------------------------------------------------------*/
function ausersort2( $one, $two, $data) {
	// Obtain a list of columns
	foreach ($data as $key => $row) {
	    $one1[$key]  = $row[$one];
	    $two2[$key] = $row[$two];
	}
	// Add $data as the last parameter, to sort by the common key
	array_multisort($one1, SORT_ASC, $two2, SORT_ASC, $data);

	return ($data);
}
/* -------------------------------------------------------------------------------------------------------------*/
function ausersort1( $one, $data) {
	// Obtain a list of columns
	foreach ($data as $key => $row) {
	    $one1[$key]  = $row[$one];
	}
	array_multisort($one1, SORT_ASC, $data);
	return ($data);
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
	$l = $aopt['list'][$i];
	$list = amr_get_alluserdata();
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

	if (count($list) > 0) {	/* do headings */
		if (isset ($l['selected']))  {
			$sel = ($l['selected']);
			asort ($sel); /* get the selected fields in the display  order requested */
			foreach ($sel as $s2=>$sv) { if ($sv > 0) $s[$s2] = $sv; }
			echo '<table class="widefat fixed meta">';
			echo '<caption><strong>'.$l['name'].'</strong></ br>';
			echo '<ul><li>';;
	/* check for filtering */
			if (isset ($l['excluded'])) 
			foreach ($l['excluded'] as $k=>$ex) { 
				printf(__(' <em>Excluding:</em> %1s = %2s',AMETA_NAME),agetnice($k), implode(__(' or ',AMETA_NAME),$ex));
				foreach ($list as $iu=>$user) { 
					if (isset ($user[$k])) { /* then we need to check the values and exclude the whole user if necessary  */
						if (in_array($user[$k], $ex)) {
							unset ($list[$iu]);
							break;
						}	
					}
				}	
			}
	
			if (isset ($l['included'])) 
				foreach ($l['included'] as $k=>$in) { 
					echo '&nbsp;';
					printf(__(' <em> Including</em> where %1s = %2s',AMETA_NAME),agetnice($k), implode(__(' or ',AMETA_NAME),$in));
					foreach ($list as $iu=>$user) { 
						if (isset ($user[$k])) { /* then we need to check the values and exclude the whole user if no match */
							if (!(in_array($user[$k], $in))) {
								unset ($list[$iu]);
								break;
							}	
						}
					}	
			}
			
			if (isset ($l['sortby'])) { $ss = $l['sortby']; 
				if (isset ($ss['1'])) { 
					$one = $ss['1'][0]; 
					$seq1 = $ss['1'][1]; 
					printf( __(' <em>Sorting by: </em>%s ',AMETA_NAME), agetnice($one));
					if (isset ($ss['2'])) { 
						$two = $ss['2'][0]; 
						$seq1 = $ss['2'][1]; 
						printf( __(' and %s ',AMETA_NAME), agetnice($two));	
						$list = ausersort2 ( $one, $two,  $list);
					}
					else $list = ausersort1 ($one, $list);	
				}
			}
			
			echo '<li>'.sprintf( __('%1s Users selected from total of %2s', AMETA_NAME),count($list), $total).'</li></ul>';
	
			echo '</caption>';
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

			echo '<form method="post" action="" id="csvexp"><fieldset>';
			echo '<input type="hidden" name="csv" value="'.htmlentities($csv2) . '" />'.AMR_NL;
			echo '<input style="font-size: 1.5em !important; " type="submit" name="reqcsv" value="'.__('Export to CSV',AMETA-NAME).'" class="button" />';
			echo '</fieldset></form>';
			
		}
		else _e('No selection specified in options', AMETA_NAME);
	}
	else _e('No users found', AMETA_NAME);
	
}


/* ----------------------------------------------------------------------------------- */

	if (( isset ($_POST['csv']) ) and (isset($_POST['reqcsv']))) {	
	/* since data passed by the form, a security check here is unnecessary, since it will just create headers for whatever is passed .*/
		amr_to_csv (htmlspecialchars_decode($_POST['csv']));
	}
	
?>
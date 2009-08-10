<?php 
require_once ('ameta-includes.php');

/* -------------------------------------------------------------------------------------------------------------*/
function amr_user_meta_totals(){


	echo '<h2>Not fully implemented yet - stay tuned at anmari.com</h2>';
	return;

	$aopt = get_option ('amrmeta');
	$usermeta_selected = $aopt[0]['selected'];
	$usermeta_display = $aopt[0]['display'];
	$usermeta_totals = $aopt[0]['totals'];
	
	$list = ( amr_get_usermetavalues($aopt[0]['selected'])); 
	foreach ($list as $userid => $detailsarray) {
		foreach ($usermeta_totals as $i => $t) {
			if (isset ($detailsarray[$t])) {
				$totals [$t][$detailsarray[$t]]++;
			}	
		}
	}

	if (count($totals) > 0) {
	/* do headings */
		echo AMR_NL.'<table class="widefat userdata-totals" style="width:200px;">';
		foreach ($totals as $i => $t) {
			echo AMR_NL.'<thead><tr class="thead"><th colspan="2"><strong>'.amr_get_usermeta_nicename($i).'</strong></th></thead>';
			$total = 0;
			echo AMR_NL.'<tbody>';

			foreach ($t as $i2 => $t2) {
				$total = $total + $t2;
				echo AMR_NL.'<tr><th>'.$i2.'</th>'.'<td>'.$t2.'</td></tr>';
			}	
			echo AMR_NL.'</tr><th></th><th><strong>'.$total.'</strong></th></tr>';	
			echo AMR_NL.'</tbody>';
		}
		echo AMR_NL.'</table>'.AMR_NL;
	}
	else _e('No totals found',PLUGINAME );
}

//amr_user_meta_totals();
?>
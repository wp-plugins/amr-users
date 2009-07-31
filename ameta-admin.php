<?php
/* This is the amr  admin section file */

require_once ('ameta-includes.php');

/* -------------------------------------------------------------------------------------------------------------*/
	
function amrmeta_validate_options()	{
		global $aopt;
		$nonce = $_REQUEST['_wpnonce'];
		if (! wp_verify_nonce($nonce, AMETA_NAME)) die ("Cancelled due to failed security check");
		
		if (isset($_POST["no-lists"]) ) {		
			if (function_exists( 'filter_var') ) {
				$int_ok = (filter_var($_POST["no-lists"], FILTER_VALIDATE_INT, 
					array("options" => array("min_range"=>1, "max_range"=>10))));
			}
			else $int_ok = 	(is_integer($_POST["no-lists"]) ? $_POST["no-lists"] : false);
			if ($int_ok) {	
				for ($i = $aopt['no-lists']+1; $i <= $int_ok; $i++)	{	
					$aopt['list'][$i] = $aopt['list'][1];	
					$aopt['list'][$i]['name'] .= ' '.$i;
				}
				$aopt['no-lists'] =  $int_ok;							
				}
			else {
				_e('Number of Lists must be greater than 0 and less than 11', AMETA_NAME);	
				return(false);
				}
		}
	
		if (isset($_POST['nn'])) { 
			if (is_array($_POST['nn'])) {
				foreach ($_POST['nn'] as $i => $v) {   	
					if	($s = filter_var ($_POST['nn'][$i], FILTER_SANITIZE_STRING ))  		
						$aopt['nicenames'][$i] = $s;
					else { 
						echo '<h2>Error in string:'.$s.'</h2>';
						return(false);
						}	
					}
				}
			else return(false);	
			}
			
//		echo '<h2> post</h2>';	var_dump($_POST);
				
		if (isset($_POST['l'])) {
			if (is_array($_POST['l'])) {/*  do we have selected, etc*/
				foreach ($_POST['l'] as $i => $arr) {		/* for each list */	
					if (is_array($arr))  {/*  */
						if (isset ($arr['name'])) { 
							$aopt['list'][$i]['name'] = $arr['name'];
							}
						if (is_array($arr['selected']))  {/*  do we have  name, selected, etc*/		
							foreach ($arr['selected'] as $j => $v) {
								if (empty($v)) $aopt['list'][$i]['selected'][$j] = '';
								else 
								if ($s = filter_var($v, FILTER_VALIDATE_INT,
									array("options" => array("min_range"=>1, "max_range"=>999))))
									$aopt['list'][$i]['selected'][$j] = $s;
								else {
									echo '<h2>Error in integer: for '.$j.$s.'</h2>';
									return(false);
								}	
							}
						
//							asort ($aopt['list'][$i]['selected'],SORT_NUMERIC);
							}
						
						/* Now check included */
						if (is_array($arr['included']))  {		
							foreach ($arr['included'] as $j => $v) {
								if (empty($v)) unset($aopt['list'][$i]['included'][$j]);
								else 
								$aopt['list'][$i]['included'][$j] 
									= explode (',', filter_var($v, FILTER_SANITIZE_STRING));
								}	
							}
						/* Now check excluded */
						if (is_array($arr['excluded']))  {		
							foreach ($arr['excluded'] as $j => $v) {
								if (empty($v)) unset($aopt['list'][$i]['excluded'][$j]);
								else 
								$aopt['list'][$i]['excluded'][$j] 
									= explode(',', filter_var($v, FILTER_SANITIZE_STRING));
								}	
							}	
					}
				}
		}
		else return (false);
		}
		
	return (true);	
	}
/* ---------------------------------------------------------------------*/

	
	//styling options page
function ameta_admin_header() {?>
	
		<style type="text/css" media="screen">
		
fieldset {  
  padding: 1em;

  }
legend {
	  font-size: 1.1em;
	  font-weight: bold;
}  
form label {
	display: block;  /* block float the labels to left column, set a width */
	float: left; 
	text-align: right; 
	width:20em;
  margin-right:0.5em;
  padding-top:0.2em;
  padding-left:2em;

  }
		</style>
		<?php
	}
	
	/* ---------------------------------------------------------------------*/
	function amrmeta_acknowledgement () {
	?>
	<p style="border-width: 1px;"><?php _e('Significant effort goes into these plugins to ensure that they <strong>work straightaway</strong> with minimal effort, are easy to use but <strong>very configurable</strong>, that they are <strong>well tested</strong>,that they produce <strong>valid html and css</strong> both at the front and admin area. If you wish to remove the credit link or using the plugin commercially, then please donate.','amr-ical-events-list'); ?>
	<span style="font-size: x-large;"><a href="http://webdesign.anmari.com/web-tools/donate/"><?php
	_e('Donate','amr-ical-events-list');?></a></span>
	&nbsp;&nbsp;&nbsp;<a href="http://webdesign.anmari.com/plugins/users/">Author&#39;s website</a>
	&nbsp;&nbsp;&nbsp;<a href="">Plugin at wordpress</a></p>
	<?php
	}
		/* ---------------------------------------------------------------------*/
	function ameta_listnicefield ($nnid, $nnval, $v, $v2=NULL) {
	
		echo "\n\t".'<li><label for="nn'.$nnid.'"  '.(is_null($v2)?'>':' class="nested" >') .$v.' '.$v2.'</label>'
		.'<input type="text" size="25" id="nn'.$nnid.'"  name="nn['.$nnid.']"  value= "'.$nnval.'" /></li>'; 
	}
	/* ---------------------------------------------------------------------*/
	function ausers_submit () {	
	return ('
	<fieldset class="submit">
		<input type="hidden" name="action" value="save" />
		<input type="submit" name="update" value="'. __('Update', AMETA_NAME) .'" />
		<input type="submit" name="reset" value="'. __('Reset', AMETA_NAME) .'" />
		<input type="submit" name="uninstall" value="'. __('Uninstall', AMETA_NAME) .'" />		
	</fieldset>');
	}
		
	/* ---------------------------------------------------------------------*/	
	function ameta_list_nicenames_for_input($nicenames) {
	/* get the standard names and then the  meta names  */

		echo "\n\t".'<fieldset class="widefat">';
		echo '<legend>'.__('Nicer names for list headings',AMETA_NAME).'</legend><ul>';
	
		foreach ($nicenames as $i => $v ) {
			echo "\n\t".'<li><label for="nn'.$i.'" >'.$i.'</label>'
			.'<input type="text" size="25" id="nn'.$i.'"  name="nn['.$i.']"  value= "'.$v.'" /></li>';
		}	
		echo "\n\t".'</ul></fieldset>';
		return;	
		
	}

	/* ---------------------------------------------------------------------*/
	
	function amr_listfields( $listindex, $nicenames, $config) {
	/*  List the fields for the specified list number, and for the configuration type ('selected' etc) */
		/*** would be nice to srt, but have to move away from nicenames as main index then */
		echo  AMR_NL.'<fieldset>';

		echo '<table><thead  style="text-align:center;"><tr><th style="text-align:right;">'
			.__('Display order',AMETA_NAME).'</th>'
			.'<th>'.__('Include:',AMETA_NAME).'</th>'
			.'<th>'.__('But Exclude:',AMETA_NAME).'</th>'
			.'</tr></thead><tbody>';
			foreach ( $nicenames as $i => $f )		{					

				echo AMR_NL.'<tr>';
				$l = 'l'.$listindex.'-'.$i;
				echo '<td><label for="'.$l.'"  >'.$f.' </label>';
				echo '<input type="text" size="1" id="'.$l.'" name="l['.$listindex.'][selected]['.$i.']"'. 
				' value="'.$config['selected'][$i] .'" /></td>';
				$l = 'i'.$listindex.'-'.$i;		
				/* don't need label - use previous lable*/			
				echo '<td><input type="text" size="20" id="'.$l.'" name="l['.$listindex.'][included]['.$i.']"';
				if (isset ($config['included'][$i])) echo ' value="'.implode(',',$config['included'][$i]) .'"';
				echo '/></td>';
				
				$l = 'x'.$listindex.'-'.$i;
				echo '<td><input type="text" size="20" id="'.$l.'" name="l['.$listindex.'][excluded]['.$i.']"';
				if (isset ($config['excluded'][$i])) echo ' value="'.implode(',',$config['excluded'][$i]) .'"';
				echo '/></td>';

//				echo '<select multiple="yes" size="3" id="'.$l.'" name="inc['.$listindex.']["include"]['.$i.']"'. 
//				' value="'.$config['include'][$i] .'" /></td>';

				echo '</tr>';
			}
		echo AMR_NL.'</tbody></table></fieldset>';
	return;	
	}
/* ---------------------------------------------------------------------*/	
	function amrmeta_options_page() {
	global $aopt;

	$nonce = wp_create_nonce('amr_ical'); /* used for security to verify that any action request comes from this plugin's forms */
	if (isset($_REQUEST['uninstall'])  OR isset($_REQUEST['reallyuninstall']))  { /*  */
		die ('<h2>Need uninstall code</h2>');	
		return;
	}
	else if (isset ($_POST['reset'])) {
			delete_option (AMETA_NAME);
			unset ($aopt);
			$aopt = ameta_defaultoptions();
			update_option (AMETA_NAME, $aopt);
			echo '<h2>'.__('Reset', AMETA_NAME).'</h2>'; 
		}	
	else {
		$aopt = ameta_options();
		if ($_POST['action'] == "save") {/* Validate the input and save */
			if (amrmeta_validate_options()) {
				update_option (AMETA_NAME, $aopt);
				echo '<h2>'.__('Options Updated', AMETA_NAME).'</h2>'; 
				}
			else echo '<h2>'.__('Validation failed', AMETA_NAME).'</h2>'; 	
		}
	}
	amrmeta_acknowledgement();
	echo '<strong>';
	_e('Configuration of User Lists and Statistic Reports:',AMETA_NAME);
	
//	_e('Configure: ', AMETA_NAME);
		for ($i = 1; $i <= $aopt['no-lists']; $i++) { 	
			echo '&nbsp;&nbsp;<a href="#list'.$i.'" title="'.$aopt['list'][$i]['name'].'" >List'.$i.'</a> &nbsp;&nbsp;';
		}	
		echo '</strong>';
//		echo ausers_submit(); ?>
		<div class="wrap" id="AMETA_NAME"> 		
		<form method="post" action="<?php htmlentities($_SERVER['PHP_SELF']); ?>">
			<?php  wp_nonce_field(AMETA_NAME); /* outputs hidden field */?>			
			<fieldset>
				<label for="no-lists"><?php _e('Number of Lists:', AMETA_NAME); ?></label>
				<input type="text" size="2" id="no-lists" 
					name="no-lists" value="<?php echo $aopt['no-lists'];  ?>" />
			</fieldset>
			<?php	ameta_list_nicenames_for_input($aopt['nicenames']); ?>
			<fieldset>
			<?php 
			$alt = true;
			for ($i = 1; $i <= $aopt['no-lists']; $i++) { 	
			
				echo '<a name="list'.$i.'"> </a>';
				echo AMR_NL.'<fieldset class="widefat">';	
				echo '<legend>'.sprintf(__('Configure list %s',AMETA_NAME),$i).'</legend>'; 
		
				echo '<label for="name'.$i.'">'.__('Name of List ', AMETA_NAME).$i; 
				echo '</label>';
				echo AMR_NL.'<input type="text" size="30" id="name'.$i.'" name="list['.$i.'][name]"'
				.' value="'.$aopt['list'][$i]['name'].'" />'.AMR_NL;	
//				amr_listfields(__('Select for Display', AMETA_NAME), 
//					'selected', $i,$aopt['nicenames'], $aopt['list'][$i]['selected'] );
				amr_listfields($i,$aopt['nicenames'], $aopt['list'][$i] );
				echo AMR_NL.'</fieldset>'.AMR_NL; 
			} 
			echo AMR_NL.'</fieldset>'.AMR_NL; 
			echo ausers_submit(); ?>
			</form>
		</div>
		<?php
		
	}	//end amrmetaoption_page
/* ---------------------------------------------------------------------*/
?>
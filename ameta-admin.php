<?php
/* This is the amr  admin section file */

require_once ('ameta-includes.php');

/* -------------------------------------------------------------------------------------------------------------*/
	
function amrmeta_validate_no_lists()	{ /* basically the number of lists & names */
	global $amr_lists;
	global $aopt;
		
		if (function_exists( 'filter_var') ) {
			$int_ok = (filter_var($_POST["no-lists"], FILTER_VALIDATE_INT, 
				array("options" => array("min_range"=>1, "max_range"=>40))));
		}
		else $int_ok = (is_numeric($_POST["no-lists"]) ? $_POST["no-lists"] : false);
		if ($int_ok) {
			if ($int_ok > $amr_lists['no-lists'] ) {
				for ($i = $amr_lists['no-lists']+1; $i <= $int_ok; $i++)	{	
					$amr_lists['names'][$i] = $amr_lists['names'][1].'-'.__('copy').' '.$i;
				}				
			}	
			$amr_lists['no-lists'] =  $int_ok;
			return (true);
		}	
			
		else {
			_e('Number of Lists must be greater than 0 and less than 40', AMETA_NAME);	
			return(false);
			}
}
/* -------------------------------------------------------------------------------------------------------------*/
	
function amrmeta_validate_names()	{ /*  the names of lists */
	global $amr_lists;

	if (is_array($_POST['name']))  {/*  do we have selected, etc*/
		foreach ($_POST['name'] as $i => $n) {		/* for each list */	
			$amr_lists['names'][$i] = $n;		/*** shoudld validate somehow? */
		}
		return (true);
	}
	else return (false);
	
}	
	
/* -------------------------------------------------------------------------------------------------------------*/
	
function amrmeta_validate_mainoptions()	{ /* basically the number of lists */
	global $amr_lists;
	if (isset($_POST["no-lists"]) ) amrmeta_validate_no_lists();
	if (isset($_POST['name'])) amrmeta_validate_names();
	return (update_option (AMETA_NAME.'-no-lists', $amr_lists));
	}

/* ---------------------------------------------------------------------*/
	//styling options page
function ameta_admin_header() {
amr_script();
?>
<!-- Admin styles for amr-users settings screen - admin_print_styles trashed the admin menu-->
<style type="text/css" media="screen">
legend {
	  font-size: 1.1em;
	  font-weight: bold;
}  
form label.lists {
	display: block;  /* block float the labels to left column, set a width */
	clear: left;
	float: left;  
	text-align: right; 
	width:40%;
	margin-right:0.5em;
	padding-top:0.2em;
	padding-bottom:1em;
	padding-left:2em;
 }
.userlistfields th a { cursor: help;}
</style>
		
<?php
}
	
	/* ---------------------------------------------------------------------*/
	function amrmeta_acknowledgement () {
	?>
	<p><a class="button" href="http://webdesign.anmari.com/about/donate/"><?php
	_e('Say thanks','amr-ical-events-list');?></a>
	&nbsp;&nbsp;&nbsp;<a class="button" href="http://webdesign.anmari.com/plugins/users/"><?php _e('Plugin at Author&#39;s website',AMETA_NAME);?></a>
	&nbsp;&nbsp;&nbsp;<a class="button" href="http://wordpress.org/extend/plugins/amr-users/"><?php _e('Plugin at wordpress',AMETA_NAME);?></a>
	</p>
	<?php
	}
/* -------------------------------------------------------------------------------------------------------------*/
	
function amrmeta_validate_nicenames()	{
		global $amr_nicenames;
	
		if (isset($_POST['nn'])) { 
			if (is_array($_POST['nn'])) {
				foreach ($_POST['nn'] as $i => $v) {   	
					if	($s = filter_var ($v, FILTER_SANITIZE_STRING ))  		
						$amr_nicenames[$i] = $s;
					else { 
						echo '<h2>Error in string:'.$s.'</h2>';
						return(false);
						}	
					}
				}
			else return(false);	
			}

		return (true);	
	}
	/* -------------------------------------------------------------------------------------------------------------*/

	function ameta_listnicefield ($nnid, $nnval, $v, $v2=NULL) {
	
		echo "\n\t".'<li><label class="lists" for="nn'.$nnid.'"  '.(is_null($v2)?'>':' class="nested" >') .$v.' '.$v2.'</label>'
		.'<input type="text" size="50" id="nn'.$nnid.'"  name="nn['.$nnid.']"  value= "'.$nnval.'" /></li>'; 
	}
	/* ---------------------------------------------------------------------*/
	function ausers_submit () {	
	return ('
	<fieldset class="submit">
		<input type="hidden" name="action" value="save" />
		<input type="submit" name="update" value="'. __('Update', AMETA_NAME) .'" />
		<input type="submit" name="uninstall" value="'. __('Uninstall', AMETA_NAME) .'" />		
		<input type="submit" name="reset" value="'. __('Reset all options', AMETA_NAME) .'" />
	</fieldset>');
	}
		/* ---------------------------------------------------------------------*/
	function ausers_update () {	
	return ('
	<fieldset class="submit">
		<input type="hidden" name="action" value="save" />
		<input type="submit" name="update" value="'. __('Update', AMETA_NAME) .'" />
	</fieldset>');
	}
		
	/* ---------------------------------------------------------------------*/	
	function ameta_list_nicenames_for_input($nicenames) {
	/* get the standard names and then the  meta names  */

		echo "\n\t".'<fieldset class="widefat wp-submenu">';
		echo '<legend>'.__('Nicer names for list headings',AMETA_NAME).'</legend><ul>';
		foreach ($nicenames as $i => $v ) {
			echo "\n\t".'<li style="clear: both;"><label class="lists" for="nn'.$i.'" >'.$i.'</label>'
			.'<input type="text" size="30" id="nn'.$i.'"  name="nn['.$i.']"  value= "'.$v.'" />'
			.'</li>';
		}	
		echo "\n\t".'</ul></fieldset>';
		return;	
		
	}
	/* ---------------------------------------------------------------------*/	
	function amrmeta_nicenames_page() {
	global $amr_nicenames;
	
		$amr_nicenames = ameta_nicenames();
		if ($_POST['action'] == "save") {/* Validate the input and save */
			if (amrmeta_validate_nicenames()) {
				update_option (AMETA_NAME.'-nicenames', $aopt);
				echo '<h2>'.__('Options Updated', AMETA_NAME).'</h2>'; 
				}
			else echo '<h2>'.__('Validation failed', AMETA_NAME).'</h2>'; 	
		}

		echo ausers_update(); 
		ameta_list_nicenames_for_input($amr_nicenames); 
		echo ausers_update();
	
	}	//end amrmeta nice names option_page
/* ---------------------------------------------------------------------*/
	
function amrmeta_validate_listfields()	{
global $aopt;

/* We are only coming here if there is a SAVE, now there may be blanked out fields in all areas - except must have something selected*/
					
	if (isset($_POST['list'])) {
		if (is_array($_POST['list'])) {/*  do we have selected, etc*/
			foreach ($_POST['list'] as $i => $arr) {		/* for each list */	
					
				if (is_array($arr))  {/*  */

					if (is_array($arr['selected']))  {/*  do we have  name, selected, etc*/		
						foreach ($arr['selected'] as $j => $v) {
							if (empty($v) or ($v == '0')) unset ($aopt['list'][$i]['selected'][$j] );
							else 
							if ($s = filter_var($v, FILTER_VALIDATE_INT,
								array("options" => array("min_range"=>1, "max_range"=>999))))
								$aopt['list'][$i]['selected'][$j] = $s;
							else {
								echo '<h2>Error in integer: for '.$j.$s.'</h2>';
								return(false);
							}	
						}
//						asort ($aopt['list'][$i]['selected']); /* sort at update time so we don't have to sosrt every display time */
					}
					else {
						echo '<h2>No fields selected for display</h2>'; return (false);
					}
					
					/* Now check included */
					if (is_array($arr['included']))  {		
						foreach ($arr['included'] as $j => $v) {
							if (auser_novalue($v)) unset($aopt['list'][$i]['included'][$j]);
							else {
								$aopt['list'][$i]['included'][$j] 
									= explode (',', filter_var($v, FILTER_SANITIZE_STRING));
								$aopt['list'][$i]['included'][$j] = array_map('trim', $aopt['list'][$i]['included'][$j] );
								}
						}	
					}
					/* Now check excluded */
					if (is_array($arr['excluded']))  {		
						foreach ($arr['excluded'] as $j => $v) {
							if (auser_novalue($v)) unset($aopt['list'][$i]['excluded'][$j]);
							else 
							$aopt['list'][$i]['excluded'][$j] 
								= explode(',', filter_var($v, FILTER_SANITIZE_STRING));
							}	
						}	
					/* Now check sortby */
					unset ($aopt['list'][$i]['sortby']	);		/* unset all sort by's in case non eare set in the form */	
					if (is_array($arr['sortby']))  {
						foreach ($arr['sortby'] as $j => $v) {
						
							if (auser_novalue($v)) unset ($aopt['list'][$i]['sortby'][$j]);
							else $aopt['list'][$i]['sortby'][$j]  = $v;	
						}	
					}
					/* Now check sortdir */
					unset ($aopt['list'][$i]['sortdir']	);		/* unset all sort directions */		
					if (is_array($arr['sortdir']))  {				
						foreach ($arr['sortdir'] as $j => $v) {									
							if (!(auser_novalue($v))) $aopt['list'][$i]['sortdir'][$j] = $v;
							else $aopt['list'][$i]['sortdir'][$j] = 'SORT_ASC';
						}	
					}
				}
			}
	}
	else {
		echo '<h3>'.__('At least some display order must be specified for the list to be meaningful').'</h3>';
		return (false);
		}
	}
	
return (true);	
}

/* ---------------------------------------------------------------------*/
function amrmeta_listfields_page($listindex) {
global $aopt;
global $amr_lists;

//	echo '<h2>aopt in listfield page</h2>'; var_dump($aopt);
	
		if (!(is_numeric($listindex) )) {		
			echo '<h2>'.sprintf(__('Must be numeric  %s <= %s', AMETA_NAME),$listindex,$amr_lists['no-lists']).'</h2>'; 
			return (false);
		} else 	if (!($listindex <= $amr_lists['no-lists'])) {
			echo '<h2>'.sprintf(__('%s Must be  <= %s', AMETA_NAME),$listindex,$amr_lists['no-lists']).'</h2>'; 
			return (false);
		}
		
		if ($_POST['action'] == "save") {/* Validate the input and save */

			if (amrmeta_validate_listfields($listindex)) {
				update_option (AMETA_NAME, $aopt);
				echo '<h2>'.__('Options Updated', AMETA_NAME).'</h2>'; 
				}
			else echo '<h2>'.__('List Fields Validation failed', AMETA_NAME).'</h2>'; 	
		}

		echo ausers_update(); 

		amr_listfields( $listindex);

	}	

	/* ---------------------------------------------------------------------*/
	
	function amr_listfields( $listindex) {
	global $aopt;
	global $amr_lists;
	global $amr_nicenames;
	
//	echo '<h2>aopt</h2>'; var_dump($aopt);

	$config = &$aopt['list'][$listindex];
	
	/* sort our controlling index by the seelcted display order for ease of viewing */
	$sel = &$config['selected'];
	if (count ($sel) > 0) {	
		uasort ($sel,"auser_usort");
		$nicenames = auser_sortbyother ($amr_nicenames, $sel); /* sort for display with the selected fields first */
//		echo '<h2>nicenames?</h2>'; var_dump($amr_nicenames);
	} 
	else {
		$nicenames = $amr_nicenames;
//		echo '<h2>nicenames default?</h2>'; var_dump($amr_nicenames);
		}

	
	
	/*  List the fields for the specified list number, and for the configuration type ('selected' etc) */
		/*** would be nice to srt, but have to move away from nicenames as main index then */	
//		echo '<a name="list'.$i.'"> </a>';
		echo '<span class="button" style="float:right; padding-right: 2em;">'. amrmeta_view_link(__('View',AMETA_NAME), $listindex,$amr_lists['names'][$listindex]).'</span>';
		echo AMR_NL.'<fieldset class="widefat userlistfields">';
		echo '<legend>'.sprintf(__('Configure list %s: %s',AMETA_NAME),$listindex,$amr_lists['names'][$listindex]).'</legend>'; 
		echo '<table><thead  style="text-align:center;"><tr>'
			.AMR_NL.'<th style="text-align:right;">'.__('Field name',AMETA_NAME).'</th>'
			.AMR_NL.'<th style="width:1em;"><a href="#" title="'.__('Blank to hide, Enter a integer to select and specify column order.  Eg: 1 2 6 8', AMETA_NAME).'"> '.__('Display order',AMETA_NAME).'</a></th>'
			.AMR_NL.'<th><a href="#" title="'.__('Eg: value1,value2', AMETA_NAME).'"> '.__('Include:',AMETA_NAME).'</a></th>'
			.AMR_NL.'<th><a href="#" title="'.__('Eg: value1,value2', AMETA_NAME).'"> '.__('But Exclude:',AMETA_NAME).'</a></th>'
			.AMR_NL.'<th style="width:1em;"><a href="#" title="'
				.__('Enter integers, need not be contiguous', AMETA_NAME).'"> '.__('Sort Order:',AMETA_NAME).'</a></th>'
			.AMR_NL.'<th style="width:2em;"><a href="#" title="'.__('For sort order.  Default is ascending', AMETA_NAME).'"> '.__('Sort Descending:',AMETA_NAME).'</a></th>'
			.AMR_NL.'</tr></thead><tbody>';
	
			foreach ( $nicenames as $i => $f )		{		/* list through all the possible fields*/			
				echo AMR_NL.'<tr>';
				$l = 'l'.$listindex.'-'.$i;
				echo '<td style="text-align:right;">'.$f .'</td>';
				echo '<td><input type="text" size="1" id="'.$l.'" name="list['.$listindex.'][selected]['.$i.']"'. 
				' value="'.$config['selected'][$i] .'" /></td>';
				$l = 'i'.$listindex.'-'.$i;		
				/* don't need label - use previous lable*/			
				echo '<td><input type="text" size="20" id="'.$l.'" name="list['.$listindex.'][included]['.$i.']"';
				if (isset ($config['included'][$i])) echo ' value="'.implode(',',$config['included'][$i]) .'"';
				echo ' /></td>';
				
				$l = 'x'.$listindex.'-'.$i;
				echo '<td><input type="text" size="20" id="'.$l.'" name="list['.$listindex.'][excluded]['.$i.']"';
				if (isset ($config['excluded'][$i])) echo ' value="'.implode(',',$config['excluded'][$i]) .'"';
				echo ' /></td>';

//				echo '<select multiple="yes" size="3" id="'.$l.'" name="inc['.$listindex.']["include"]['.$i.']"'. 
//				' value="'.$config['include'][$i] .'" /></td>';

				$l = 's'.$listindex.'-'.$i;
				echo '<td>'
				.'<input type="text" size="2" id="'.$l.'" name="list['.$listindex.'][sortby]['.$i.']"';
				if (isset ($config['sortby'][$i]))  echo ' value="'.$config['sortby'][$i] .'"';
				echo ' /></td>'
				.'<td><input type="checkbox" id="sd'.$l.'" name="list['.$listindex.'][sortdir]['.$i.']"';
				 echo ' value="SORT_DESC"';
				if (isset ($config['sortdir'][$i]))  echo ' checked="checked"';
				echo ' />'
				.'</td>';

				echo '</tr>';
			}
		echo AMR_NL.'</tbody></table></fieldset>';
	return;	
	}
/* ---------------------------------------------------------------------*/	
function amrmeta_configure_link($text, $i,$name) {
	$t = '<a href="options-general.php?page=ameta-admin.php&amp;am_ulist='.$i
		.'" title="'.$name.'" >'
		.$text
//		.sprintf(__('List %s - %s', AMETA_NAME),$i,$name)
		.'</a>';
	return ($t);
}
/* ---------------------------------------------------------------------*/	
function amrmeta_view_link($text, $i,$name) {
	$t = '<a href="users.php?page=ameta-list.php?am_ulist='.$i.'" title="'.$name.'" >'
		.$text
		.'</a>';
	return ($t);
}
/* ---------------------------------------------------------------------*/	
function amrmeta_admin_header() {
	
	echo AMR_NL.'<h2>'.__('Configure User Lists:',AMETA_NAME).'</h2>'
	.AMR_NL.'<ul style="display: block; float:left; padding-right: 2em;">';	
	$t = __('Home: Number and names of User Lists', AMETA_NAME);
	echo AMR_NL.'<li><a href="options-general.php?page=ameta-admin.php" title="'.$t.'" >'.$t.'</a></li>';
	$t = __('Nice Names for User Fields', AMETA_NAME);
	echo '<li><a href="'
	.htmlentities(add_query_arg('am_page','nicenames','options-general.php?page=ameta-admin.php')).'" title="'.$t.'" >'.$t.'</a></li>';
	echo '</ul>'.AMR_NL;

	return;
}
/* ---------------------------------------------------------------------*/	

function amr_meta_reset() {
global $aopt;
global $amr_lists;
global $amr_nicenames;

	if (delete_option (AMETA_NAME)) echo '<h2>'.__('Deleting number of lists and names in database',AMETA_NAME).'</h2>';
//	else echo '<h3>'.__('Error deleting number of lists and names in database.',AMETA_NAME).'</h3>';
	if (delete_option (AMETA_NAME.'-no-lists')) echo '<h2>'.__('Deleting all lists settings in database',AMETA_NAME).'</h2>';
//	else echo '<h3>'.__('Error deleting all lists settings in database',AMETA_NAME).'</h3>';
	if (delete_option (AMETA_NAME.'-nicenames')) echo '<h2>'.__('Deleting all nice name settings in database',AMETA_NAME).'</h2>';
//	else echo '<h3>'.__('Error deleting all lists settings in database',AMETA_NAME).'</h3>';

	unset ($aopt);
	unset ($amr_lists);
	unset ($amr_nicenames);
	echo '<h2><a href="options-general.php?page=ameta-admin.php">'.__('Click to return to defaults settings',AMETA_NAME).'</a></h2>';
	die;
}

/* ---------------------------------------------------------------------*/	

function amr_meta_numlists_page() { /* the main setting spage  - num of lists and names of lists */
global $amr_lists;
/* validation will have been done */

		echo ausers_submit();?>
		<fieldset class="widefat">
			<ul style="padding: 1em;"><li>
			<label for="no-lists"><?php _e('Number of User Lists:', AMETA_NAME); ?></label>
			<input type="text" size="2" id="no-lists" 
					name="no-lists" value="<?php echo $amr_lists['no-lists']; ?>"/></li>
<?php 	if (isset ($amr_lists['names'])) {
			for ($i = 1; $i <= $amr_lists['no-lists']; $i++)	{	
					echo '<li><label for="name'.$i.'">'.__('Name of List ', AMETA_NAME).$i; 
					echo AMR_NL.'<input type="text" size="70" id="name'.$i.'" name="name['.$i.']"'
					.' value="'.$amr_lists['names'][$i].'" /></label>'
					.amrmeta_configure_link('&nbsp;&nbsp;'.__('Configure',AMETA_NAME),$i,$amr_lists['names'][$i])
					.amrmeta_view_link('&nbsp;&nbsp;'.__('View',AMETA_NAME),$i,$amr_lists['names'][$i])
					.'</li>'.AMR_NL;	
				}
			};?>
		</fieldset> 
		<?php 
}			

/* ---------------------------------------------------------------------*/
	function amrmeta_options_page() {
	global $aopt;
	global $amr_lists;
	global $amr_nicenames;

	amrmeta_acknowledgement();	
	if (isset($_REQUEST['uninstall'])  OR isset($_REQUEST['reallyuninstall']))  { /*  */
		amr_users_check_uninstall();
		return;
	}
	else {?>
		<div class="wrap" id="AMETA_NAME" style="clear: left;" >	
		<form method="post" action="<?php htmlentities($_SERVER['PHP_SELF']); ?>"><?php

		if (isset ($_POST['reset'])) {
			amr_meta_reset();
		}
		else {
			/* get our defatult or option data first */
			if (!isset ($amr_lists) ) $amr_lists =  ameta_no_lists();
			$amr_nicenames = ameta_nicenames();
			$aopt = ameta_options();
//	echo '<h2>aopt in main page</h2>'; var_dump($aopt);		
			if ($_POST['action'] == "save") { /* Validate num of lists if we have etc and save.  Need to do this early */
				if (isset($_POST["no-lists"]) ) amrmeta_validate_mainoptions();
			}
				/* Now we know the number of lists, we can do the header */

			amrmeta_admin_header(); 
					
			if (isset($_REQUEST['am_page']) and ($_REQUEST['am_page'] === 'nicenames')) amrmeta_nicenames_page();

			else 
			if (isset($_REQUEST['am_ulist']) ) {
//				echo '<h3></h3>';

				amrmeta_listfields_page($_REQUEST['am_ulist']);
			}
			else amr_meta_numlists_page(); /* else do the main header page */
			echo ausers_update();
			
		}
		?>
		</form>
		</div><?php	
	}
	
}	//end amrmetaoption_page
/* ---------------------------------------------------------------------*/
function amr_test() {
?>
<a class="reveal" href="#">More</a>
<div class="detail">
some stuff here
</div>

<?php
}
/* ---------------------------------------------------------------------*/
function amr_script() {    
?>
<script type="text/javascript">
//<![CDATA[
jQuery(document).ready( function($){
 $('.inprogress').hide();
 $('.detail').hide();
 $(".reveal").click(function(){
 $(".detail").slideToggle(300);return false;
 });
});
//]]>
</script>

<?php 	 
}
/* ---------------------------------------------------------------------*/
?>
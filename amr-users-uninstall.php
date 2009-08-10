<?php
/* This is the amr ical uninstall file */
	function amr_users_uninstall(){	
	if (function_exists ('delete_option')) {  			
		if ( $del1 = delete_option(AMETA_NAME)) {
			echo '<p>'.__('AmR user Options deleted from Database', AMETA_NAME).'</p>';
		};
	
		return ($del1);	 
	}
	else {
		echo '<p>Wordpress Function delete_option does not exist.</p>';
		return (false);	
		}
					
	}
/* -------------------------------------------------------------------------------------------------------------*/
	
	function amr_users_check_uninstall()
	{	
		?>
		<div class="wrap" > 
		<h2><?php _e('Uninstall AmR user Options', AMETA_NAME); ?></h2>
		<p><?php _e('Note this function removes the options from the database.  To completely uninstall, one should continue on to use the standard wordpress functions to deactivate the plugin and delete the files.  It is not necessary to run this separately as the uninstall will also run as part of the wordpress delete plug-in files.', AMETA_NAME);?></p>
		<p><?php _e('The function is provided here as an aid to someone who has perhaps got their wordpress install in a knot and wishes to temporarily remove the options from the database as part of their debugging or cleanup.  Consider also the use of the RESET.');?></p>
		<?php
		$nonce = $_REQUEST['_wpnonce'];
		if (! wp_verify_nonce($nonce, AMETA_NAME)) die ("Cancelled due to failed security check");

		if (isset ($_POST['reallyuninstall']))  { 
				amr_users_uninstall();
					echo '<p>'
					.__('Note: Navigating to "User List Settings" will RELOAD default options - negating the uninstall.', AMETA_NAME)
					.'</p>';
					echo '<a href="'.'../wp-admin/plugins.php">'.__('Continue to Plugin list to delete files as well',AMETA_NAME).'</a>'; 
				}
		
		if (isset ($_POST['uninstall'])) {
			$nonce = wp_create_nonce('amr_users'); /* used for security to verify that any action request comes from this plugin's forms */
			?>
			<form method="post" action="<?php  ?>">
			<?php  wp_nonce_field(AMETA_NAME); /* outputs hidden field */?>

				<fieldset id="submit">
					<input type="hidden" name="action" value="uninstalloptions" />
					<input type="submit" name="cancel" value="<?php _e('Cancel', 'amr-ical-events-list') ?>" />
					<input type="submit" name="reallyuninstall" value="<?php _e('Really Uninstall Options?', AMETA_NAME) ?>" />		
				</fieldset>
			</form>
			</div>	
			<?php 
		}
	}
	if (function_exists('register_uninstall_hook')) register_uninstall_hook(__FILE__,'amr_users_uninstall');
	else echo '<strong>Your version of wordpress does not have the uninstall hook function.  Please upgrade or delete manually</strong>';
	
	?>

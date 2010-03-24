<?php
/**
 * Uninstall functionality 
 * 
 * Removes the plugin cleanly in WP 2.7 and up
 */
	/* -----------------------------------------------------------*/

	function ameta_cache_drop ($table_name) {
	/* Create a cache table if t does not exist */
		global $wpdb;
	/* 	if the cache table does not exist, then create it . be VERY VERY CAREFUL about editing this sql */

		$sql = "DROP TABLE " . $table_name . ";";
		$results = $wpdb->query( $sql );
		return ($results);

	}
		/* -----------------------------------------------------------*/
/* This is the amr ical uninstall file */
	function amr_users_uninstall() {	
	global $wpdb;
	if (function_exists ('delete_option')) {  	

		if (delete_option ('amr-users')) echo '<h3>'.__('Deleting number of lists and names in database','amr-users').'</h3>';
		if (delete_option ('amr-users'.'-no-lists')) echo '<h3>'.__('Deleting all lists settings in database','amr-users').'</h3>';
		if (delete_option ('amr-users-nicenames')) echo '<h3>'.__('Deleting all nice name settings in database','amr-users').'</h3>';
		if (delete_option ('amr-users-cache-status')) echo '<h3>'.__('Deleting cache status in database','amr-users').'</h3>';
		if (delete_option ('amr-users-cachedlists')) echo '<h3>'.__('Deleting cached lists info in database','amr-users').'</h3>';
	}

	if (function_exists ('wp_clear_scheduled_hook')) {
		wp_clear_scheduled_hook('amr_reportcacheing');
		echo '<h3>'.__('Removed scheduled action','amr-users').'</h3>';
	}

	if (ameta_cache_drop($wpdb->prefix . "amr_reportcache")) echo '<h3>'.__('Deleted cache table','amr-users').'</h3>';
	if (ameta_cache_drop($wpdb->prefix . "amr_reportcachelogging")) echo '<h3>'.__('Deleted cache log table','amr-users').'</h3>';;		
	return (true);	 
					
	}
/* -------------------------------------------------------------------------------------------------------------*/
	
	function amr_users_check_uninstall() {	
		?>
		<div class="wrap" > 
		<h3><?php _e('Uninstall AmR user Options', 'amr-users'); ?></h3>
		<p><?php _e('This function removes the options, the scheduled caches and drops the cache tables.  To completely uninstall, one should continue on to use the standard wordpress functions to deactivate the plugin and delete the files.', 'amr-users');?></p>
		<?php
		/* do we really need these anymore - wordpress seems to do a pretyy good job of checking itself */
//		$nonce = $_REQUEST['_wpnonce'];
//		if (! wp_verify_nonce($nonce, 'amr-users')) die ("Cancelled due to failed security check");

		if (isset ($_POST['reallyuninstall']))  { 
				check_admin_referer('amr-meta');
				amr_users_uninstall();
				echo '<a href="'.'../wp-admin/plugins.php">'.__('Continue to Plugin list to delete files as well','amr-users').'</a>'; 
			}
		else {
//		if (isset ($_POST['uninstall'])) {
			$nonce = wp_create_nonce('amr-meta'); /* used for security to verify that any action request comes from this plugin's forms */
			?>
			<form method="post" action="<?php  ?>">
			<?php  wp_nonce_field('amr-meta'); /* outputs hidden field */?>

				<fieldset id="submit">
					<input type="hidden" name="action" value="uninstalloptions" />
					<input type="submit" name="cancel" value="<?php _e('Cancel', 'amr-ical-events-list') ?>" />
					<input type="submit" name="reallyuninstall" value="<?php _e('Really Uninstall Options?', 'amr-users') ?>" />		
				</fieldset>
			</form>
			</div>	
			<?php 
		}
	}

// first, check to make sure that we are indeed uninstalling
if ( !defined('WP_UNINSTALL_PLUGIN') ) {    exit();}
else amr_users_uninstall();
?>

<?php 
/*
Plugin Name: AmR users
Plugin URI: http://webdesign.anmari.com
Description: Configurable users listings by meta keys and values, comment count and post count. Includes  display, inclusion, exclusion, sorting configuration and an option to export to CSV. <a href="options-general.php?page=ameta-admin.php">Manage Settings</a>  or <a href="users.php?page=ameta-list.php">Go to Users Lists</a>.     If you found this useful, please <a href="http://webdesign.anmari.com/web-tools/donate/">Donate</a>, <a href="http://wordpress.org/extend/plugins/amr-users/">  or rate it</a>, or write a post.  
Author: Anna-marie Redpath
Version: 1.0alpha
Author URI: http://webdesign.anmari.com
*/
define( 'AMETA_BASENAME', plugin_basename( __FILE__ ) );
require_once ('ameta-includes.php');
require_once ('ameta-admin.php');
require_once ('ameta-list.php');
require_once ('ameta-stats.php');
require_once('amr-users-uninstall.php');

	/**
	Adds a link directly to the settings page from the plugin page
	*/
	function ausers_plugin_action($links, $file) {
	/* create link */
		if ( $file == AMETA_BASENAME ) {
			array_unshift(
				$links,
				sprintf( '<a href="options-general.php?page=%s">%s</a>', AMETA_BASENAME, __('Settings') )
			);
		}
	return $links;
	} // end plugin_action()
 
	add_filter('plugin_action_links', 'ausers_plugin_action', -10, 2);

/* ----------------------------------------------------------------------------------- */

function amr_meta_menu() { /* parent, page title, menu title, access level, file, function */
/* Note have to have different files, else wordpress runs all the functions together */

	$aopt = ameta_options ();
	if (isset ($aopt['list'])) {
		add_submenu_page('users.php', __('User lists', AMETA_NAME), __('User lists', AMETA_NAME), 7, 'ameta-list.php', 'amr_list_user_meta');
		}
//	if (isset ($aopt['stats'])) {
//		add_submenu_page('users.php', __('User Stats', AMETA_NAME), __('User Stats', AMETA_NAME), 7, 'ameta-stats.php', 'amr_user_meta_totals');
//		}	
	
//	add_submenu_page('users.php', 'User stats listing', 'List User stats', 8, 'ameta-stats.php', 'amr_user_meta_totals');
	$plugin_page = add_submenu_page('options-general.php', 'Configure User Listings', 'User Lists Settings', 8, 'ameta-admin.php', 'amrmeta_options_page');

	 add_action( 'admin_head-'. $plugin_page, 'ameta_admin_header' );

}

load_plugin_textdomain('AMETA_NAME', PLUGINDIR
	.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)));
add_action('admin_menu', 'amr_meta_menu');
?>
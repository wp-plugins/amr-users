<?php 
/*
Plugin Name: AmR users
Plugin URI: http://webdesign.anmari.com
Description: Lists users or posts by meta keys and values
Author: Anna-marie Redpath
Version: 1.0
Author URI: http://webdesign.anmari.com

*/
	// [listmeta]
/**
* List / manage and select users by any/all of their data (meta or otherwise)
* Retrieve  meta keys - either a unique list of the meta keys.
*
* Returned values depend on whether there is only
* one item to be returned, which be that single item type. If there is more
* than one metadata value, then it will be list of metadata values.
*
* @uses $wpdb WordPress database object for queries.

* @param string $meta_key Optional. Metadata key.
* @return mixed
 */

require_once ('ameta-includes.php');
require_once ('ameta-admin.php');
require_once ('ameta-list.php');
require_once ('ameta-stats.php');

/* ----------------------------------------------------------------------------------- */

function amr_meta_menu() { /* parent, page title, menu title, access level, file, function */
/* Note have to have different files, else wordpress runs all the functions together */

	$aopt = ameta_options ();
	if (isset ($aopt['list'])) {
		add_submenu_page('users.php', __('User lists', AMETA_NAME), __('User lists', AMETA_NAME), 8, 'ameta-list.php', 'amr_list_user_meta');
		}
	if (isset ($aopt['stats'])) {
		add_submenu_page('users.php', __('User Stats', AMETA_NAME), __('User Stats', AMETA_NAME), 8, 'ameta-stats.php', 'amr_user_meta_totals');
		}	
	
//	add_submenu_page('users.php', 'User stats listing', 'List User stats', 8, 'ameta-stats.php', 'amr_user_meta_totals');
	$plugin_page = add_submenu_page('options-general.php', 'List Meta User or Posts', 'User Lists Settings', 8, 'ameta-admin.php', 'amrmeta_options_page');

	 add_action( 'admin_head-'. $plugin_page, 'ameta_admin_header' );

}

//load_plugin_textdomain('AMETA-NAME', PLUGINDIR
//	.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)));
add_action('admin_menu', 'amr_meta_menu');
?>
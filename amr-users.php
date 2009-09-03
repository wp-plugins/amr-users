<?php 
/*
Plugin Name: AmR users
Plugin URI: http://webdesign.anmari.com/plugins/users/
Author URI: http://webdesign.anmari.com
Description: Configurable users listings by meta keys and values, comment count and post count. Includes  display, inclusion, exclusion, sorting configuration and an option to export to CSV. <a href="options-general.php?page=ameta-admin.php">Manage Settings</a>  or <a href="users.php?page=ameta-list.php">Go to Users Lists</a>.     If you found this useful, please <a href="http://webdesign.anmari.com/web-tools/donate/">Donate</a>, <a href="http://wordpress.org/extend/plugins/amr-users/">  or rate it</a>, or write a post.  
Author: Anna-marie Redpath
Version: 1.2
Text Domain: amr-users

*/
define( 'AMETA_BASENAME', plugin_basename( __FILE__ ) );

define ('AUSERS_URL', WP_PLUGIN_URL.'/amr-users');
define ('AUSERS_DIR', WP_PLUGIN_DIR.'/amr-users');

require_once ('ameta-includes.php');
require_once ('ameta-admin.php');
require_once ('ameta-list.php');
require_once('amr-users-uninstall.php');

	/**
	Adds a link directly to the settings page from the plugin page
	*/
	function ausers_plugin_action($links, $file) {
	/* create link */
		if ( $file == AMETA_BASENAME ) {
			array_unshift($links,'<a href="options-general.php?page=ameta-admin">'. __('Settings').'</a>' );
		}
	return $links;
	} // end plugin_action()
 
	add_filter('plugin_action_links', 'ausers_plugin_action', -10, 2);

/* ----------------------------------------------------------------------------------- */
function amr_load_scripts () {
	wp_enqueue_script('jquery');
}	
/* ----------------------------------------------------------------------------------- */
function add_ameta_stylesheet () {
      $myStyleUrl = AUSERS_URL.'/auserlist.css';
      $myStyleFile = AUSERS_DIR. '/auserlist.css';
        if ( file_exists($myStyleFile) ) {
            wp_register_style('auserlist', $myStyleUrl);
            wp_enqueue_style( 'auserlist', $myStyleUrl);
        }
}
/* ----------------------------------------------------------------------------------- */
function add_ameta_printstylesheet () {
      $myStyleUrl = AUSERS_URL.'/auserlist_print.css';
      $myStyleFile = AUSERS_DIR. '/auserlist_print.css';
        if ( file_exists($myStyleFile) ) {
            wp_register_style('auserlist_print', $myStyleUrl);
            wp_enqueue_style( 'auserlist_print', $myStyleUrl, false, false, 'print');
        }
}

	
/* ----------------------------------------------------------------------------------- */	
	

function amr_meta_menu() { /* parent, page title, menu title, access level, file, function */
/* Note have to have different files, else wordpress runs all the functions together */
global $amr_lists;

	$plugin_page = add_submenu_page('options-general.php', 
		'Configure User Listings', 'User Lists Settings', 8,
		'ameta-admin.php', 'amrmeta_options_page');

	add_action('admin_init-'.$plugin_page, 'amr_load_scripts' );
//	add_action('admin_print_styles-'.$plugin_page, 'add_ameta_stylesheet');
//	add_action('admin_print_styles-'.$plugin_page, 'add_ameta_printstylesheet');
//      They above caused the whole admin menu to disappear, so revert back to below.
	add_action( 'admin_head-'.$plugin_page, 'ameta_admin_header' );
	 
	$amr_lists = ameta_no_lists();  /*  Need to get this early so we can do menus */
	
	if ((isset ($amr_lists['no-lists'])) & (isset ($amr_lists['names']))) { /* add a separate menu item for each list */
		for ($i = 1; $i <= $amr_lists['no-lists']; $i++)	{	
			if (isset ($amr_lists['names'][$i])) {
				add_submenu_page('users.php',  __('User lists', AMETA_NAME), 
				$amr_lists['names'][$i], 7, 
				add_query_arg ('am_ulist',$i,'ameta-list.php'), 'amr_list_user_meta');
			}
		}
	}

	
}
/* ----------------------------------------------------------------------------------- */	
load_plugin_textdomain('AMETA_NAME', PLUGINDIR
	.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)));
add_action('admin_menu', 'amr_meta_menu');
?>
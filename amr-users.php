<?php 
/*
Plugin Name: AmR users
Plugin URI: http://webdesign.anmari.com/plugins/users/
Author URI: http://webdesign.anmari.com
Description: Configurable users listings by meta keys and values, comment count and post count. Includes  display, inclusion, exclusion, sorting configuration and an option to export to CSV. <a href="options-general.php?page=ameta-admin.php">Manage Settings</a>  or <a href="users.php?page=ameta-list.php">Go to Users Lists</a>.     If you found this useful, please <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=anmari%40anmari%2ecom&item_name=AmRUsersPlugin">Donate</a>, <a href="http://wordpress.org/extend/plugins/amr-users/">  or rate it</a>, or write a post.  
Author: Anna-marie Redpath
Version: 1.4.3
Text Domain: amr-users

*/
define( 'AMETA_BASENAME', plugin_basename( __FILE__ ) );
define ('AUSERS_URL', WP_PLUGIN_URL.'/amr-users');
define ('AUSERS_DIR', WP_PLUGIN_DIR.'/amr-users');

require_once ('ameta-includes.php');
require_once ('ameta-list.php');

if (is_admin()) {
	require_once ('ameta-admin.php');
	require_once('amr-users-uninstall.php');
	}


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
function amr_userlist($atts) {

global $aopt;

	extract(shortcode_atts(array(
		'list' => '1',
		'csv' => 'false',   /* optional add csv link  */
		'headings' => 'false'
	), $atts));

	$aopt = ameta_options(); 
	
	if ((isset($_REQUEST['csv'])) or ($csv  === 'true')) $csv = true;
	else $csv = false;
	if ((isset($_REQUEST['headings'])) or ($headings === 'true')) $headings = true;
	else $headings = false;
	if (is_admin() and ((isset($_REQUEST['list'])))) {
		$num = (int)$_REQUEST['list'];
		if (($num > 0) and (num <= $amr_lists['no-lists'])) $list= $num; 
		
	}
	
return (alist_one($list, $headings, $csv));
}


/* ----------------------------------------------------------------------------------- */	
load_plugin_textdomain(AMETA_NAME, PLUGINDIR
	.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)));
if (is_admin()) add_action('admin_menu', 'amr_meta_menu');

add_shortcode('userlist', 'amr_userlist');
?>
<?php 
/*
Plugin Name: AmR users
Plugin URI: http://webdesign.anmari.com/plugins/users/
Author URI: http://webdesign.anmari.com
Description: Configurable users listings by meta keys and values, comment count and post count. Includes  display, inclusion, exclusion, sorting configuration and an option to export to CSV. <a href="options-general.php?page=ameta-admin.php">Manage Settings</a>  or <a href="users.php?page=ameta-list.php">Go to Users Lists</a>.     If you found this useful, please <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=anmari%40anmari%2ecom&item_name=AmRUsersPlugin">Donate</a>, <a href="http://wordpress.org/extend/plugins/amr-users/">  or rate it</a>, or write a post.  
Author: Anna-marie Redpath
Version: 2.0
Text Domain: amr-users
License: GPL2

 Copyright 2009  Anna-marie RedpathE  (email : anmari@anmari.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/*

Technical Notes:

Uses Tables:
wpprefix_amr_reportcache  (id, reportid, line, csvcontent)
wpprefix_amr_reportcachelogging (id, eventtime, eventdescription)

wp options:
amr-users [list] (see config file)

amr-users-no-lists - the main options
	[rowsperpage]
	[no-lists]
	
amr-users-nicenames 	

amr-users-cache-status [reportid]
		[start]
		[end]
		[name]
		[lines]
		[peakmem]
		[headings]  (in html)

*/


if (defined('WP_PLUGIN_URL')) define ('AUSERS_URL', WP_PLUGIN_URL.'/amr-users');
else { if (defined ('BBPATH')) define ('AUSERS_URL', bb_get_option('uri').trim(str_replace(array(trim(BBPATH,"/\\"),"\\"),array("","/"),dirname(__FILE__)),' /\\').'/'); }
if (defined('WP_PLUGIN_DIR')) define ('AUSERS_DIR', WP_PLUGIN_DIR.'/amr-users');
else define ('AUSERS_DIR', rtrim(dirname(__FILE__),' /\\').'/');

define( 'AMETA_BASENAME', plugin_basename( __FILE__ ) );



require_once (AUSERS_DIR. '/ameta-includes.php');
require_once (AUSERS_DIR. '/ameta-list.php');
require_once (AUSERS_DIR. '/amr-users-widget.php');
//require_once (AUSERS_DIR. '/amr-functions.php');

amr_setDefaultTZ(); /* essential to get correct times as per wordpress install - why does wp not do this by default? Ideally should be set in php.ini, but many people may not have access */
//date_default_timezone_set(get_option('timezone_string'));  

if  ((!function_exists ('is_admin')) /* eg maybe bbpress*/ or (is_admin())) {
	require_once(AUSERS_DIR. '/ameta-admin.php');

	}



/* ----------------------------------------------------------------------------------- */
function add_ameta_stylesheet () {
      $myStyleUrl = AUSERS_URL.'/alist.css';
      $myStyleFile = AUSERS_DIR. '/alist.css';
        if ( file_exists($myStyleFile) ) {
            wp_register_style('alist', $myStyleUrl);
            wp_enqueue_style( 'alist', $myStyleUrl);
        }
}
/* ----------------------------------------------------------------------------------- */
function add_ameta_printstylesheet () {
      $myStyleUrl = AUSERS_URL.'/alist_print.css';
      $myStyleFile = AUSERS_DIR. '/alist_print.css';
        if ( file_exists($myStyleFile) ) {
            wp_register_style('alist_print', $myStyleUrl);
            wp_enqueue_style( 'alist_print', $myStyleUrl, false, false, 'print');
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

	ameta_options(); 
	
	if ((isset($_REQUEST['csv'])) or ($csv  === 'true')) $csv = true;
	else $csv = false;
	if ((isset($_REQUEST['headings'])) or ($headings === 'true')) $headings = true;
	else $headings = false;
	if (is_admin() and ((isset($_REQUEST['list'])))) {
		$num = (int)$_REQUEST['list'];
		if (($num > 0) and (num <= $amr_lists['no-lists'])) $list= $num; 
		
	}
	
return (alist_one('user',$list, $headings, $csv));
}


/* ----------------------------------------------------------------------------------- */	
	/**
	Adds a link directly to the settings page from the plugin page
	*/
	function ausers_plugin_action($links, $file) {
	/* create link */
		if ( $file == AMETA_BASENAME ) {
			array_unshift($links,'<a href="options-general.php?page=ameta-admin">'. __('Settings').'</a>' );
		}
	return $links;
	} // end plugin_action
/* ---------------------------------------------------------------*/
function amr_profile_update ($userid) { /* wordpress passes the user id as a argument on a "profile update action */
	$logcache = new adb_cache();	
	$logcache->log_cache_event(sprintf(__('Update of User %s - user reporting cache update requested','amr-users'),$userid));
	return (amr_request_cache());
}
/* ---------------------------------------------------------------*/
function ameta_schedule_regular_cacheing () { /* This should be done once on activation only or once if settings changed, or perhaps only if requested  */

	$logcache = new adb_cache();	
	$logcache->log_cache_event(__('Activated regular cacheing of lists','amr-users'));
	wp_schedule_event(time(), 'daily', 'amr_reportcacheing');   /* update once a day for now */

	}
/* ---------------------------------------------------------------*/
function ameta_cron_unschedule	() { /* This should be done once on activation only or once if settings changed, or perhaps only if requested  */

	if (function_exists ('wp_clear_scheduled_hook')) {
		wp_clear_scheduled_hook('amr_reportcacheing');
	}	
	
}

/* ---------------------------------------------------------------*/
function amr_request_cache_with_feedback ($list=null) {

	$result = amr_request_cache($list);
	if ($result) {
	?>
			<br /><?php echo $result;?><br />
			<ul><li><?php _e('Report Cache has been scheduled.','amr-users');?>
			</li><li><?php _e('If you have a lot of records, it may take a while.','amr-users'); ?>
			</li><li><?php echo au_cachelog_link(); ?>
			</li><li><?php echo au_cachestatus_link();?>
			</a></li>
			</ul>

	<?php
	}
	else {
		echo '<h2>Error:'. $result.'</h2>';  /**** */
		}
	return($result);	
	

// time()+3600 = one hour from now.	
	
}		
/* ---------------------------------------------------------------*/
function amr_request_cache ($list=null) {

global $aopt;
global $amain;

	$logcache = new adb_cache();	

	if (!empty($list)) {
		if ($logcache->cache_in_progress($logcache->reportid($list,'user'))) {
			$text = sprintf(__('Cache of %s already in progress','amr-users'),$list);
			$logcache->log_cache_event($text);
			return ($text);
		}
		elseif ($result=$logcache->cache_already_scheduled($list)) { 
				$logcache->log_cache_event($result); 
				return ($result);	
		}		
		else {
			$logcache->log_cache_event(sprintf(__('Schedule background cacheing of report: %s','amr-users'),$list));
			$args[] = $list;
			wp_schedule_single_event(time()+30, 'amr_reportcacheing', $args); /* request for now a single run of the build function */
			return(true);
		}
	}
	else {
	
		ameta_options();  

		if (empty ($aopt['list']) ) { $logcache->log_cache_event(__('Error: No stored options found.','amr-users')); return;}
		else $no_rpts = count ($aopt['list']);

		$logcache->log_cache_event(sprintf(__('Received background cache request for %s reports','amr-users'),$no_rpts));

		$returntext = '';
		foreach ($aopt['list'] as $i => $l) { 
			if ($i <= $amain['no-lists']) {
				$args[] = $i;
				if ($result=$logcache->cache_already_scheduled($i)) { 
					$text = sprintf(__('Cache of %s already in progress','amr-users'),$i);
					$logcache->log_cache_event($text);
					$returntext .= $text.', ';
				}
				else {
					wp_schedule_single_event(time(), 'amr_reportcacheing', $args); /* request for now a single run of the build function */
					unset ($args);
					$text = sprintf(__('Schedule background cacheing of report: %s','amr-users'),$i);
					$logcache->log_cache_event($text);
					$returntext .= $text.' ';
				}
			}
		}
		return ($returntext);
	}	
	
//$result = spawn_cron( time()); /* kick it off soon */ 

// time()+3600 = one hour from now.	
	
}	
/* ----------------------------------------------------------------------------------- */	
    /*
     * Enqueue style-file, if it exists.
     */

    function add_amr_stylesheet() {
        $myStyleUrl = WP_PLUGIN_URL . '/amr-users/style.css';
        $myStyleFile = WP_PLUGIN_DIR . '/amr-users/style.css';
        if ( file_exists($myStyleFile) ) {
            wp_register_style('myStyleSheets', $myStyleUrl);
            wp_enqueue_style( 'myStyleSheets');
        }
    }
/* ----------------------------------------------------------------------------------- */	

function amr_users_widget_init() {
//    register_sidebar_widget("AmR iCal Widget", "amr_ical_list_widget");
//    register_widget_control("AmR iCal Widget", "amr_ical_list_widget_control");
	register_widget('amr_users_widget');
}
/* -------------------------------------------------------------------------------------------------------------*/
add_action('wp_print_styles', 'add_amr_stylesheet');

load_plugin_textdomain('amr-users', PLUGINDIR
	.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)));
if  ((!function_exists ('is_admin')) /* eg maybe bbpress*/ or (is_admin())) {
	add_action('admin_menu', 'amr_meta_menu');
	add_filter('plugin_action_links', 'ausers_plugin_action', -10, 2);	
	}
else add_shortcode('userlist', 'amr_userlist');

add_action('amr_reportcacheing','amr_build_cache_for_one');
add_action('profile_update','amr_profile_update');
add_action('user_register','amr_profile_update');
//add_action('widgets_init', 'amr_users_widget_init');	


	/* ---------------------------------------------------------------------------------*/
	/* When the plugin is activated, create the table if necessary */
	register_activation_hook(__FILE__,'ameta_cache_enable');
	register_activation_hook(__FILE__,'ameta_cachelogging_enable');
//	if ( function_exists('register_uninstall_hook') ) register_uninstall_hook( __FILE__, 'amr_users_check_uninstall' );

//	register_activation_hook(__FILE__,'ameta_schedule_regular_cacheing');

?>
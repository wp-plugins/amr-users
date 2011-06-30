<?php 
/*
Plugin Name: amr users
Plugin URI: http://wpusersplugin.com/
Author URI: http://webdesign.anmari.com
Description: Configurable users listings by meta keys and values, comment count and post count. Includes  display, inclusion, exclusion, sorting configuration and an option to export to CSV. <a href="options-general.php?page=ameta-admin.php">Manage Settings</a>  or <a href="users.php?page=ameta-list.php">Go to Users Lists</a>.     If you found this useful, please <a href="http://wordpress.org/extend/plugins/amr-users/">  or rate it</a>, or write a post.  
Author: anmari
Version: 2.3.13
Text Domain: amr-users
License: GPL2

 Copyright 2009  anmari  (email : anmari@anmari.com)

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
define ('AUSERS_VERSION', '2.3.13');

if (defined('WP_PLUGIN_URL')) define ('AUSERS_URL', WP_PLUGIN_URL.'/amr-users');
else { if (defined ('BBPATH')) define ('AUSERS_URL', bb_get_option('uri').trim(str_replace(array(trim(BBPATH,"/\\"),"\\"),array("","/"),dirname(__FILE__)),' /\\').'/'); }
if (defined('WP_PLUGIN_DIR')) define ('AUSERS_DIR', WP_PLUGIN_DIR.'/amr-users');
else define ('AUSERS_DIR', rtrim(dirname(__FILE__),' /\\').'/');

define( 'AMETA_BASENAME', plugin_basename( __FILE__ ) );

require_once (AUSERS_DIR. '/ameta-includes.php');
require_once (AUSERS_DIR. '/ameta-list.php');
require_once (AUSERS_DIR. '/amr-users-widget.php');


amr_setDefaultTZ(); /* essential to get correct times as per wordpress install - why does wp not do this by default? Ideally should be set in php.ini, but many people may not have access */
//date_default_timezone_set(get_option('timezone_string'));  

if  ((!function_exists ('is_admin')) /* eg maybe bbpress*/ or (is_admin())) {
	require_once(AUSERS_DIR. '/ameta-admin.php');
	}
	
add_action ('after_setup_theme','ausers_load_pluggables');	

function ausers_load_pluggables() {
	require_once('ausers-pluggable.php');
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

global $amain, $aopt;

	extract(shortcode_atts(array(
		'list' => '1',
		'csv' => 'false',   /* optional add csv link  */
		'headings' => 'true',
	), $atts));

	ameta_options(); 
	
	if ((isset($_REQUEST['csv'])) or ($csv  === 'true')) 	$csv = true;
	else 													$csv = false;
	if ((isset($_REQUEST['headings'])) or ($headings === 'true')) $headings = true;
	else $headings = false;
	if (isset($_REQUEST['list'])) { /* allow admin users to test lists from the front end, bu adding list=x to the url */
		$num = (int)$_REQUEST['list'];
		if (($num > 0) and ($num <= $amain['no-lists'])) $list= $num; 
	}
	if  (isset($amain['public'][$list]) or
		(is_user_logged_in() 
		and ((current_user_can('list_users') 
		or current_user_can('edit_users')) ))) 
		return (alist_one('user',$list, $headings, $csv));
	else 
		return('<!-- '.__('Inadequate permission for non public user list','amr-users').' -->');
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
	$logcache->log_cache_event(
	'<em style="color: green;">'.sprintf(__('Update of User %s - user reporting cache update requested','amr-users'),$userid).'</em>');
	return (amr_request_cache());
}
/* ---------------------------------------------------------------*/
	function ameta_schedule_regular_cacheing ($freq) { /* This should be done once only or once if settings changed, or perhaps only if requested  */
	global $amain;	
	ameta_cron_unschedule();
	if (!($freq == 'notauto')) {
		
		wp_schedule_event(time(), $amain['cache_frequency'], 'amr_regular_reportcacheing');   /* update once a day for now */	 
		$timestamp = wp_next_scheduled( 'amr_regular_reportcacheing' ); 
		$logcache = new adb_cache(); 
		$text = __('Activated regular cacheing of lists: ','amr-users'). $freq;
		$time = date('Y-m-d H:i:s', $timestamp);
		$text2 = (__('Next cache run on user change or soon after: ','amr-users'). $time);	
		echo '<h2 class="message">'.$text .'<br />'.$text2.'</h2>';		
		$result = $logcache->log_cache_event($text);
		$result = $logcache->log_cache_event($text2);
		return (true);
	}
	return (false);
}
/* ---------------------------------------------------------------*/
	function ameta_cron_unschedule	() { /* This should be done once on activation only or once if settings changed, or perhaps only if requested  */

	if (function_exists ('wp_clear_scheduled_hook')) {
		wp_clear_scheduled_hook('amr_regular_reportcacheing');
		$logcache = new adb_cache();	
		$text = __('Deactivated any existing regular cacheing of lists','amr-users');
		$logcache->log_cache_event($text);
		echo '<h2 class="message">'.$text .'</h2>';
	}	
	
}


/* ---------------------------------------------------------------*/
	function amr_request_cache_with_feedback ($list=null) {

	$result = amr_request_cache($list);
	if (!empty($result)) {
			
	?><div id="message" class="updated fade"><p><?php echo $result;?></p></div>

			<ul><li><?php _e('Report Cache has been scheduled.','amr-users');?>
			</li><li><?php _e('If you have a lot of records, it may take a while.','amr-users'); ?>
			</li><li><?php _e('Please check the cache log - refresh for updates and do not reschedule until all the reports have completed. ','amr-users'); ?>
			</li><li><?php _e('If you think it is taking too long, problems may be occuring in the background job, such as running out of memory.  Check server logs and/or Increase wordpress\s php memory limit','amr-users'); ?>
			</li><li><?php _e('The cache status or the TPC Memory Usage plugin may be useful to assess this.','amr-users'); ?>
			</li><li><?php echo au_cachelog_link(); ?>
			</li><li><?php echo au_cachestatus_link();?>
			</a></li>
			</ul>

	<?php
	}
	else { 
		echo '<h2>Error requesting cache:'. $result.'</h2>';  /**** */
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
			return $text;
		}
		if ($text = $logcache->cache_already_scheduled($list) ) {
			$new_text = __('Report ','amr-users').$list.': '.$text;
			$logcache->log_cache_event($new_text); 
			return $new_text;
		}

		$time = time()+5;
		$text = sprintf(__('Schedule background cacheing of report: %s','amr-users'),$list);
		$logcache->log_cache_event($text);
		$args[] = $list;
		wp_schedule_single_event($time, 'amr_reportcacheing', $args); /* request for now a single run of the build function */
		return($text);

	}
	else {	
		ameta_options();  
		if (empty ($aopt['list']) ) { 
			$text = __('Error: No stored options found.','amr-users');
			$logcache->log_cache_event($text); 
			return $text;
		}
		else $no_rpts = count ($aopt['list']);

		$logcache->log_cache_event('<b>'.sprintf(__('Received background cache request for %s reports','amr-users'),$no_rpts).'</b>');

		$returntext = '';
		$time_increment = 60;
		$nexttime = time();
		foreach ($aopt['list'] as $i => $l) { 
			
			if ($i <= $amain['no-lists']) {
				$args = array('report'=>$i);
				if ($text = $logcache->cache_already_scheduled($i)) { 
					$new_text = __('All reports: ','amr-users').$text;
					$logcache->log_cache_event($new_text);
					$returntext .= $new_text.'<br />';
					return $returntext;
				}
				else {
					wp_schedule_single_event($nexttime, 'amr_reportcacheing', $args); /* request for now a single run of the build function */
					$nexttime = $nexttime + $time_increment;
					unset ($args);
					$text = sprintf(__('Schedule background cacheing of report: %s','amr-users'),$i);
					$logcache->log_cache_event($text);
					$returntext .= $text.'<br />';
				}
			}	
			
		}
		return ($returntext);
	}	
//$result = spawn_cron( time()); /* kick it off soon */ 
// time()+3600 = one hour from now.	
}	
/* ----------------------------------------------------------------------------------- */	
    function add_amr_script() {		

			wp_enqueue_script('jquery');
			wp_enqueue_script('jquery-ui-core');
//			wp_register_script('jquerytable' , 'http://tablesorter.com/jquery.tablesorter.js');
//			wp_enqueue_script('jquerytable' );	
//			wp_register_script('calltablesorter' , WP_PLUGIN_URL.'/amr-users/calltablesorter.js');
//			wp_enqueue_script('calltablesorter' );	

}
/* ----------------------------------------------------------------------------------- */	
    /*
     * Enqueue style-file, if it exists.
     */

	function add_amr_stylesheet() {
	
	$amain = get_option('amr-users-no-lists');
	if (isset($amain['do_not_use_css']) and ($amain['do_not_use_css'])) return;
	
    $myStyleUrl = WP_PLUGIN_URL . '/amr-users/style.css';
    $myStyleFile = WP_PLUGIN_DIR . '/amr-users/style.css';
    if ( file_exists($myStyleFile) ) {
            wp_register_style('amrusers-StyleSheets', $myStyleUrl);
            wp_enqueue_style( 'amrusers-StyleSheets');
        }
    }
/* ----------------------------------------------------------------------------------- */	

	function amr_users_widget_init() {
//    register_sidebar_widget("AmR iCal Widget", "amr_ical_list_widget");
//    register_widget_control("AmR iCal Widget", "amr_ical_list_widget_control");
	register_widget('amr_users_widget');
}

/* -------------------------------------------------------------------------------------------------------------*/
	function amr_users_filter_csv_line( $csv_line ) {
#
   return preg_replace( '@\r\n@Usi', ' ', $csv_line );
#
}
/* -------------------------------------------------------------------------------------------------------------*/
	function amr_shutdown () {
	
	if ($error = error_get_last()) {
        if (isset($error['type']) && ($error['type'] == E_ERROR || $error['type'] == E_PARSE || $error['type'] == E_COMPILE_ERROR)) {
            ob_end_clean();
 
            if (!headers_sent()) {
                header('HTTP/1.1 500 Internal Server Error');
            }
 
            echo '<h1>Bad stuff happened?</h1>';
            echo '<p>But that is okay</p>';
            echo '<code>' . print_r($error, true) . '</code>';
			error_log($error);
        }
    }

	}
/* -------------------------------------------------------------------------------------------------------------*/	
	function amr_users_deactivation () {
	if (function_exists ('wp_clear_scheduled_hook')) {
		wp_clear_scheduled_hook('amr_regular_reportcacheing');
		// outputs at bad  time.   echo '<h3>'.__('Removed scheduled action','amr-users').'</h3>';
	}
	}	
/* -------------------------------------------------------------------------------------------------------------*/

	load_plugin_textdomain('amr-users', PLUGINDIR
		.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)));
	if  ((!function_exists ('is_admin')) /* eg maybe bbpress*/ or (is_admin())) {
		add_action('admin_menu', 'amr_meta_menu');
		add_filter('plugin_action_links', 'ausers_plugin_action', -10, 2);		
		}
	else add_shortcode('userlist', 'amr_userlist');
	add_action('wp_print_styles', 'add_amr_stylesheet');
//	add_action('wp_print_scripts', 'add_amr_script');
	add_action('amr_regular_reportcacheing','amr_request_cache');
	add_action('amr_reportcacheing','amr_build_cache_for_one');  /* the singel job option */
	add_action('profile_update','amr_profile_update');
	add_action('user_register','amr_profile_update');
	add_action('widgets_init', 'amr_users_widget_init');	
	add_filter( 'amr_users_csv_line', 'amr_users_filter_csv_line' );

	/* ---------------------------------------------------------------------------------*/
	/* When the plugin is activated, create the table if necessary */
	register_activation_hook(__FILE__,'ameta_cache_enable');
	register_activation_hook(__FILE__,'ameta_cachelogging_enable');
	if ( function_exists('register_uninstall_hook') ) register_uninstall_hook( __FILE__, 'amr_users_check_uninstall' );

	/* The deactivation hook is executed when the plugin is deactivated */
    register_deactivation_hook(__FILE__,'amr_users_deactivation');
	/* ---------------------------------------------------------------------------------*/


?>
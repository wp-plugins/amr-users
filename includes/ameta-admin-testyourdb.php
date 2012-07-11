<?php
function amr_about_users () {
global $wpdb,$charset_collate;	
global $ausersadminurl;		
	$wpdb->show_errors();
	if (is_multisite() and is_network_admin()) {
		$where = '';
		$wheremeta = '';
		_e('This is a multi-site network.  All users shown here.');
		echo '<br />';
	}
	else { $where = ' INNER JOIN ' . $wpdb->usermeta .  
       ' ON      ' . $wpdb->users . '.ID = ' . $wpdb->usermeta . '.user_id 
        WHERE   ' . $wpdb->usermeta .'.meta_key =\'' . $wpdb->prefix . 'capabilities\'' ;

		_e('This website with blog_id='.$GLOBALS['blog_id'].'and prefix='.$wpdb->prefix .' has:', 'amr-users');
	}	
	echo '<ul>';
	echo '<li>';
	printf(__('Plugin version: %s','amr-users'),AUSERS_VERSION);
		echo '</li>';
	echo '<li>';
		printf(__('Php version: %s ', 'amr-users'),phpversion()); 
	echo '</li>';
	echo '<li>';
		printf(__('Wp version: %s ', 'amr-users'),get_bloginfo( 'version', 'display' )); 
	echo '</li>';
	echo '<li>';
		printf(__('Charset: %s ', 'amr-users'),get_bloginfo( 'charset', 'display' )); 
	echo '</li>';
	if (!empty($charset_collate)) { 
	echo '<li>';
		printf(__('Collation: %s ', 'amr-users'),$charset_collate); 
	echo '</li>';
	}
	if (is_multisite() and is_network_admin()) {
		echo '<li>';
			
		$sql = "SELECT count(*) FROM " . $wpdb->blogs;	

		$results = $wpdb->get_col( $sql, 0 );	
		foreach ($results as $i => $total) {
				printf(__('%s sites', 'amr-users'),number_format($total,0,'.',','));
		}
		echo '</li>';
	}
	echo '<li>';
		
	$sql = "SELECT count(*) FROM " . $wpdb->users.$where;	

	$results = $wpdb->get_col( $sql, 0 );	
	foreach ($results as $i => $total) {
			printf(__('%s users', 'amr-users'),number_format($total,0,'.',','));
	}
	echo '</li>';	
	echo '<li>';

	if (!empty($where))  // then we already know we are in a sub blog
		$wheremeta = " WHERE ".$wpdb->usermeta.".user_id IN ".
	"(SELECT distinct user_id FROM ".$wpdb->usermeta." WHERE ".$wpdb->usermeta .".meta_key ='" . $wpdb->prefix . "capabilities')";
	
	$sql = "SELECT count(*) FROM $wpdb->usermeta ".$wheremeta; 
	$results2 = $wpdb->get_col( $sql, 0 );	

	foreach ($results2 as $i => $total) {
		printf(__('%s user meta records.', 'amr-users'),number_format($total,0,'.',',')); 
	}
	echo '</li>';
	echo '<li>';
	$sql = "SELECT meta_key, count(*) FROM $wpdb->usermeta ".$wheremeta." GROUP BY meta_key ORDER BY meta_key ASC "; 
	$results = $wpdb->get_col( $sql, 0 );	
	$total = count($results);
	printf(__('%s different user meta keys.', 'amr-users'),number_format($total,0,'.',',')); 
	echo '</li>';
	echo '<li>';
		printf(__('Wordpress Memory limit: %s ', 'amr-users'),WP_MEMORY_LIMIT); 
	echo '</li>';
	echo '<li>';
		printf(__('Php Memory Limit: %s ', 'amr-users'),ini_get('memory_limit')); 
	echo '</li>';
	echo '</ul>';
	echo '<p>';
		_e('Compare the memory limits to the memory stats shown in your cache status', 'amr-users');
	echo '<a href="'.$ausersadminurl.'?page=ameta-admin-cache-settings.php&tab=status'.'"> '.__('go').'</a>';
	echo '</p>';	

	echo '<p>';
	_e('If the user and user meta numbers are large, you may experience problems with large lists.', 'amr-users');
	echo '<br /><br />';	

	_e('If this happens, try: increasing php memory, clean up users (get rid of the spammy users), clean up usermeta.  You may have records from inactive plugins.', 'amr-users');
	echo '<input id="submit" style= "float:right;" class="button-secondary subsubsub" name="testqueries" type="submit" value="';
	_e('Run test queries', 'amr-users'); 
	echo '" /><br /><br />';
	if (isset($_REQUEST['testqueries'])) {
		echo '<hr /><b>'.__('Running some test queries:', 'amr-users').'</b>';
		if (!defined('WP_DEBUG')) define('WP_DEBUG', true);
		$_REQUEST['mem'] = true;  // to make track progress work
		track_progress('Test full user query:');
		$sql = "SELECT * FROM $wpdb->users".$where;
		$results = $wpdb->get_col( $sql, 0 );	
		track_progress('After users - how was it?');
		track_progress('Test user meta query:');
		$sql = "SELECT * FROM $wpdb->usermeta".$wheremeta;
		$results = $wpdb->get_col( $sql, 0 );	
		track_progress('After usermeta - how was it?');
		echo '<hr /><b>'.__('If these queries completed, the "fetch users directly" method should work, even if the "wp_query" method fails.', 'amr-users').__('See "How to fetch data" in the general settings.', 'amr-users').'</b>';
	}
}
/* ----------------------------------------------------------------------------------- */
function amr_test_your_db() { 
	amr_mimic_meta_box('about', 'About your user database','amr_about_users', false);
}
/* ---------------------------------------------------------------------*/	
function amr_meta_test_your_db_page() { /* the main setting spage  - num of lists and names of lists */
	amr_meta_admin_headings ($plugin_page=''); // does the nonce check etc
	amr_test_your_db();
}
/* ---------------------------------------------------------------------*/	
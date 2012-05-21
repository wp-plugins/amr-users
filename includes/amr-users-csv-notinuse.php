<?php
/* ---------------------------------------------------------------------------------- */
function amr_list_csv_files () { // may not need, Not in use
	// Define the folder to clean
	// (keep trailing slashes)
	$Folder  = amr_users_get_csv_path();
	$url = amr_users_get_log_url();
	$files = scandir($Folder); 
	
	// Find all files of the given file type
	foreach ( $files as $i=>$Filename) {
		if (!stristr($Filename,'.csv'))   // if not  atxt file, then skip  ?? OR .txt - NEEDS MOD
			unset($files[$i]);
	}
	
	echo PHP_EOL.'<div class="wrap">'.PHP_EOL.'<div id="icon-options-general" class="icon32"><br/></div><h2>';
	 _e('List user log files','amr-users'); 
	echo '</h2>'.PHP_EOL.'<div class="postbox" style="padding: 1em;"><!-- post box-->'.PHP_EOL;
	
	if (count($files) > 0) {
		echo '<form method="post" action="'. esc_url($_SERVER['PHP_SELF']).'?page=amr_user_templates_settings_page">';
		wp_nonce_field( 'amr-users','amr-users' ); 
		echo '<input style="clear:both; margin: 1em; float:right;" class="button-primary" type="submit" value="'
		.__('Clear csvfiles','amr-users').
		'" name="deletecsvfiles"/></form>';
	}	
	echo '<a href="'.remove_query_arg('viewcsvfiles').'">'.__('back').'</a><br/>';

	// Find all files of the given file type
	foreach ( $files as $Filename) {
		echo '<br/><a target="_blank" href="'.$url.'/'.$Filename.'">'.$Filename.'</a><br/>';
	}
	
	echo PHP_EOL.'</div><!-- end post box -->'
	.PHP_EOL.'</div><!-- end wrap -->';
}
/* ---------------------------------------------------------------------------------- */
function amr_users_delete_old_csv_files ($expire_days=31) { // Not in use - do we really need - could just overwrite one per report
	// Define the folder to clean
	// (keep trailing slashes)
	$Folder  = amr_users_get_csv_path();
	// Filetypes to check (you can also use *.*)
	$fileTypes      = '*.csv';	 
	// Here you can define after how many
	// days the files should get deleted
	$expire_time    = $expire_days * 60*60*24; // 24 hrs * 60 mins *60 sec
	
//	$files = glob($Folder . $fileTypes);
	$files = scandir($Folder);
	 
	// Find all files of the given file type
	foreach ( $files as $Filename) {
		if (stristr($Filename,'.csv') or stristr($Filename,'.txt'))  { // if not  atxt file, then skip
			if (!stristr($Filename,'user_list'))
		    // Read file creation time
		    $FileTime = filectime($Folder.'/'.$Filename);  // need the full file name		 
		    // Calculate file age in seconds
		    if (!empty($FileTime)) 
				$FileAge = time() - $FileTime; 		
			else continue;		
		    // Is the file older than the given time span?
		    if ($FileAge > ($expire_time)){  
		        unlink($Folder.'/'.$Filename);
		    } 
		}
	}
}
/* ---------------------------------------------------------------------------------- */ 

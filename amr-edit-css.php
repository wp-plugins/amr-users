<?php 

function amr_upload_dir( $upload_subdir ) { /* mostly wordpress code, */
	global $switched;
	$siteurl = get_option( 'siteurl' );
	$upload_path = get_option( 'upload_path' );
	$upload_path = trim($upload_path);
	$main_override = defined( 'MULTISITE' ) && is_main_site();
	if ( empty($upload_path) ) {
		$dir = WP_CONTENT_DIR . '/uploads';
	} else {
		$dir = $upload_path;
		if ( 'wp-content/uploads' == $upload_path ) {
			$dir = WP_CONTENT_DIR . '/uploads';
		} elseif ( 0 !== strpos($dir, ABSPATH) ) {
			// $dir is absolute, $upload_path is (maybe) relative to ABSPATH
			$dir = path_join( ABSPATH, $dir );
		}
	}
	if ( !$url = get_option( 'upload_url_path' ) ) {
		if ( empty($upload_path) || ( 'wp-content/uploads' == $upload_path ) || ( $upload_path == $dir ) )
			$url = WP_CONTENT_URL . '/uploads';
		else
			$url = trailingslashit( $siteurl ) . $upload_path;
	}
	if ( defined('UPLOADS') && !$main_override && ( !isset( $switched ) || $switched === false ) ) {
		$dir = ABSPATH . UPLOADS;
		$url = trailingslashit( $siteurl ) . UPLOADS;
	}
	if ( is_multisite() && !$main_override && ( !isset( $switched ) || $switched === false ) ) {
		if ( defined( 'BLOGUPLOADDIR' ) )
			$dir = untrailingslashit(BLOGUPLOADDIR);
		$url = str_replace( UPLOADS, 'files', $url );
	}	
	$bdir = $dir;
	$burl = $url;
	$subdir = '';
	$dir .= $subdir;
	$url .= $subdir;

	$uploads = apply_filters( 'upload_dir', array( 'path' => $dir, 'url' => $url, 'subdir' => $subdir, 'basedir' => $bdir, 'baseurl' => $burl, 'error' => false ) );

	// Make sure we have an uploads dir
	if ( ! wp_mkdir_p( $uploads['path'] ) ) {
		$message = sprintf( __( 'Unable to create directory %s. Is its parent directory writable by the server?' ), $uploads['path'] );
		return array( 'error' => $message );
	}

	return $uploads;
}

/* --------------------------------------------------------------------------------------------------- */
$file = validate_file_to_edit($file, $plugin_files);
$real_file = WP_PLUGIN_DIR . '/' . $file;
switch ( $action ) {

case 'update':

	check_admin_referer('edit-plugin_' . $file);

	$newcontent = stripslashes($_POST['newcontent']);
	if ( is_writeable($real_file) ) {
		$f = fopen($real_file, 'w+');
		fwrite($f, $newcontent);
		fclose($f);
break;

default:	

	if ( ! is_file($real_file) ) {
		wp_die(sprintf('<p>%s</p>', __('No such file exists! Double check the name and try again.')));
	} else {
	
	$content = file_get_contents( $real_file );
	$content = htmlspecialchars( $content );
?>	
<div class="wrap">
<?php screen_icon(); ?>
<h2><?php echo esc_html( $title ); ?></h2>

<div class="fileedit-sub">
<div class="alignleft">
<big><?php
	if ( is_writeable($real_file) )
			echo sprintf(__('Editing <strong>%s</strong>'), $file);
		else
			echo sprintf(__('Browsing <strong>%s</strong>'), $file);
?></big>
</div>
<div class="alignright">
	<form action="css-editor.php" method="post">
		<strong><label for="cssfile"><?php _e('Select file to edit:'); ?> </label></strong>
		<select name="cssfile" id="cssfile">
<?php
	foreach ( $cssfiles as $cssfile_key => $cssfile ) {
		$cssfile_name = $cssfile['Name'];
		if ( $cssfile_key == $cssfile )
			$selected = " selected='selected'";
		else
			$selected = '';
		$cssfile_name = esc_attr($cssfile_name);
		$cssfile_key = esc_attr($cssfile_key);
		echo "\n\t<option value=\"$cssfile_key\" $selected>$cssfile_name</option>";
	}
?>
		</select>
		<input type="submit" name="Submit" value="<?php esc_attr_e('Select') ?>" class="button" />
	</form>
</div>
<br class="clear" />
</div>
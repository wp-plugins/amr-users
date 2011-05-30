<?php 

/* -------------------------------------------------------------------------------------------------------------*/
if (!function_exists('ausers_format_user_nicename')) {
	function ausers_format_user_nicename($v, $u) {
		if (!empty($u->user_url))
			return ('<a href="'.$u->user_url.'">'.$v.'</a>');
		else return ($v);	
	}
}
/* -------------------------------------------------------------------------------------------------------------*/
if (!function_exists('ausers_format_ausers_last_login')) {
	function ausers_format_ausers_last_login($v, $u) {
		if (!empty($v))
			return (substr($v, 0, 16)); //2011-05-30-11:03:02 EST Australia/Sydney
		else return ('');	
	}
}
/* -------------------------------------------------------------------------------------------------------------*/
if (!function_exists('amr_format_user_cell')) {
function amr_format_user_cell($i, $v, $u) {  // thefield, the value, the user object
/* receive the key and the value and format accordingly - wordpress has a similar user function function - should we use that? */
	if (function_exists('ausers_format_'.$i) ) {
		return (call_user_func('ausers_format_'.$i, $v, $u));
	}
	else

	switch ($i) {
		case 'user_email': {
			return('<a href="mailto:'.$v.'">'.$v.'</a>');
			break;
		}
		case 'user_login': {
			if (is_object($u) and isset ($u->ID) ) 
			return('<a href="'.site_url().'/wp-admin/user-edit.php?user_id='.$u->ID.'">'.$v.'</a>');
// do as filter maybe?			
//			return('<a href="'.$u->user_url.'">'.$v.'</a>');
			break;
			
		}
		case 'post_count': {
			if (empty($v)) return( ' ');
			else if (is_object($u) and isset ($u->ID) ) 
				return('<a href="'.add_query_arg('author',$u->ID, site_url()).'">'.$v.'</a>');
			break;
		}
		case 'user_url': {
			return('<a href="'.$v.'">'.$v.'</a>');
			break;
		}
		case 'comment_count': {  /* if they have wp stats plugin enabled */
			if ((empty($v)) or (!($stats_url = get_option('stats_url')))) return($v);
			else return( '<a href="'.add_query_arg('stats_author',$u->user_login, $stats_url).'">'.$v.'</a>');
			break;
		}
		case 'description': {  
			return((nl2br($v))); break;
		}
		default: {
			if (isset ($v)) return($v);
			else return(' ');
		}
	}
	return('');
}
}
/* -------------------------------------------------------------------------------------------------------------*/
if (!function_exists('amr_do_cell')) {
	function amr_do_cell($i, $k, $openbracket,$closebracket) {
		return ($openbracket.$i.$closebracket);
	}
}
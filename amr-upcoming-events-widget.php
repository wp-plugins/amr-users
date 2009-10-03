<?php
//ini_set('display_errors', 1);
//error_reporting(E_ALL);
/*
Description: Display a sweet, concise list of events from iCal sources, using a list type from the amr iCal plugin <a href="options-general.php?page=manage_amr_ical">Manage Settings Page</a> and  <a href="widgets.php">Manage Widget</a> 

*/

/* ============================================================================================== */
function amr_ical_list_widget($args)  {	
	global $amrW;
	global $amr_options;
	global $amr_limits;
	global $amrwidget_options;
	global $amr_listtype;
//	global $amr_ical_widgetlimit;  /* we are using same variable for widget and page, but it gets picked up from the options table as needed, so should be ok */
	$amrW = 'w';

	extract( $args );

	$amrwidget_options = amr_getset_widgetoptions();

	$amr_listtype  = (empty($amrwidget_options["listtype"])) ? $amr_listtype : $amrwidget_options["listtype"]; /*  must be before get params */
	
	$urls =	amr_get_params (); 
	
	$title = (empty($amrwidget_options["title"])) ? null : $amrwidget_options["title"];
	$urls  = (empty($amrwidget_options["urls"])) ? null : (explode(',', $amrwidget_options["urls"]));
	$urls = array_map ('trim', $urls);


		
//	$amr_limits = $amr_options[$amr_listtype]['limit'];  /* get the limits for the listtype specified for the widget */
	foreach ($amr_options[$amr_listtype]['limit'] as $i=> $l) $amr_limits[$i] = $l;  /* override any other limits with the widget limits */
		
	if (!empty($amrwidget_options["limit"])) $amr_limits['events'] = $amrwidget_options["limit"] ; /* overwrite with the number of events specified in the widget */

	$moreurl = (empty($amrwidget_options['moreurl'])) ? null : $amrwidget_options['moreurl'] ;
	if (isset ($moreurl)) $title = '<a href= "'.$moreurl.'">'.$title.'</a>';
	
	If (ICAL_EVENTS_DEBUG) {echo '<br><br> urls '; print_r($urls);}	
	
	$content = process_icalspec($urls, '0');
	//output...
	echo $before_widget;
	echo $before_title . $title . $after_title ;
	echo $content;
	echo $after_widget; 
}
/* -------------------------------------------------------------------------------------------------------------*/
function amr_ical_widget_init()
{
    register_sidebar_widget("AmR iCal Widget", "amr_ical_list_widget");
    register_widget_control("AmR iCal Widget", "amr_ical_list_widget_control");
}
/* ------------------------------------------------------------------------------------------------------ */
	function amr_getset_widgetoptions ($reset=false)

	/* get the options from wordpress if in wordpress
	if no options, then set defaults */
	{		
		if (function_exists ('get_option')) {
			if	($amrwidget_options = get_option('amr-ical-widget')) return ($amrwidget_options);
			else if ($amrwidget_options = get_option('AmRiCalWidget')) return ($amrwidget_options);
			else $amrwidget_options = amrwidget_defaults();	
		}
		else $amrwidget_options = amrwidget_defaults();		
		return ($amrwidget_options);
	}

/* -------------------------------------------------------------------------------------------*/

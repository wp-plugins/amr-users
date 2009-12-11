=== AmR Users ===
Contributors: Anmari
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=anmari%40anmari%2ecom&item_name=AmRUsersPlugin
Tags: user, users, reports, lists, stats, statistics, members, membership, authors, subscribers, post counts, comment counts, csv, export
Requires at least: 2.7 
Tested up to: 2.9-rare
Version: 1.4.5
Stable tag: trunk

== Description ==
Configurable user listings by meta keys and values, comment count and post count. Includes display, inclusion, exclusion, sorting configuration and an option to export to CSV.  Now also includes an option to add a list on the public side as a shortcode.  You must ensure that you suitably define the fields, lists and protection for the shortcode to manage your users privacy.

The admin settings area is aimed at an administrator, who will design and setup the reports for less skilled editors or website managers.  Some lists are provided pre-configured to get you going - you can add more or change these.

The first field of each listing will offer a link to edit that user.
The fields you see listed will vary depending on the plugins that you have in use, and on the meta data that the plugins may have created.  In the Screenshots you will some data from subscribe 2, register plus and your members plugins.

If you have very large numbers of users, the post count and particularly the comment count (no wordpress cacheing) listings may be slow.  If very slow, please contact me and we can consider ways to cache the data.  If you wanted to list this data on the front end, the performance would have to be improved.  for now it is assumed to be admin/editor use only.

If anyone would like to offer some translations for other languages, please do.  The Code Styling Localisation Plugin is very useful for this.

NOTE:  Requires PHP > 5.2, due to use of filter var for validation.

== FAQ: == 
Please see the posts at http://webdesign.anmari.com/category/plugins/user-lists/

= More to come =
Please add a comment to the site about any features you would like to see - a contribution towards that would also help! Planned features:
*   possibly allow dropdown selection for those fields that do not have too many values - maybe configure threshold
*   add statistic reports with groupings and totals
*   make skill level of user list access configurable


== Changelog ==
= Version 1.4.5 =
*   Allowed for less than wordpress 2.8 for non essential plugin news in admin home
*   Allowed for situation where user has not configured 'nicenames' at all

= Version 1.4.4 =
*   Added exclusion of deprecated duplicate fields (eg: wordpress currently returns both user_description and description, regardless of what is in the database. Only the latter is required now).
*   0 post counts and comments will not be listed
*   if plugin wp-stats is enabled and a stats page has been specified in it's settings, then any non zero comment counts will link to a list of comments by the author (Note this only applies to registered users)
*   Fixed problem where updated nice names where not being correctly accessed.

= Version 1.4.3 =
*   Fixed addition of extra lists - now uses prev list as default entries.  NB Must configure and update before viewing.
*   Added RSS feed links to highlight any plugin related news

= Version 1.4.2 =
*   Hmm now using get_bloginfo('wpurl') not get_option!! - otherwise if no WP_SITEURL defined in config, admin loses colours!

= Version 1.4.1 =
*   Defined WP_SITEURL if not defined, using get_bloginfo('wp-url') (not 'siteurl') so both wordpress relocated, and standard wordpress will work, and it will be faster than calling bloginfo.

= Version 1.4 =
*   Changed get_bloginfo('url') to get_bloginfo('siteurl') for those that have wordpress relocated
*   Put the CSV export back - got temporarily lost due to adding possibility of not having it on the front end 
*   Thanks to kiwicam for quick pickup and detailed specific response!

= Version 1.3 =
*   Changed WP_SITEURL to get_bloginfo('url') for those that do not have WP_SITEUEL defined (was in edit link)
*   Added column titles to the csv file
*   Made some updates as suggested by http://planetozh.com/blog/2009/09/wordpress-plugin-competition-2009-43-reviews/.  Note that as we are not running any DB update queries, some of the comments are not strictly relevant.
*   added ability to access a list via shortcode. Your Themes table styling will apply.
*   improved ability to select data - can include only blanks, or exclude if blank.
*   the following fields will automaically be hyperlinked as follows:
*       email address - mailto link
*       user login - edit user link 
*       post count - author archive link

= Version 1.2 =
*   Fixed bug that had crept in where some aspects were not updating in the admin pages
*   Fixed problem with multiple exclusions and inclusions
*   Changed empty to check to null check as 0 is considered empty, but may be a valid inclusion or exclusion value.
*   Changed admin area to separate pages in attempt to simplify the setup.

= Version 1.1 =
*   Allowed for situation where there may be no user meta data for a user record.
*   Tested on 2.8.4

= Version 1.1 =
*   Fixed an inappropriate nonce security check which caused a plugin conflict.

= Version 1 =
*   Initial Listing

== Installation ==

From wordpress admin folder, click add new, search for "amr user", select and install.

OR 

1.  Download and Unzip the folder into your wordpress plugins folder.
2.  Activate the plugin through the 'Plugins' menu in WordPress
3.  Default listings available in the user submenu
4.  Configure or add listings the settings panel.
5.  For shortcode, create page or post, enter in text [userlist list=n].  Optional extras: csv=true headings=true


== Frequently Asked Questions ==

See author site: http://webdesign.anmari.com/category/plugins/user-lists/

== Screenshots ==

1. Default list 1
2. Default list 2
3. Default list 3
4. Admin 1
5. Admin 2
6. Admin 3
7. CSV Export
8. CSV Imported again!
9. Shortcode simple
10. Shortcode with extras



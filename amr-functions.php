<?php
/* This holds common amr functions file - it may  be in several plugins  */


/* ---------------------------------------------------------------------*/
if (!function_exists('amr_feed')) {

	function amr_feed($uri, $num=3, $text='Recent News',$icon="http://webdesign.anmari.com/images/amrusers-rss.png") {

	if (!function_exists (fetch_feed)) { ?>
		<h3><a href="<?php echo $uri;?>">
			<?php echo $text;?></a><img src="<?php echo $icon; ?>" alt="Rss icon" style="vertical-align:middle;" />
		</h3><?php	
		return (false);
		}
	if (!empty($text)) {?>
	<div>
	<h3><?php _e($text);?><a href="<?php echo $uri; ?>" title="<?php echo $text; ?>" >
	</a><img src="<?php echo $icon;?>"  alt="Rss icon" style="vertical-align:middle;"/></h3><?php
	}
	// Get RSS Feed(s)
	include_once(ABSPATH . WPINC . '/feed.php');
	include_once(ABSPATH . WPINC . '/formatting.php');
	// Get a SimplePie feed object from the specified feed source.
	$rss = fetch_feed($uri);

	// Figure out how many total items there are, but limit it to 5. 
	$maxitems = $rss->get_item_quantity($num); 

	// Build an array of all the items, starting with element 0 (first element).
	$rss_items = $rss->get_items(0, $maxitems); 
	?>

	<ul class="rss_widget">
	    <?php if ($maxitems == 0) echo '<li>'.__('No items').'</li>';
	    else
	    // Loop through each feed item and display each item as a hyperlink.
	    foreach ( $rss_items as $item ) :  ?>
	    <li> 
	        <a href='<?php echo $item->get_permalink(); ?>'
	        title='	<?php echo $item->get_date('j F 2009'); ?>'>
	        <?php echo $item->get_title(); ?></a> 
			<?php echo balanceTags(substr($item->get_description(),0, 80)).'...'; ?>
	    </li>
	    <?php endforeach; ?>
		<li>...</li>
	</ul>
	</div>
	<?php }

	}

?>
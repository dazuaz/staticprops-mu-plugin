<?php
/**
 * Enables preview mode for Next 9.3 +
 */
add_filter('preview_post_link', 'staticpropst_preview_fix');

function staticpropst_preview_fix()
{
	$slug = basename(get_permalink());
	$secret = urlencode(getenv('AUTH_KEY'));
	$nonce = wp_create_nonce('wp_rest');
	$wpUrl = getenv('WP_URL');
	return "$wpUrl/api/preview?secret=$secret&slug=$slug&nonce=$nonce";
}

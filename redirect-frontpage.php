<?php
/**
 *
 * @see https://stackoverflow.com/a/768472/1469799
 *
 * @param $url
 * @param bool $permanent
 */
function staticprops_headless_mode_redirect($url, $permanent = false)
{
	header('Location: ' . $url, true, $permanent ? 301 : 302);
	exit();
}

/**
 * Based on https://gist.github.com/jasonbahl/5dd6c046cd5a5d39bda9eaaf7e32a09d
 */
add_action('parse_request', 'staticprops_disable_front_end', 99);

function staticprops_disable_front_end()
{
	// if (current_user_can('edit_posts')) {
	// 	return;
	// }

	global $wp;
	/**
	 * If the request is not part of a CRON, REST Request, GraphQL Request or Admin request,
	 * redirect to the login page
	 */
	if (
		!defined('DOING_CRON') &&
		!defined('REST_REQUEST') &&
		!is_admin() &&
		(empty($wp->query_vars['rest_oauth1']) &&
			!defined('GRAPHQL_HTTP_REQUEST'))
	) {
		// adds the rest of the request to the new URL.
		// $new_url = trailingslashit( HEADLESS_MODE_CLIENT_URL ) . $wp->request;

		// $new_url = trailingslashit( HEADLESS_MODE_CLIENT_URL ) . $wp->request;
		// $home = trailingslashit(get_home_path()) . $wp->request .

		// TODO can integrate better with preview mode
		$new_url = wp_login_url();
		staticprops_headless_mode_redirect($new_url, true);
		exit();
	}
}

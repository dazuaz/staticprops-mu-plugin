<?php

/**
 * Plugin Name: Staticprops.com
 * Description: This plugin facilitates wordpress integration with SSG (Static Site Generators).
 * Author: Staticprops.com
 * Author URI: https://github.com/dazuaz/staticprops
 * License: GPL V2
 * Text Domain: staticprops
 * Version: 0.1.1
 */

/**
 * API route that genarates all paths avaliable.
 * Usefull for generating all avaliable paths
 *
 * @param array $data Options for the function.
 * @return string|null Post title for the latest, * or null if none.
 */
if (!defined('ABSPATH')) {
	exit();
}
function custom_font()
{
	echo "
	<link rel='stylesheet' href='https://rsms.me/inter/inter.css' />
	";
}
add_action('admin_head', 'custom_font');
/**
 * Disallow indexing of your site
 */
add_action('pre_option_blog_public', '__return_zero');

global $staticprops;

require_once __DIR__ . '/custom-apis/site-api.php';
require_once __DIR__ . '/custom-apis/paths-api.php';

/**
 * Function to register our new routes from the controller.
 */
function register_site_api_controller()
{
	$site = new SiteApi();
	$paths = new PathsApi();
	$site->register_routes();
	$paths->register_routes();
}
add_action('rest_api_init', 'register_site_api_controller');

/**
 * Change post preview link
 * This allows to integrate with the front and pass preview params
 *
 * TODO https://github.com/WordPress/gutenberg/issues/13998
 * @param WP_post $post The post in preview
 * @return string $preview_link the modified link
 */

// function the_preview_fix(WP_Post $post) {
// 		$slug = basename(get_permalink());
// 		$secret = getenv('FRONTEND_PREVIEW_SECRET');
//     $user_url = "https://observatorio.now.sh";
//     $user_preview = "api/preview";
//     $preview_link = "$user_url/$user_preview?secret=$secret&slug=$slug";
//     // TODO The best approach might be to pass a key and let the front end fetch the latest post here
//     return $preview_link;
// }
// add_filter( 'preview_post_link', 'the_preview_fix');

function mailtrap($phpmailer)
{
	$phpmailer->isSMTP();
	$phpmailer->Host = 'smtp.mailtrap.io';
	$phpmailer->SMTPAuth = true;
	$phpmailer->Port = 2525;
	$phpmailer->Username = getenv('MAILTRAP_USER');
	$phpmailer->Password = getenv('MAILTRAP_PASS');
}

if (getenv('WP_DEBUG')) {
	add_action('phpmailer_init', 'mailtrap');
}

/**
 * Editor CSS
 * This css gets added to the Wordpress Editor Screen.
 *
 */

// When enqueue block editor assets runs, also run our custom function
// add_action('enqueue_block_editor_assets', 'staticprops_override_display');

// function staticprops_override_display() {
//     wp_enqueue_style(
//       'override-display', // a unique handle for your css
//       plugins_url('staticprops/editor.css', dirname(__FILE__)), // url of the css file
//         array('wp-edit-blocks'), // dependency to include CSS after Core CSS
//         rand(111,9999) // version of your CSS
//     );
// }

// We need some CSS to position the paragraph.
// TODO allow user to choose more options.

add_action( 'graphql_register_types', function() {

	register_graphql_field( 'Post', 'rawContent', [
		'type' => 'String',
		'description' => __( 'The raw content without filters/decoding applied', 'staticprops.com' ),
		'resolve' => function( \WPGraphQL\Model\Post $post ) {
			$post_object = ! empty( $post->databaseId  ) ? get_post( $post->databaseId ) : null;
			return isset( $post_object->post_content ) ?  apply_filters( 'the_content', $post_object->post_content) : null;
		}
	]);

});
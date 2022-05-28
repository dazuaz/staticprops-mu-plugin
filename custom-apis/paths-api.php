<?php
class PathsApi extends WP_REST_Controller
{
	public function register_routes()
	{
		$version = '1';
		$namespace = 'sp/v' . $version;
		$base = 'paths';
		register_rest_route($namespace, '/' . $base, [
			[
				'methods' => WP_REST_Server::READABLE,
				'callback' => [$this, 'get_paths'],
				'permission_callback' => [$this, 'get_paths_permissions_check'],
			],
			'schema' => [$this, 'get_public_item_schema'],
		]);
	}

	/**
	 * Get paths from all posts from the collection
	 *
	 * Get all posts without pagination, useful when using getStaticPaths
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_paths(WP_REST_Request $request)
	{
		// grab email and site name from request
		$posts = get_posts([
			'posts_per_page' => -1,
		]);

		if (empty($posts)) {
			return new WP_Error(
				'no-results',
				__('No posts found', 'text-domain'),
				['status' => 500]
			);
		}
		$names = array_map(function ($post) {
			$categories = array_map(function ($category) {
				return $category->name;
			}, get_the_category($post->ID));

			return (object) [
				'id' => $post->ID,
				'name' => $post->post_name,
				'title' => $post->post_title,
				'categories' => $categories,
			];
		}, $posts);

		if (!empty($names)) {
			return new WP_REST_Response($names, 200);
		} else {
			return new WP_Error(
				'no-results',
				__('Error making paths', 'text-domain'),
				['status' => 500]
			);
		}
	}
	/**
	 * Check if a given request has access to create site
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_paths_permissions_check($request)
	{
		return true;
	}
	/**
	 * Prepare the item for create or update operation
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_Error|object $prepared_item
	 */
	protected function prepare_item_for_database($request)
	{
		return array();
	}
}

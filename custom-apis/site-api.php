<?php
class SiteApi extends WP_REST_Controller
{
	/**
	 * ID property name.
	 */
	const PROP_ID = 'id';

	/**
	 * Email property name.
	 */
	const PROP_EMAIL = 'email';

	/**
	 * Domain property name.
	 */
	const PROP_DOMAIN = 'domain';

	/**
	 * Title property name.
	 */
	const PROP_TITLE = 'title';

	/**
	 * Title property name.
	 */
	const PROP_LANG = 'lang';

	public function __construct()
	{
		$this->namespace = 'sp/v1';
		$this->rest_base = 'site';
	}

	public function register_routes()
	{
		register_rest_route($this->namespace, '/' . $this->rest_base, [
			[
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => [$this, 'create_item'],
				'permission_callback' => [
					$this,
					'create_item_permissions_check',
				],
				'args' => $this->get_endpoint_args_for_item_schema(
					WP_REST_Server::CREATABLE
				),
			],
			'schema' => [$this, 'get_public_item_schema'],
		]);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			[
				'args' => [
					'id' => [
						'description' => __('Unique identifier for the site.'),
						'type' => 'integer',
					],
				],
				[
					'methods' => WP_REST_Server::DELETABLE,
					'callback' => [$this, 'delete_item'],
					'permission_callback' => [
						$this,
						'delete_item_permissions_check',
					],
					'args' => [
						'force' => [
							'type' => 'boolean',
							'default' => false,
							'description' => __(
								'Required to be true, as users do not support trashing.'
							),
						],
					],
				],
				'schema' => [$this, 'get_public_item_schema'],
			]
		);
	}
	/**
	 * Delete one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item($request)
	{
		$site_id = $request['id'];

		if (!function_exists('wpmu_delete_blog')) {
			require_once ABSPATH . '/wp-admin/includes/ms.php';
		}
		// $drop = true;
		// wpmu_delete_blog($site_id, $drop);
		wpmu_delete_blog($site_id);

		if (get_blog_status($site_id, 'deleted')) {
			return new WP_REST_Response(
				(object) ['site_id' => $site_id, 'message' => 'Site deleted successfully'],
				200
			);
		} else {
			return new WP_Error(
				'rest_cannot_delete',
				__('The site cannot be deleted.'),
				['status' => 500]
			);
		}
	}
	/**
	 * Get one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item($request)
	{
		if (!empty($request['id'])) {
			return new WP_Error(
				'rest_user_exists',
				__('Cannot create existing user.'),
				['status' => 400]
			);
		}

		$site = $this->prepare_item_for_database($request);

		if (is_wp_error($site)) {
			return $site;
		}

		if ('' === trim($site->user_email)) {
			return new WP_Error(
				'invalid-email',
				__('Missing email address.', 'text-domain'),
				['status' => 500]
			);
		}
		$email = sanitize_email($site->user_email);
		if (!is_email($email)) {
			return new WP_Error(
				'invalid-email',
				__('Invalid email address.', 'text-domain'),
				['status' => 500]
			);
		}

		// Domain
		$domain = '';
		$domain = trim($site->domain);
		if (preg_match('|^([a-zA-Z0-9-])+$|', $domain)) {
			$domain = strtolower($domain);
		}
		// If not a subdomain installation, make sure the domain isn't a reserved word.
		if (!is_subdomain_install()) {
			$subdirectory_reserved_names = get_subdirectory_reserved_names();
			if (in_array($domain, $subdirectory_reserved_names)) {
				return new WP_Error(
					'invalid-domain',
					__(
						sprintf(
							/* translators: %s: Reserved names list. */
							__(
								'The following words are reserved for use by WordPress functions and cannot be used as blog names: %s'
							),
							'<code>' .
								implode(
									'</code>, <code>',
									$subdirectory_reserved_names
								) .
								'</code>'
						),
						'text-domain'
					),
					['status' => 500]
				);
			}
		}
		if (empty($domain)) {
			return new WP_Error(
				'invalid-domain',
				__('Missing or invalid site address.', 'text-domain'),
				['status' => 500]
			);
		}
		if (is_subdomain_install()) {
			$newdomain =
				$domain .
				'.' .
				preg_replace('|^www\.|', '', get_network()->domain);
			$path = get_network()->path;
		} else {
			$newdomain = get_network()->domain;
			$path = get_network()->path . $domain . '/';
		}

		$password = 'N/A'; //send by email

		$user_id = email_exists($email);
		if (!$user_id) {
			// Create a new user with a random password.
			$user_id = username_exists($domain);
			if ($user_id) {
				wp_die(
					__(
						'The domain or path entered conflicts with an existing username.'
					)
				);
			}
			$password = wp_generate_password(12, false);
			$user_id = wpmu_create_user($domain, $password, $email);
			if (false === $user_id) {
				wp_die(__('There was an error creating the user.'));
			}
		}

		// $validate = wpmu_validate_blog_signup( $path, $title, $user_id );
		// return new WP_Error( 'cant-create', __( print_r($validate), 'text-domain' ), array( 'status' => 500 ) );
		$title = $site->title;
		$meta = ['public' => 1];

		$id = wpmu_create_blog(
			$newdomain,
			$path,
			$title,
			$user_id,
			$meta,
			get_current_network_id()
		);
		if (is_wp_error($id)) {
			// return new WP_Error(
			// 	'rest_site_create',
			// 	__('Error creating site.'),
			// 	['status' => 500]
			// );
			return $id;
		} else {
			return new WP_REST_Response(
				(object) ['id' => $id, 'password' => $password],
				200
			);
		}

		// $request->set_param('context', 'edit');

		// // Send the email notification to the user
		// do_action('network_site_new_created_user', $user_id);

		// $response = new WP_REST_Response((object) ['id' => $id, 'password' => $password], 200)
		// 	(object) ['id' => $id, 'password' => $password],
		// 	$request
		// );
		// $response = rest_ensure_response($response);

		// $response->set_status(200);
		// $response->header(
		// 	'Location',
		// 	rest_url(
		// 		sprintf('%s/%s/%d', $this->namespace, $this->rest_base, $id)
		// 	)
		// );
	}
	/**
	 * Check if a given request has access to create site
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function delete_item_permissions_check($request)
	{
		$auth_header = $request->get_header('Authorization');
		$api_token = getenv('API_ADMIN_TOKEN');

		$arr = explode(' ', $auth_header);
		unset($arr[0]);
		$request_api_token = implode(' ', $arr); // MyFile.jpg:foo:bar

		if (isset($api_token) && $request_api_token === $api_token) {
			return true;
		}
		return false;
	}
	/**
	 * Check if a given request has access to create site
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function create_item_permissions_check($request)
	{
		$auth_header = $request->get_header('Authorization');
		$api_token = getenv('API_ADMIN_TOKEN');

		$arr = explode(' ', $auth_header);
		unset($arr[0]);
		$request_api_token = implode(' ', $arr);
		if (isset($api_token) && $request_api_token === $api_token) {
			return true;
		}
		return false;
	}
	/**
	 * Prepare the item for create or update operation
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_Error|object $prepared_item
	 */
	protected function prepare_item_for_database($request)
	{
		$prepared_site = new stdClass();

		$schema = $this->get_item_schema();

		// Required arguments.
		if (
			isset($request['email']) &&
			!empty($schema['properties']['email'])
		) {
			$prepared_site->user_email = $request['email'];
		}

		if (
			isset($request['domain']) &&
			!empty($schema['properties']['domain'])
		) {
			$prepared_site->domain = $request['domain'];
		}

		if (
			isset($request['title']) &&
			!empty($schema['properties']['title'])
		) {
			$prepared_site->title = $request['title'];
		}
		/**
		 * Filters site data before insertion via the REST API.
		 *
		 * @since 4.7.0
		 *
		 * @param object          $prepared_user User object.
		 * @param WP_REST_Request $request       Request object.
		 */
		return apply_filters('rest_pre_insert_site', $prepared_site, $request);
	}
	/**
	 * Prepare the item for the REST response
	 *
	 * @param mixed $item WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 * @return mixed
	 */
	public function prepare_item_for_response($item, $request)
	{
		$data = [];
		$fields = $this->get_fields_for_response($request);

		if (rest_is_field_included('id', $fields)) {
			$data['id'] = $item->id;
		}

		if (in_array('password', $fields, true)) {
			$data['password'] = $item->password;
		}

		$context = !empty($request['context']) ? $request['context'] : 'embed';

		$data = $this->add_additional_fields_to_object($data, $request);
		$data = $this->filter_response_by_context($data, $context);

		// Wrap the data in a response object.
		$response = rest_ensure_response($data);

		// $response->add_links( $this->prepare_links( $user ) );

		/**
		 * Filters user data returned from the REST API.
		 *
		 * @since 4.7.0
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WP_User          $user     User object used to create response.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters('rest_prepare_site', $response, $item, $request);
	}
	protected function create_new_user($email, $domain)
	{
		$user_id = email_exists($email);
		if (!$user_id) {
			// Create a new user with a random password.
			$user_id = username_exists($domain);
			if ($user_id) {
				wp_die(
					__(
						'The domain or path entered conflicts with an existing username.'
					)
				);
			}
			$password = wp_generate_password(12, false);
			$user_id = wpmu_create_user($domain, $password, $email);
			if (false === $user_id) {
				wp_die(__('There was an error creating the user.'));
			}
		}
		return $user_id;
	}
	/**
	 * Get the site, if the ID is valid.
	 *
	 * @since 4.7.2
	 *
	 * @param int $id Supplied ID.
	 * @return WP_User|WP_Error True if ID is valid, WP_Error otherwise.
	 */
	protected function get_site($id)
	{
		// $error = new WP_Error(
		// 	'rest_user_invalid_id',
		// 	__( 'Invalid user ID.' ),
		// 	array( 'status' => 404 )
		// );

		// if ( (int) $id <= 0 ) {
		// 	return $error;
		// }

		// $user = get_userdata( (int) $id );
		// if ( empty( $user ) || ! $user->exists() ) {
		// 	return $error;
		// }

		// if ( is_multisite() && ! is_user_member_of_blog( $user->ID ) ) {
		// 	return $error;
		// }

		// return $user;
	}
	/**
	 * Retrieves the item schema, conforming to JSON Schema.
	 *
	 * @since 5.0.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema()
	{
		if ($this->schema) {
			return $this->add_additional_fields_schema($this->schema);
		}

		$schema = [
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title' => 'site',
			'type' => 'object',
			'properties' => [
				self::PROP_ID => [
					'description' => __('Unique identifier for the object.'),
					'type' => 'integer',
					'context' => ['view', 'embed', 'edit'],
					'readonly' => true,
				],
				self::PROP_EMAIL => [
					'description' => __('The user email for the object.'),
					'type' => 'string',
					'format' => 'email',
					'context' => ['view', 'embed'],
					'readonly' => true,
				],
				self::PROP_DOMAIN => [
					'description' => __('URL to the object.'),
					'type' => 'string',
					'format' => 'uri',
					'context' => ['view', 'embed'],
					'readonly' => true,
				],
				self::PROP_TITLE => [
					'description' => __('URL to the object.'),
					'type' => 'string',
					'context' => ['view', 'embed'],
					'readonly' => true,
				],
			],
		];

		$this->schema = $schema;

		return $this->add_additional_fields_schema($this->schema);
	}
}

<?php
/**
 * Register Contribution post type.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc\Post_Types;

use \LaterPay\Revenue_Generator\Inc\Config;
use \LaterPay\Revenue_Generator\Inc\Post_Types;
use \LaterPay\Revenue_Generator\Inc\View;

defined( 'ABSPATH' ) || exit;

/**
 * Class Contribution
 */
class Contribution extends Base {

	/**
	 * Slug of post type.
	 *
	 * @var string
	 */
	const SLUG = 'rg_contribution';

	/**
	 * Slug of admin screen for Contributions dashboard.
	 *
	 * @var string
	 */
	const ADMIN_DASHBOARD_SLUG = 'revenue-generator-contributions';

	/**
	 * Slug of admin screen for single Contributions edit.
	 *
	 * @var string
	 */
	const ADMIN_EDIT_SLUG = 'revenue-generator-contribution';

	/**
	 * Base path for REST API route for contributions.
	 *
	 * @const string
	 */
	const REST_BASE_PATH = 'contributions';

	/**
	 * User capability required to access `GET` endpoints in REST API.
	 *
	 * @const string
	 */
	const REST_VIEW_CAP = 'manage_options';

	/**
	 * User capability required to access `DELETE` endpoints in REST API.
	 *
	 * @const string
	 */
	const REST_DELETE_CAP = 'manage_options';

	/**
	 * Extends parent `setup_hooks()` method to add its own hooks.
	 *
	 * @return void
	 */
	protected function setup_hooks() {
		parent::setup_hooks();

		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * To get list of labels for contribution post type.
	 *
	 * @return array
	 */
	public function get_labels() {

		return [
			'name'          => __( 'Contributions', 'revenue-generator' ),
			'singular_name' => __( 'Contribution', 'revenue-generator' ),
		];

	}

	/**
	 * Save contribution.
	 *
	 * @param array $data Contribution data.
	 *
	 * @return int|\WP_Error
	 */
	public function save( $data ) {
		$default_meta      = $this->get_default_meta();
		$contribution_data = wp_parse_args( $data, $default_meta );
		$all_amounts       = array_map(
			function( $amount ) {
				return $amount * 100;
			},
			$contribution_data['all_amounts']
		);
		$contribution_data['all_amounts'] = $all_amounts;

		if ( empty( $contribution_data['ID'] ) ) {
			$contribution_id = wp_insert_post(
				[
					'post_content' => $contribution_data['dialog_description'],
					'post_title'   => $contribution_data['name'],
					'post_status'  => 'publish',
					'post_type'    => static::SLUG,
					'meta_input'   => [
						'_rg_type'               => $contribution_data['type'],
						'_rg_all_amounts'        => $contribution_data['all_amounts'],
						'_rg_dialog_header'      => $contribution_data['dialog_header'],
						'_rg_dialog_description' => $contribution_data['dialog_description'],
						'_rg_dialog_content'     => esc_html( $contribution_data['dialog_content'] ),
						'_rg_layout_type'        => $contribution_data['layout_type'],
						'_rg_button_label'       => $contribution_data['button_label'],
					],
				]
			);
		} else {
			$contribution_id   = $contribution_data['ID'];
			$contribution_post = $this->get( $contribution_id );

			if ( is_wp_error( $contribution_post ) ) {
				return new \WP_Error( 'invalid_contribution', __( 'Contribution with this ID does not exist.', 'revenue-generator' ) );
			}

			$update_args = [];

			if ( isset( $data['dialog_description'] ) ) {
				$update_args['post_content'] = sanitize_text_field( $data['dialog_description'] );
			}

			if ( isset( $data['name'] ) ) {
				$update_args['post_title'] = sanitize_text_field( $data['name'] );
			}

			if ( ! empty( $update_args ) ) {
				$update_args['ID'] = $contribution_id;

				wp_update_post( $update_args );
			}

			foreach ( $default_meta as $meta_key => $meta_value ) {
				/**
				 * If there's shortcode stored in the meta, reset it
				 * to empty value so auto-generated shortcode is used
				 * since the update.
				 */
				if ( 'code' === $meta_key ) {
					$contribution_data[ $meta_key ] = '';
				}

				if ( isset( $data[ $meta_key ] ) ) {
					update_post_meta( $contribution_id, "_rg_{$meta_key}", $contribution_data[ $meta_key ] );
				}
			}
		}

		return $contribution_id;
	}

	/**
	 * Default post data.
	 *
	 * @return array Post array with meta.
	 */
	public function get_default_post() {
		$post = [
			'ID'          => 0,
			'post_title'  => '',
			'post_author' => '',
		];

		$meta = $this->get_default_meta();

		return array_merge( $post, $meta );
	}

	/**
	 * Get Contribution data by ID.
	 *
	 * @param int $id ID of the contribution.
	 *
	 * @return array
	 */
	public function get( $id = 0 ) {
		$contribution_default_meta = $this->get_default_meta();

		/**
		 * In case of non-empty ID, get contribution from the database
		 * and parse meta.
		 */
		if ( ! empty( $id ) ) {
			$contribution_post = get_post( $id );
			$meta              = [];

			if ( ! $contribution_post || static::SLUG !== $contribution_post->post_type ) {
				return new \WP_Error( 'rg_contribution_not_found', __( 'No contribution found.', 'revenue-generator' ) );
			}

			$contribution_post = $contribution_post->to_array();
			$contribution_post = array_intersect_key( $contribution_post, $this->get_default_post() );
			$contribution_meta = get_post_meta( $id, '', true );

			$meta = $this->unprefix_meta( $contribution_meta );
			$meta = wp_parse_args( $meta, $contribution_default_meta );

			$last_modified_author_id = $this->get_last_modified_author_id( $id );

			$contribution_post['last_modified_author'] = ( ! empty( $last_modified_author_id ) ) ? $last_modified_author_id : $contribution_post['post_author'];
		} else {
			/**
			 * Empty ID (0) means that this is a new contribution, so
			 * return default contribution data in that case.
			 */
			$contribution_post = $this->get_default_post();
			$meta              = $contribution_default_meta;
		}

		// Merge post data and parsed meta to a single array.
		$contribution = array_merge( $contribution_post, $meta );

		$contribution['created_updated_info'] = $this->get_date_time_string( $contribution );

		return $contribution;
	}

	/**
	 * Deletes Contribution offer from the database.
	 *
	 * @param int $contribution_id ID of the contribution.
	 *
	 * @return mixed WP_Error on failure, contribution offer's ID on success.
	 */
	public function delete( $contribution_id = 0 ) {
		if ( empty( $contribution_id ) ) {
			return new \WP_Error( 'empty_contribution_id', __( 'Provided empty contribution ID to delete method.', 'revenue-generator' ) );
		}

		$contribution = $this->get( $contribution_id );

		if ( ! is_wp_error( $contribution ) ) {
			wp_delete_post( $contribution_id );

			return $contribution_id;
		}

		return $contribution;
	}

	/**
	 * Unprefix meta passed in the method's parameters.
	 *
	 * @param array $meta Prefixed meta to unprefix.
	 *
	 * @return array Unprefixed meta.
	 */
	public function unprefix_meta( $meta = [] ) {
		$unprefixed_meta = [];

		foreach ( $meta as $key => $value ) {
			$unprefixed_key                     = str_replace( '_rg_', '', $key );
			$unprefixed_meta[ $unprefixed_key ] = maybe_unserialize( $value[0] );
		}

		return $unprefixed_meta;
	}

	/**
	 * Get 'Created on <date> by <author>' string or 'Updated on <date> by <author>'
	 * by contribution.
	 *
	 * @param array|int $contribution Contribution data or ID.
	 *
	 * @return string
	 */
	public function get_date_time_string( $contribution ) {
		// If `$contribution` param is integer, attempt to get contribution data.
		if ( is_int( $contribution ) ) {
			$contribution = $this->get( $contribution );
		}

		$date_time_string = '';

		if ( ! is_array( $contribution ) ) {
			return $date_time_string;
		}

		$created_modified_string = __( 'Created', 'revenue-generator' );
		$post_published_date     = get_the_date( '', $contribution['ID'] );
		$post_published_time     = get_the_time( '', $contribution['ID'] );
		$post_modified_date      = get_the_modified_date( '', $contribution['ID'] );
		$post_modified_time      = get_the_modified_time( '', $contribution['ID'] );

		if ( empty( $post_published_date ) ) {
			return '';
		}

		if ( $post_published_date !== $post_modified_date || $post_published_time !== $post_modified_time ) {
			$created_modified_string = __( 'Updated', 'revenue-generator' );
		}

		$date_time_string  = sprintf(
			/* translators: %1$s modified date, %2$s modified time */
			__( '%1$s on %2$s at %3$s by %4$s', 'revenue-generator' ),
			$created_modified_string,
			$post_modified_date,
			$post_modified_time,
			( isset( $contribution['last_modified_author'] ) ) ? get_the_author_meta( 'display_name', $contribution['last_modified_author'] ) : ''
		);

		return $date_time_string;
	}

	/**
	 * Get default meta data for contribution.
	 *
	 * @return array
	 */
	public function get_default_meta() {
		return [
			'dialog_header'        => __( 'Support the Author', 'revenue-generator' ),
			'button_label'         => __( 'Support the Author', 'revenue-generator' ),
			'dialog_description'   => __( 'Pick your contribution below:', 'revenue-generator' ),
			'dialog_content'       => '',
			'all_amounts'          => array( 50, 100, 150 ),
			'code'                 => '',
			'layout_type'          => 'box',
			'type'                 => 'multiple',
			'created_updated_info' => '',
		];
	}

	/**
	 * Get shortcode for the contribution.
	 *
	 * This supports previous versions of the plugin where shortcode was stored in the contribution's meta
	 * and also a new version where shortcode is ID based.
	 *
	 * @since 1.1.0
	 *
	 * @param int $contribution Contribution ID or Contribution data in array.
	 *
	 * @return string
	 */
	public function get_shortcode( $contribution = 0 ) {
		$shortcode = '';

		if ( empty( $contribution ) ) {
			return $shortcode;
		}

		if ( is_int( $contribution ) ) {
			$contribution = $this->get( $contribution );
		}

		if ( ! is_array( $contribution ) ) {
			return $shortcode;
		}

		$shortcode = sprintf(
			'[laterpay_contribution id="%d"]',
			$contribution['ID']
		);

		if ( isset( $contribution['code'] ) && ! empty( $contribution['code'] ) ) {
			$shortcode = $contribution['code'];
		}

		return $shortcode;
	}

	/**
	 * Get edit link for contribution based on its ID.
	 *
	 * @param int $contribution_id Contribution ID.
	 *
	 * @return string
	 */
	public function get_edit_link( $contribution_id = 0 ) {
		if ( empty( $contribution_id ) ) {
			return;
		}

		$edit_link = admin_url(
			sprintf(
				'admin.php?page=%s&id=%d',
				static::ADMIN_EDIT_SLUG,
				$contribution_id
			)
		);

		return $edit_link;
	}

	/**
	 * Get ID of the newest contribution created.
	 *
	 * @return int
	 */
	public function get_latest_contribution_id() {
		$query_args = [
			'post_type'      => static::SLUG,
			'post_status'    => [ 'publish' ],
			'posts_per_page' => 1,
			'fields'         => 'ids',
		];

		$contribution_id = 0;

		$contributions = get_posts( $query_args );

		if ( ! empty( $contributions ) ) {
			$contribution_id = $contributions[0];
		}

		return $contribution_id;
	}

	/**
	 * Get all Contributions.
	 *
	 * @param array $contribution_args contribution search args.
	 *
	 * @return array
	 */
	public function get_all_contributions( $contribution_args = [] ) {
		$query_args = [
			'post_type'      => static::SLUG,
			'post_status'    => [ 'publish' ],
			'posts_per_page' => 100,
			'no_found_rows'  => true,
		];

		// Merge default params and extra args.
		$query_args = array_merge( $query_args, $contribution_args );

		// Initialize WP_Query without args.
		$get_contributions_query = new \WP_Query();

		// Get posts for requested args.
		$posts         = $get_contributions_query->query( $query_args );
		$contributions = [];

		foreach ( $posts as $key => $post ) {
			$contributions[ $key ] = $this->get( $post->ID );
		}

		return $contributions;
	}

	/**
	 * Get user id of the user who last updated the contribution.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string|int
	 */
	public function get_last_modified_author_id( $post_id ) {
		$last_id = get_post_meta( $post_id, '_edit_last', true );
		if ( $last_id ) {
			return $last_id;
		}

		return '';
	}

	/**
	 * Check if the provided shortcode configuration for Contribution is valid or now.
	 *
	 * @param array $config_array Contribution configuration data.
	 *
	 * @return array|bool
	 */
	private static function is_contribution_config_valid( $config_array ) {

		// Check if campaign name is set.
		if ( empty( $config_array['name'] ) ) {
			return [
				'success' => false,
				'message' => esc_html__( 'Please enter a Campaign Name above.', 'revenue-generator' ),
			];
		}

		// Check if campaign amount is empty.
		if ( 'single' === $config_array['type'] ) {
			if ( floatval( $config_array['single_amount'] ) === floatval( 0.0 ) ) {
				return [
					'success' => false,
					'message' => esc_html__( 'Please enter a valid contribution amount above.', 'revenue-generator' ),
				];
			}
			return true;
		}

		return true;
	}

	/**
	 * Filter to modify the search of contribution data.
	 *
	 * @param string    $sql   SQL string.
	 * @param \WP_Query $query Query object.
	 *
	 * @return string
	 */
	public function rg_contribution_title_filter( $sql, $query ) {
		global $wpdb;

		// If our custom query var is set modify the query.
		if ( ! empty( $query->query['rg_contribution_title'] ) ) {
			$term = $wpdb->esc_like( $query->query['rg_contribution_title'] );
			$sql .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . $term . '%\'';
		}

		return $sql;
	}

	/**
	 * Get Contributions dashboard URL.
	 *
	 * When plugin has paywalls feature, Contributions screen will be registered under
	 * the self::ADMIN_DASHBOARD_SLUG. Otherwise Contributions dashboard will be
	 * plugin's main admin page.
	 *
	 * @return string
	 */
	public static function get_dashboard_url() {
		$url = admin_url(
			sprintf(
				'admin.php?page=%s',
				self::ADMIN_DASHBOARD_SLUG
			)
		);

		if ( defined( 'REVENUE_GENERATOR_HAS_PAYWALLS' ) && ! REVENUE_GENERATOR_HAS_PAYWALLS ) {
			$url = admin_url( 'admin.php?page=revenue-generator' );
		}

		return $url;
	}

	/**
	 * Check if there are contributions in the database.
	 *
	 * @return boolean
	 */
	public static function has_contributions() {
		$contributions = get_posts(
			[
				'post_type'      => static::SLUG,
				'post_status'    => 'publish',
				'posts_per_page' => '1',
				'fields'         => 'ID',
			]
		);

		if ( empty( $contributions ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get ID of floating footer contribution.
	 *
	 * @return int Zero if not found, non-zero contribution ID on success.
	 */
	public static function get_footer_contribution_id() {
		$item = get_posts(
			[
				'post_type'      => static::SLUG,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => [
					[
						'key'   => '_rg_layout_type',
						'value' => 'footer',
					],
				],
			]
		);

		if ( empty( $item ) ) {
			return 0;
		}

		return $item[0];
	}

	/**
	 * Register REST API routes.
	 *
	 * @hooked action `rest_api_init`
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			REVENUE_GENERATOR_REST_NAMESPACE,
			static::REST_BASE_PATH,
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'rest_get_all_contributions' ],
					'permission_callback' => [ $this, 'rest_check_view_permission' ],
				],
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'rest_add_contribution' ],
					'permission_callback' => [ $this, 'rest_check_add_update_permission' ],
				],
			]
		);

		register_rest_route(
			REVENUE_GENERATOR_REST_NAMESPACE,
			'/' . static::REST_BASE_PATH . '/(?P<id>[\d]+)',
			[
				'args' => [
					'id' => [
						'description' => __( 'Unique identifier for contribution object.', 'revenue-generator' ),
						'type'        => 'integer',
					],
				],
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'rest_get_single_contribution' ],
					'permission_callback' => [ $this, 'rest_check_view_permission' ],
				],
				[
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'rest_update_contribution' ],
					'permission_callback' => [ $this, 'rest_check_add_update_permission' ],
				],
				[
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'rest_delete_contribution' ],
					'permission_callback' => [ $this, 'rest_check_delete_permission' ],
				],
			]
		);
	}

	/**
	 * Get all contributions, serving GET endpoint.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, WP_Error object on failure.
	 */
	public function rest_get_all_contributions() {
		// Return early if not within REST context.
		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
			return;
		}

		$data  = [];
		$items = $this->get_all_contributions();

		$returned_keys = [
			'ID',
			'post_title',
			'created_updated_info',
			'all_amounts',
			'dialog_description',
			'dialog_header',
			'dialog_content',
			'button_label',
			'layout_type',
		];

		$filtered = [];

		foreach ( $items as $item ) {
			$item = array_filter(
				$item,
				function( $key ) use ( $returned_keys ) {
					return in_array( $key, $returned_keys );
				},
				ARRAY_FILTER_USE_KEY
			);

			$filtered[] = $item;
		}

		$data['items'] = $filtered;
		$data['total'] = count( $items );

		$response = rest_ensure_response( $data );

		return $response;
	}

	/**
	 * Get a single contribution by ID. GET endpoint with `/(id)`.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_get_single_contribution( $request ) {
		// Return early if not within REST context.
		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
			return;
		}

		$id = (int) $request['id'];

		if ( empty( $id ) ) {
			return new \WP_Error(
				'rg_rest_missing_param_id',
				__( 'ID parameter is required.', 'revenue-generator' ),
				[
					'status' => 400,
				]
			);
		}

		$item = $this->get( $request['id'] );

		if ( is_wp_error( $item ) ) {
			return new \WP_Error(
				'rg_rest_not_found',
				__( 'The item with that ID was not found.', 'revenue-generator' ),
				[
					'status' => 404,
				]
			);
		}

		$all_amounts = array_map(
			function( $item ) {
				return $item / 100;
			},
			$item['all_amounts']
		);

		$item['all_amounts'] = $all_amounts;

		return rest_ensure_response( $item );
	}

	/**
	 * Add contribution by REST request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response|\WP_Error Response object on success, WP_Error on failure.
	 */
	public function rest_add_contribution( $request ) {
		// Return early if not within REST context.
		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
			return;
		}

		$body = json_decode( $request->get_body(), true );

		if ( empty( $body ) ) {
			return new \WP_Error(
				'rg_rest_missing_body',
				__( 'The request is missing a body which is required.', 'revenue-generator' ),
				[
					'status' => 400,
				]
			);
		}

		$required = [
			'name',
			'all_amounts',
			'dialog_header',
			'layout_type',
		];

		$required_to_passed_diff = array_diff( array_values( $required ), array_keys( $body ) );

		if ( ! empty( $required_to_passed_diff ) ) {
			return new \WP_Error(
				'rg_rest_missing_body_params',
				sprintf(
					/* translators: %s is a comma separated list of missing parameters */
					__( 'The following body params are required but missing: %s', 'revenue-generator' ),
					implode( ', ', $required_to_passed_diff )
				),
				[
					'status' => 400,
				]
			);
		}

		if ( isset( $body['ID'] ) ) {
			return new \WP_Error(
				'rg_rest_invalid_param',
				__( 'The parameter ID cannot be passed to this endpoint. Use update endpoint instead.', 'revenue-generator' ),
				[
					'status' => 400,
				]
			);
		}

		$amounts = &$body['all_amounts'];

		if ( empty( $amounts ) || ! is_array( $amounts ) ) {
			return new \WP_Error(
				'rg_rest_invalid_param_all_amounts',
				__( 'The parameter \'all_amounts\' needs to be an array.', 'revenue-generator' ),
				[
					'status' => 400,
				]
			);
		}

		$amounts = array_filter(
			$amounts,
			function( $element ) {
				return is_float( $element ) || is_int( $element );
			}
		);

		if ( 3 !== count( $amounts ) ) {
			return new \WP_Error(
				'rg_rest_invalid_param_all_amounts',
				__( 'The parameter \'all_amounts\' needs to be an array and hold exactly 3 numbers.', 'revenue-generator' ),
				[
					'status' => 400,
				]
			);
		}

		$save = $this->save( $body );

		$response = [
			'ID' => $save,
		];

		return rest_ensure_response( $response );
	}

	/**
	 * Update contribution by REST request.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response|\WP_Error REST response object on success,
	 * WP_Error on failure.
	 */
	public function rest_update_contribution( $request ) {
		// Return early if not within REST context.
		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
			return;
		}

		$id   = (int) $request->get_param( 'id' );
		$body = json_decode( $request->get_body(), true );

		if ( empty( $body ) ) {
			return new \WP_Error(
				'rg_rest_missing_body',
				__( 'The request is missing a body which is required.', 'revenue-generator' ),
				[
					'status' => 400,
				]
			);
		}

		$item = $this->get( $id );

		if ( is_wp_error( $item ) ) {
			return new \WP_Error(
				'rg_rest_not_found',
				__( 'The item with that ID was not found.', 'revenue-generator' ),
				[
					'status' => 404,
				]
			);
		}

		$data       = $body;
		$data['ID'] = $id;

		$save = $this->save( $data );

		$response = [
			'ID' => $save,
		];

		return rest_ensure_response( $response );
	}

	/**
	 * Delete contribution by REST request. Handler for the call to
	 * DELETE endpoint.
	 *
	 * @param \WP_REST_Request $request REST request.
	 *
	 * @return \WP_REST_Response|\WP_Error REST response object on success,
	 * WP_Error on failure.
	 */
	public function rest_delete_contribution( $request ) {
		$id = (int) $request['id'];

		if ( empty( $id ) ) {
			return new \WP_Error(
				'rg_rest_missing_param_id',
				__( 'ID param is required but missing.', 'revenue-generator' ),
				[
					'status' => 400,
				]
			);
		}

		$post = get_post( $id );

		if ( empty( $post ) ) {
			return new \WP_Error(
				'rg_rest_not_found',
				__( 'Contribution with that ID could not be found.', 'revenue-generator' ),
				[
					'status' => 404,
				]
			);
		}

		if ( self::SLUG !== $post->post_type ) {
			return new \WP_Error(
				'rg_rest_invalid_param_id',
				__( 'The parameter \'ID\' is invalid.', 'revenue-generator' ),
				[
					'status' => 400,
				]
			);
		}

		$delete = $this->delete( $id );

		$response = [
			'success' => true,
		];

		return rest_ensure_response( $response );
	}

	/**
	 * Check if user has a permission to view content in REST context.
	 *
	 * @return void|WP_Error Nothing on fulfilling requirements, WP_Error otherwise.
	 */
	public function rest_check_view_permission() {
		if ( ! current_user_can( self::REST_VIEW_CAP ) ) {
			return $this->rest_get_insufficient_permissions_error();
		}

		return true;
	}

	/**
	 * Check if user has a permission to add and update content in REST context.
	 *
	 * @return void|WP_Error Nothing on fulfilling requirements, WP_Error otherwise.
	 */
	public function rest_check_add_update_permission() {
		$post_type = get_post_type_object( self::SLUG );

		if ( ! current_user_can( $post_type->cap->edit_posts ) ) {
			return $this->rest_get_insufficient_permissions_error();
		}

		return true;
	}

	/**
	 * Check if user has a permission to delete content in REST context.
	 *
	 * @return boolean|WP_Error
	 */
	public function rest_check_delete_permission() {
		if ( ! current_user_can( self::REST_DELETE_CAP ) ) {
			return $this->rest_get_insufficient_permissions_error();
		}

		return true;
	}

	/**
	 * Returns WP_Error object used when a user making request does not have
	 * sufficient privileges to access the endpoint.
	 *
	 * @return WP_Error
	 */
	public function rest_get_insufficient_permissions_error() {
		return new \WP_Error(
			'rg_rest_forbidden_context',
			__( 'Sorry, you are not authorized for this endpoint.', 'revenue-generator' ),
			[
				'status' => rest_authorization_required_code(),
			]
		);
	}

}

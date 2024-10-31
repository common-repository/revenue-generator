<?php
/**
 * Register Paywall post type.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc\Post_Types;

use LaterPay\Revenue_Generator\Inc\Config;
use LaterPay\Revenue_Generator\Inc\Post_Types;
use LaterPay\Revenue_Generator\Inc\View;
use LaterPay\Revenue_Generator\Inc\Post_Types\Contribution_Preview;

defined( 'ABSPATH' ) || exit;

/**
 * Class Paywall
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
	 * Extends parent `setup_hooks()` method to add its own hooks.
	 *
	 * @return void
	 */
	protected function setup_hooks() {
		parent::setup_hooks();

		add_filter( 'rg_contribution_builder_data', [ $this, 'filter_builder_contribution_data' ] );
	}

	/**
	 * To get list of labels for paywall post type.
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
	 * @param array $contribution_data Contribution Information.
	 *
	 * @return int|\WP_Error
	 */
	public function save( $contribution_data ) {
		$default_meta      = $this->get_default_meta();
		$contribution_data = wp_parse_args( $contribution_data, $default_meta );

		if ( empty( $contribution_data['ID'] ) ) {
			$contribution_id = wp_insert_post(
				[
					'post_content' => $contribution_data['dialog_description'],
					'post_title'   => $contribution_data['name'],
					'post_status'  => 'publish',
					'post_type'    => static::SLUG,
					'meta_input'   => [
						'_rg_thank_you'            => $contribution_data['thank_you'],
						'_rg_type'                 => $contribution_data['type'],
						'_rg_custom_amount'        => $contribution_data['custom_amount'],
						'_rg_all_amounts'          => $contribution_data['all_amounts'],
						'_rg_dialog_header'        => $contribution_data['dialog_header'],
						'_rg_dialog_description'   => $contribution_data['dialog_description'],
						'_rg_all_revenues'         => $contribution_data['all_revenues'],
						'_rg_selected_amount'      => $contribution_data['selected_amount'],
						'_rg_layout_type'          => $contribution_data['layout_type'],
						'_rg_button_label'         => $contribution_data['button_label'],
					],
				]
			);
		} else {
			$contribution_id   = $contribution_data['ID'];
			$contribution_post = $this->get( $contribution_id );

			if ( is_wp_error( $contribution_post ) ) {
				return new \WP_Error( 'invalid_contribution', __( 'Contribution with this ID does not exist.', 'revenue-generator' ) );
			}

			wp_update_post(
				[
					'ID'           => $contribution_id,
					'post_content' => $contribution_data['dialog_description'],
					'post_title'   => $contribution_data['name'],
				]
			);

			foreach ( $default_meta as $meta_key => $meta_value ) {
				/**
				 * If there's shortcode stored in the meta, reset it
				 * to empty value so auto-generated shortcode is used
				 * since the update.
				 */
				if ( 'code' === $meta_key ) {
					$contribution_data[ $meta_key ] = '';
				}

				update_post_meta( $contribution_id, "_rg_{$meta_key}", $contribution_data[ $meta_key ] );
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

		if ( $post_published_date !== $post_modified_date || $post_published_time !== $post_modified_time ) {
			$created_modified_string = __( 'Updated', 'revenue-generator' );
		}

		$date_time_string  = sprintf(
			/* translators: %1$s modified date, %2$s modified time */
			__( '%1$s on %2$s at %3$s by %4$s', 'revenue-generator' ),
			$created_modified_string,
			$post_modified_date,
			$post_modified_time,
			get_the_author_meta( 'display_name', $contribution['last_modified_author'] )
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
			'type'               => 'multiple',
			'name'               => '',
			'thank_you'          => '',
			'dialog_header'      => __( 'Support the Author', 'revenue-generator' ),
			'button_label'       => __( 'Support the Author', 'revenue-generator' ),
			'dialog_description' => __( 'Pick your contribution below:', 'revenue-generator' ),
			'custom_amount'      => '',
			'all_amounts'        => array( 50, 100, 150 ),
			'all_revenues'       => '',
			'selected_amount'    => '',
			'code'               => '',
			'layout_type'        => 'box',
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
	 * Get all Contributions.
	 *
	 * @param array $contribution_args contribution search args.
	 *
	 * @return array
	 */
	public function get_all_contributions( $contribution_args ) {
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
	 * Get user id of the user who last updated the paywall.
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
	 * Get preview URL of Contribution preview.
	 *
	 * @return string $url Post preview URL.
	 */
	public static function get_preview_post_url() {
		$url     = '';
		$post_id = 0;

		$posts = get_posts(
			[
				'post_type'      => Contribution_Preview::SLUG,
				'posts_per_page' => 1,
				'post_status'    => 'draft',
				'fields'         => 'ids',
			]
		);

		if ( ! empty( $posts ) ) {
			$post_id = $posts[0];
		} else {
			$post_id = wp_insert_post(
				[
					'post_type' => Contribution_Preview::SLUG,
					'post_status' => 'draft',
				]
			);
		}

		$url = add_query_arg(
			[
				'p'         => $post_id,
				'post_type' => Contribution_Preview::SLUG,
				'preview'   => 'true',
			],
			site_url()
		);

		return $url;
	}

	/**
	 * Filters contribution data before passing it to builder.
	 *
	 * @hooked filter `rg_contribution_builder_data`
	 *
	 * @param array $data Contribution data.
	 *
	 * @return array
	 */
	public function filter_builder_contribution_data( $data ) {
		// Convert amounts from cents back to floats for use in the builder.
		foreach ( $data['all_amounts'] as $key => $amount ) {
			$data['all_amounts'][ $key ] = (int) $amount / 100;
		}

		$data['amounts'] = $data['all_amounts'];

		return $data;
	}

	/**
	 * Get Contributions dashboard URL.
	 *
	 * @return string
	 */
	public static function get_dashboard_url() {
		$url = admin_url( 'admin.php?page=revenue-generator' );

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

}

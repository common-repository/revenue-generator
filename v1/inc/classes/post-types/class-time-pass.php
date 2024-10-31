<?php
/**
 * Register Time Pass post type.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc\Post_Types;

defined( 'ABSPATH' ) || exit;

/**
 * Class Time_Pass
 */
class Time_Pass extends Base {

	/**
	 * Slug of post type.
	 *
	 * @var string
	 */
	const SLUG = 'rg_pass';

	/**
	 * Token of post type.
	 *
	 * @var string
	 */
	const TOKEN = 'tlp';

	/**
	 * To get list of labels for time pass post type.
	 *
	 * @return array
	 */
	public function get_labels() {

		return [
			'name'          => __( 'Time Passes', 'revenue-generator' ),
			'singular_name' => __( 'Time Pass', 'revenue-generator' ),
		];

	}

	/**
	 * Get count of existing time passes.
	 *
	 * @param bool $ignore_deleted ignore count of deleted pass.
	 *
	 * @return int number of defined time passes
	 */
	public static function get_time_passes_count( $ignore_deleted = false ) {

		$time_pass_count = wp_count_posts( static::SLUG );

		$result = ( ( true === $ignore_deleted ) ? $time_pass_count->publish : $time_pass_count->publish + $time_pass_count->draft );

		return absint( $result );
	}

	/**
	 * Get all active time passes.
	 *
	 * @return array of time passes.
	 */
	public function get_active_time_passes() {
		return $this->get_all_time_passes( true );
	}

	/**
	 * Get all time passes id.
	 *
	 * @return array of time passes.
	 */
	public function get_all_time_pass_tokenized_ids() {
		$time_pass_ids   = [];
		$all_time_passes = $this->get_all_time_passes();
		foreach ( $all_time_passes as $time_pass ) {
			$time_pass_ids[] = $this->tokenize_time_pass_id( $time_pass['id'] );
		}

		return $time_pass_ids;
	}

	/**
	 * Get all active time passes id.
	 *
	 * @return array of time passes.
	 */
	public function get_active_time_pass_tokenized_ids() {
		$time_pass_ids      = [];
		$active_time_passes = $this->get_active_time_passes();
		foreach ( $active_time_passes as $time_pass ) {
			$time_pass_ids[] = $this->tokenize_time_pass_id( $time_pass['id'] );
		}

		return $time_pass_ids;
	}

	/**
	 * Get inactive time passes id.
	 *
	 * @return array of time passes.
	 */
	public function get_inactive_time_pass_tokenized_ids() {
		$time_pass_ids   = [];
		$all_time_passes = $this->get_all_time_passes();
		foreach ( $all_time_passes as $time_pass ) {
			if ( empty( $time_pass['is_active'] ) ) {
				$time_pass_ids[] = $this->tokenize_time_pass_id( $time_pass['id'] );
			}
		}

		return $time_pass_ids;
	}

	/**
	 * Get tokenized time pass id.
	 *
	 * @param int $id Time Pass ID.
	 *
	 * @return string
	 */
	public function tokenize_time_pass_id( $id ) {
		return sprintf( '%s_%s', self::TOKEN, $id );
	}

	/**
	 * Get all time passes.
	 *
	 * @param bool $ignore_deleted ignore deleted time passes.
	 *
	 * @return array list of time passes
	 */
	public function get_all_time_passes( $ignore_deleted = false ) {

		$query_args = [
			'post_type'      => static::SLUG,
			'post_status'    => [ 'publish', 'draft' ],
			'posts_per_page' => 100,
			'no_found_rows'  => true,
		];

		// Don't include the deleted time passes.
		if ( $ignore_deleted ) {
			$query_args['post_status'] = 'publish';
		}

		// Initialize WP_Query without args.
		$get_time_passes = new \WP_Query();

		// Get posts for requested args.
		$posts       = $get_time_passes->query( $query_args );
		$time_passes = [];

		foreach ( $posts as $key => $post ) {
			if ( 'draft' === $post->post_status ) {
				$is_expired = $this->is_expired_duration( $post );
				if ( ! $is_expired ) {
					continue;
				}
			}
			$time_passes[ $key ] = $this->formatted_time_pass( $post );
		}

		return $time_passes;
	}


	/**
	 * Compares modfied post with current date time and checks for expired post.
	 *
	 * @param object $post Post to check.
	 *
	 * @return boolean returns false if exipred else true.
	 */
	public function is_expired_duration( $post ) {

		$post_meta    = get_post_meta( $post->ID );
		$post_meta    = $this->formatted_post_meta( $post_meta );
		$duration     = $post_meta['duration'];
		$period       = $post_meta['period'];
		$post_modifed = strtotime( $post->post_modified );

		// Verify duration and period exists.
		if ( empty( $duration ) && empty( $period ) ) {
			return false;
		}

		$expired_period = '';
		$current_date   = gmdate( 'Y-m-d H:i:s' );

		// Get Expired duration.
		switch ( $duration ) {
			case 'h':
				$duration_string = '+' . $period . ' Hour';
				$expired_period  = gmdate( 'Y-m-d H:i:s', strtotime( $duration_string, $post_modifed ) );
				break;
			case 'd':
				$duration_string = '+' . $period . ' Day';
				$expired_period  = gmdate( 'Y-m-d H:i:s', strtotime( $duration_string, $post_modifed ) );
				break;
			case 'm':
				$duration_string = '+' . $period . ' Month';
				$expired_period  = gmdate( 'Y-m-d H:i:s', strtotime( $duration_string, $post_modifed ) );
				break;
			case 'w':
				$duration_string = '+' . $period . ' Week';
				$expired_period  = gmdate( 'Y-m-d H:i:s', strtotime( $duration_string, $post_modifed ) );
				break;
			case 'y':
				$duration_string = '+' . $period . ' Year';
				$expired_period  = gmdate( 'Y-m-d H:i:s', strtotime( $duration_string, $post_modifed ) );
				break;
		}

		// Compare Expired period with current date time and return true or false.
		if ( ! empty( $current_date ) && ! empty( $expired_period ) && $current_date > $expired_period ) {
			return false;
		} else {

			return true;
		}

	}

	/**
	 * Returns relevant fields for time pass of given WP_Post
	 *
	 * @param \WP_Post $post Post to transform.
	 *
	 * @return array Time Pass instance as array
	 */
	private function formatted_time_pass( $post ) {

		$post_meta = get_post_meta( $post->ID );
		$is_active = ( 'draft' === $post->post_status ) ? 0 : 1;

		$post_meta = $this->formatted_post_meta( $post_meta );

		$time_pass                  = [];
		$time_pass['id']            = $post->ID;
		$time_pass['title']         = $post->post_title;
		$time_pass['description']   = $post->post_content;
		$time_pass['price']         = $post_meta['price'];
		$time_pass['revenue']       = $post_meta['revenue'];
		$time_pass['duration']      = $post_meta['duration'];
		$time_pass['period']        = $post_meta['period'];
		$time_pass['is_active']     = $is_active;
		$time_pass['access_to']     = $post_meta['access_to'];
		$time_pass['access_entity'] = $post_meta['access_entity'];
		$time_pass['custom_title']  = $post_meta['custom_title'];
		$time_pass['custom_desc']   = $post_meta['custom_desc'];

		return $time_pass;
	}

	/**
	 * Check if post meta has values.
	 *
	 * @param array $post_meta Post meta values fetched form database.
	 *
	 * @return array
	 */
	private function formatted_post_meta( $post_meta ) {
		$post_meta_data = [];

		/**
		 * _rg_price - store the pricing configuration array.
		 * _rg_expiry - store the content access expiry configuration array.
		 * _rg_access_to - store the content to which the time pass will allow access, can be category / all.
		 * _rg_access_entity - store the id to which the time pass will allow access if access to is category.
		 */
		$post_meta_data['price']         = ( isset( $post_meta['_rg_price'][0] ) ) ? $post_meta['_rg_price'][0] : '';
		$post_meta_data['revenue']       = ( isset( $post_meta['_rg_revenue'][0] ) ) ? $post_meta['_rg_revenue'][0] : '';
		$post_meta_data['duration']      = ( isset( $post_meta['_rg_duration'][0] ) ) ? $post_meta['_rg_duration'][0] : '';
		$post_meta_data['period']        = ( isset( $post_meta['_rg_period'][0] ) ) ? $post_meta['_rg_period'][0] : '';
		$post_meta_data['access_to']     = ( isset( $post_meta['_rg_access_to'][0] ) ) ? $post_meta['_rg_access_to'][0] : '';
		$post_meta_data['access_entity'] = ( isset( $post_meta['_rg_access_entity'][0] ) ) ? $post_meta['_rg_access_entity'][0] : '';
		$post_meta_data['custom_title']  = ( isset( $post_meta['_rg_custom_title'][0] ) ) ? $post_meta['_rg_custom_title'][0] : '';
		$post_meta_data['custom_desc']   = ( isset( $post_meta['_rg_custom_desc'][0] ) ) ? $post_meta['_rg_custom_desc'][0] : '';

		return $post_meta_data;
	}

	/**
	 * Get all time passes that apply to a given post.
	 *
	 * @param bool $ignore_deleted ignore deleted time passes.
	 *
	 * @return array $time_passes list of time passes
	 */
	public function get_time_passes_by_criteria( $ignore_deleted = true ) {

		$query_args = [
			'post_type'      => static::SLUG,
			'post_status'    => [ 'publish', 'draft' ],
			'posts_per_page' => 100,
			'no_found_rows'  => true,
		];

		$meta_query = [
			'relation' => 'AND',
			[
				'key'     => '_rg_access_to',
				'value'   => 'all',
				'compare' => '=',
			],
		];

		if ( $ignore_deleted ) {
			$query_args['post_status'] = 'publish';
		}

		// Meta query used to get all time passes which are applicable.
		$query_args['meta_query'] = $meta_query;

		// Initialize WP_Query without args.
		$get_time_passes_query = new \WP_Query();

		// Get posts for requested args.
		$posts       = $get_time_passes_query->query( $query_args );
		$time_passes = [];

		foreach ( $posts as $key => $post ) {
			$time_passes[ $key ] = $this->formatted_time_pass( $post );
		}

		return $time_passes;
	}

	/**
	 * Update time pass data.
	 *
	 * @param array $time_pass_data Time Pass data.
	 *
	 * @return int|\WP_Error
	 */
	public function update_time_pass( $time_pass_data ) {
		if ( empty( $time_pass_data['id'] ) ) {
			$time_pass_id = wp_insert_post(
				[
					'post_content' => $time_pass_data['description'],
					'post_title'   => $time_pass_data['title'],
					'post_status'  => 'publish',
					'post_type'    => static::SLUG,
					'meta_input'   => [
						'_rg_price'        => $time_pass_data['price'],
						'_rg_revenue'      => $time_pass_data['revenue'],
						'_rg_duration'     => $time_pass_data['duration'],
						'_rg_period'       => $time_pass_data['period'],
						'_rg_access_to'    => $time_pass_data['access_to'],
						'_rg_custom_title' => $time_pass_data['custom_title'],
						'_rg_custom_desc'  => $time_pass_data['custom_desc'],
					],
				]
			);
		} else {
			$time_pass_id = $time_pass_data['id'];
			wp_update_post(
				[
					'ID'           => $time_pass_id,
					'post_content' => $time_pass_data['description'],
					'post_title'   => $time_pass_data['title'],
				]
			);

			update_post_meta( $time_pass_id, '_rg_price', $time_pass_data['price'] );
			update_post_meta( $time_pass_id, '_rg_revenue', $time_pass_data['revenue'] );
			update_post_meta( $time_pass_id, '_rg_duration', $time_pass_data['duration'] );
			update_post_meta( $time_pass_id, '_rg_period', $time_pass_data['period'] );
			update_post_meta( $time_pass_id, '_rg_access_to', $time_pass_data['access_to'] );
			update_post_meta( $time_pass_id, '_rg_custom_title', $time_pass_data['custom_title'] );
			update_post_meta( $time_pass_id, '_rg_custom_desc', $time_pass_data['custom_desc'] );
		}

		return $time_pass_id;
	}

	/**
	 * Get all time passes applicable to content.
	 *
	 * @return array
	 */
	public function get_applicable_time_passes() {
		$timepasses  = [];
		$time_passes = $this->get_time_passes_by_criteria( true );
		foreach ( $time_passes as $time_pass ) {
			$timepasses[] = $time_pass;
		}

		return $timepasses;
	}

	/**
	 * Delete time pass by ID.
	 *
	 * @param int $time_pass_id Time Pass ID.
	 *
	 * @return bool
	 */
	private function delete_time_pass( $time_pass_id ) {
		$post = null;
		if ( ! empty( $time_pass_id ) ) {
			$args = [
				'ID'          => $time_pass_id,
				'post_status' => 'draft',
			];
			$post = wp_update_post( $args );
		}

		return ( is_wp_error( $post ) || empty( $post ) ) ? false : true;
	}

	/**
	 * Remove the time pass.
	 *
	 * @param int $time_pass_id Time Pass ID.
	 * @param int $paywall_id   Paywall ID.
	 *
	 * @return boolean
	 */
	public function remove_time_pass_purchase_option( $time_pass_id, $paywall_id ) {
		if ( $this->delete_time_pass( $time_pass_id ) ) {
			if ( ! empty( $paywall_id ) ) {
				// Unset order if found.
				$current_order = get_post_meta( $paywall_id, '_rg_options_order', true );
				if ( isset( $current_order[ 'tlp_' . $time_pass_id ] ) ) {
					unset( $current_order[ 'tlp_' . $time_pass_id ] );
					update_post_meta( $paywall_id, '_rg_options_order', $current_order );
				}
			}

			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get requested time pass by id.
	 *
	 * @param int $time_pass_id Time Pass ID.
	 *
	 * @return array time pass data.
	 */
	public function get_time_pass_by_id( $time_pass_id ) {
		return $this->formatted_time_pass( get_post( $time_pass_id ) );
	}

	/**
	 * Get time pass ids in a paywall.
	 *
	 * @param array $paywall_options Paywall order data.
	 *
	 * @return array
	 */
	public function get_time_pass_ids( $paywall_options ) {
		if ( ! empty( $paywall_options ) ) {
			$time_pass_ids = array_map(
				function ( $paywall_option ) {
					if ( false !== strpos( $paywall_option, 'tlp_' ) ) {
						$time_pass_data = explode( 'tlp_', $paywall_option );
						if ( ! empty( $time_pass_data[1] ) ) {
							return absint( $time_pass_data[1] );
						}
					}

					return '';
				},
				$paywall_options
			);

			return array_filter( $time_pass_ids, 'strlen' );
		}

		return [];
	}

}

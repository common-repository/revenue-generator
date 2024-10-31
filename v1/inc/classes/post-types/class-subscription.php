<?php
/**
 * Register Subscription post type.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc\Post_Types;

defined( 'ABSPATH' ) || exit;

/**
 * Class Subscription
 */
class Subscription extends Base {

	/**
	 * Slug of post type.
	 *
	 * @var string
	 */
	const SLUG = 'rg_subscription';

	/**
	 * Token of post type.
	 *
	 * @var string
	 */
	const TOKEN = 'sub';

	/**
	 * To get list of labels for subscription post type.
	 *
	 * @return array
	 */
	public function get_labels() {

		return [
			'name'          => __( 'Subscriptions', 'revenue-generator' ),
			'singular_name' => __( 'Subscription', 'revenue-generator' ),
		];

	}

	/**
	 * Get count of existing subscriptions.
	 *
	 * @param bool $ignore_deleted to get count of deleted subscription or not.
	 *
	 * @return int number of defined subscriptions
	 */
	public static function get_subscriptions_count( $ignore_deleted = false ) {

		$subscriptions_count = wp_count_posts( static::SLUG );

		$result = ( ( true === $ignore_deleted ) ? $subscriptions_count->publish : $subscriptions_count->publish + $subscriptions_count->draft );

		return absint( $result );
	}

	/**
	 * Get all active subscriptions.
	 *
	 * @return array of subscriptions
	 */
	public function get_active_subscriptions() {
		return $this->get_all_subscriptions( true );
	}

	/**
	 * Get all subscriptions id.
	 *
	 * @return array of subscriptions ids.
	 */
	public function get_all_subscription_tokenized_ids() {
		$subscription_ids  = [];
		$all_subscriptions = $this->get_all_subscriptions();
		foreach ( $all_subscriptions as $subscription ) {
			$subscription_ids[] = $this->tokenize_subscription_id( $subscription['id'] );
		}

		return $subscription_ids;
	}

	/**
	 * Get all active subscriptions id.
	 *
	 * @return array of subscriptions ids.
	 */
	public function get_active_subscription_tokenized_ids() {
		$subscription_ids     = [];
		$active_subscriptions = $this->get_active_subscriptions();
		foreach ( $active_subscriptions as $subscription ) {
			$subscription_ids[] = $this->tokenize_subscription_id( $subscription['id'] );
		}

		return $subscription_ids;
	}

	/**
	 * Get inactive subscriptions id.
	 *
	 * @return array of subscriptions ids.
	 */
	public function get_inactive_subscription_tokenized_ids() {
		$subscription_ids  = [];
		$all_subscriptions = $this->get_all_subscriptions();
		foreach ( $all_subscriptions as $subscription ) {
			if ( empty( $subscription['is_active'] ) ) {
				$subscription_ids[] = $this->tokenize_subscription_id( $subscription['id'] );
			}
		}

		return $subscription_ids;
	}

	/**
	 * Get tokenized subsription id.
	 *
	 * @param int $id Subscription ID.
	 *
	 * @return string
	 */
	public function tokenize_subscription_id( $id ) {
		return sprintf( '%s_%s', self::TOKEN, $id );
	}

	/**
	 * Get all subscriptions.
	 *
	 * @param bool $ignore_deleted ignore deleted subscriptions.
	 *
	 * @return array list of subscriptions
	 */
	public function get_all_subscriptions( $ignore_deleted = false ) {

		$query_args = [
			'post_type'      => static::SLUG,
			'post_status'    => [ 'publish', 'draft' ],
			'posts_per_page' => 100,
			'no_found_rows'  => true,
		];

		// Don't include the deleted subscriptions.
		if ( $ignore_deleted ) {
			$query_args['post_status'] = 'publish';
		}


		// Initialize WP_Query without args.
		$get_subscriptions = new \WP_Query();

		// Get posts for requested args.
		$posts         = $get_subscriptions->query( $query_args );
		$subscriptions = [];

		foreach ( $posts as $key => $post ) {
			if ( 'draft' === $post->post_status ) {
				$is_expired = $this->is_expired_duration( $post );
				if ( ! $is_expired ) {
					continue;
				}
			}
			$subscriptions[ $key ] = $this->formatted_subscription( $post );
		}

		return $subscriptions;
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
	 * Returns relevant fields for subscription of given WP_Post
	 *
	 * @param \WP_Post $post Post to transform.
	 *
	 * @return array Subscription instance as array
	 */
	private function formatted_subscription( $post ) {

		$post_meta = get_post_meta( $post->ID );
		$is_active = ( 'draft' === $post->post_status ) ? 0 : 1;

		$post_meta = $this->formatted_post_meta( $post_meta );

		$subscription                  = [];
		$subscription['id']            = $post->ID;
		$subscription['title']         = $post->post_title;
		$subscription['description']   = $post->post_content;
		$subscription['price']         = $post_meta['price'];
		$subscription['revenue']       = $post_meta['revenue'];
		$subscription['duration']      = $post_meta['duration'];
		$subscription['period']        = $post_meta['period'];
		$subscription['is_active']     = $is_active;
		$subscription['access_to']     = $post_meta['access_to'];
		$subscription['access_entity'] = $post_meta['access_entity'];
		$subscription['custom_title']  = $post_meta['custom_title'];
		$subscription['custom_desc']   = $post_meta['custom_desc'];

		return $subscription;
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
		 * _rg_access_to - store the content to which the subscription will allow access, can be category / all.
		 * _rg_access_entity - store the id to which the subscription will allow access if access to is category.
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
	 * Get all subscriptions that apply to a given post.
	 *
	 * @param bool $ignore_deleted ignore deleted subscriptions.
	 *
	 * @return array $subscriptions list of subscriptions
	 */
	public function get_subscriptions_by_criteria( $ignore_deleted = true ) {

		$query_args = [
			'post_type'      => static::SLUG,
			'post_status'    => [ 'publish', 'draft' ],
			'posts_per_page' => 100,
			'no_found_rows'  => true,
		];

		$meta_query = [
			'relation' => 'OR',
			[
				'key'     => '_rg_access_to',
				'value'   => 'all',
				'compare' => '=',
			],
		];

		if ( $ignore_deleted ) {
			$query_args['post_status'] = 'publish';
		}

		// Meta query used to get all subscriptions which are applicable.
		$query_args['meta_query'] = $meta_query;

		// Initialize WP_Query without args.
		$get_subscriptions_query = new \WP_Query();

		// Get posts for requested args.
		$posts         = $get_subscriptions_query->query( $query_args );
		$subscriptions = [];

		foreach ( $posts as $key => $post ) {
			$subscriptions[ $key ] = $this->formatted_subscription( $post );
		}

		return $subscriptions;
	}

	/**
	 * Update subscription information.
	 *
	 * @param array $subscription_data Subscription data.
	 *
	 * @return int|\WP_Error
	 */
	public function update_subscription( $subscription_data ) {
		if ( empty( $subscription_data['id'] ) ) {
			$subscription_id = wp_insert_post(
				[
					'post_content' => $subscription_data['description'],
					'post_title'   => $subscription_data['title'],
					'post_status'  => 'publish',
					'post_type'    => static::SLUG,
					'meta_input'   => [
						'_rg_price'        => $subscription_data['price'],
						'_rg_revenue'      => $subscription_data['revenue'],
						'_rg_duration'     => $subscription_data['duration'],
						'_rg_period'       => $subscription_data['period'],
						'_rg_access_to'    => $subscription_data['access_to'],
						'_rg_custom_title' => $subscription_data['custom_title'],
						'_rg_custom_desc'  => $subscription_data['custom_desc'],
					],
				]
			);
		} else {
			$subscription_id = $subscription_data['id'];
			wp_update_post(
				[
					'ID'           => $subscription_id,
					'post_content' => $subscription_data['description'],
					'post_title'   => $subscription_data['title'],
				]
			);

			update_post_meta( $subscription_id, '_rg_price', $subscription_data['price'] );
			update_post_meta( $subscription_id, '_rg_revenue', $subscription_data['revenue'] );
			update_post_meta( $subscription_id, '_rg_duration', $subscription_data['duration'] );
			update_post_meta( $subscription_id, '_rg_access_to', $subscription_data['access_to'] );
			update_post_meta( $subscription_id, '_rg_period', $subscription_data['period'] );
			update_post_meta( $subscription_id, '_rg_custom_title', $subscription_data['custom_title'] );
			update_post_meta( $subscription_id, '_rg_custom_desc', $subscription_data['custom_desc'] );
		}

		return $subscription_id;
	}

	/**
	 * Get all subscriptions applicable to content.
	 *
	 * @return array
	 */
	public function get_applicable_subscriptions() {
		$subscriptions     = [];
		$all_subscriptions = $this->get_subscriptions_by_criteria( true );
		foreach ( $all_subscriptions as $subscription ) {
			$subscriptions[] = $subscription;
		}

		return $subscriptions;
	}

	/**
	 * Delete subscription by ID.
	 *
	 * @param int $subscription_id Subscription ID.
	 *
	 * @return bool
	 */
	private function delete_subscription( $subscription_id ) {
		$post = null;
		if ( ! empty( $subscription_id ) ) {
			$args = [
				'ID'          => $subscription_id,
				'post_status' => 'draft',
			];
			$post = wp_update_post( $args );
		}

		return ( is_wp_error( $post ) || empty( $post ) ) ? false : true;
	}

	/**
	 * Remove subscription.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @param int $paywall_id      Paywall ID.
	 *
	 * @return boolean
	 */
	public function remove_subscription_purchase_option( $subscription_id, $paywall_id ) {
		if ( $this->delete_subscription( $subscription_id ) ) {
			if ( ! empty( $paywall_id ) ) {
				// Unset order if found.
				$current_order = get_post_meta( $paywall_id, '_rg_options_order', true );
				if ( isset( $current_order[ 'sub_' . $subscription_id ] ) ) {
					unset( $current_order[ 'sub_' . $subscription_id ] );
					update_post_meta( $paywall_id, '_rg_options_order', $current_order );
				}
			}

			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get requested subscription by id.
	 *
	 * @param int $subscription_id Subscription ID.
	 *
	 * @return array time pass data.
	 */
	public function get_subscription_by_id( $subscription_id ) {
		return $this->formatted_subscription( get_post( $subscription_id ) );
	}


	/**
	 * Get subscription ids in a paywall.
	 *
	 * @param array $paywall_options Paywall order data.
	 *
	 * @return array
	 */
	public function get_subscription_ids( $paywall_options ) {
		if ( ! empty( $paywall_options ) ) {
			$subscription_ids = array_map(
				function ( $paywall_option ) {
					if ( false !== strpos( $paywall_option, 'sub_' ) ) {
						$subscription_data = explode( 'sub_', $paywall_option );
						if ( ! empty( $subscription_data[1] ) ) {
							return absint( $subscription_data[1] );
						}
					}

					return '';
				},
				$paywall_options
			);

			return array_filter( $subscription_ids, 'strlen' );
		}

		return [];
	}
}

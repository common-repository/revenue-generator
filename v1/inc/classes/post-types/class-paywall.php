<?php
/**
 * Register Paywall post type.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc\Post_Types;

use LaterPay\Revenue_Generator\Inc\Categories;
use LaterPay\Revenue_Generator\Inc\Config;
use LaterPay\Revenue_Generator\Inc\Post_Types;
use LaterPay\Revenue_Generator\Inc\View;

defined( 'ABSPATH' ) || exit;

/**
 * Class Paywall
 */
class Paywall extends Base {

	/**
	 * Slug of post type.
	 *
	 * @var string
	 */
	const SLUG = 'rg_paywall';

	/**
	 * To get list of labels for paywall post type.
	 *
	 * @return array
	 */
	public function get_labels() {

		return [
			'name'          => __( 'Paywalls', 'revenue-generator' ),
			'singular_name' => __( 'Paywall', 'revenue-generator' ),
		];

	}

	/**
	 * Create/Update Paywall.
	 *
	 * @param array $paywall_data Paywall Information.
	 *
	 * @return int|\WP_Error
	 */
	public function update_paywall( $paywall_data ) {
		if ( empty( $paywall_data['id'] ) ) {
			$paywall_id = wp_insert_post(
				[
					'post_content' => $paywall_data['description'],
					'post_title'   => $paywall_data['name'],
					'post_status'  => 'publish',
					'post_type'    => static::SLUG,
					'meta_input'   => [
						'_rg_title'          => $paywall_data['title'],
						'_rg_access_to'      => $paywall_data['access_to'],
						'_rg_access_entity'  => $paywall_data['access_entity'],
						'_rg_preview_id'     => $paywall_data['preview_id'],
						'_rg_specific_posts' => $paywall_data['specific_posts'],
					],
				]
			);
		} else {
			$paywall_id = $paywall_data['id'];
			wp_update_post(
				[
					'ID'           => $paywall_id,
					'post_content' => $paywall_data['description'],
					'post_title'   => $paywall_data['name'],
				]
			);

			update_post_meta( $paywall_id, '_rg_title', $paywall_data['title'] );
			update_post_meta( $paywall_id, '_rg_access_to', $paywall_data['access_to'] );
			update_post_meta( $paywall_id, '_rg_access_entity', $paywall_data['access_entity'] );
			update_post_meta( $paywall_id, '_rg_preview_id', $paywall_data['preview_id'] );
			update_post_meta( $paywall_id, '_rg_specific_posts', $paywall_data['specific_posts'] );
		}

		// If paywall is being added based on categories make sure to create meta to identify it.
		if ( ( ! empty( $paywall_data['access_to'] ) && ! empty( $paywall_data['access_entity'] ) ) ) {
			if ( 'category' === $paywall_data['access_to'] || 'exclude_category' === $paywall_data['access_to'] ) {
				update_term_meta( $paywall_data['access_entity'], '_rg_has_paywall', $paywall_data['access_to'] );
			} elseif ( 'supported' === $paywall_data['access_to'] ) {
				update_post_meta( $paywall_data['access_entity'], '_rg_has_paywall', 'true' );
			}
		}

		return $paywall_id;
	}

	/**
	 * Update individual purchase option for the paywall.
	 *
	 * @param int   $paywall_id      Paywall ID.
	 * @param array $individual_data Individual option data.
	 */
	public function update_paywall_individual_option( $paywall_id, $individual_data ) {
		update_post_meta( $paywall_id, '_rg_individual_option', $individual_data );
	}

	/**
	 * Update the order of purchase options in paywall.
	 *
	 * @param int   $paywall_id Paywall ID.
	 * @param array $order_data Purchase option data.
	 */
	public function update_paywall_option_order( $paywall_id, $order_data ) {
		update_post_meta( $paywall_id, '_rg_options_order', $order_data );
	}

	/**
	 * Get related paywall information.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array
	 */
	public function get_purchase_option_data_by_post_id( $post_id ) {
		$paywall_info = [];
		$query_args   = [
			'post_type'      => static::SLUG,
			'post_status'    => [ 'publish' ],
			'posts_per_page' => 1,
		];

		$meta_query = [
			'relation' => 'AND',
			[
				'key'     => '_rg_access_entity',
				'compare' => '=',
				'value'   => $post_id,
			],
			[
				'key'     => '_rg_access_to',
				'value'   => 'supported',
				'compare' => '=',
			],
		];

		$query_args['meta_query'] = $meta_query;

		$query = new \WP_Query( $query_args );

		$current_post = $query->posts;

		if ( ! empty( $current_post[0] ) ) {
			$pay_wall                      = $current_post[0];
			$paywall_info['id']            = $pay_wall->ID;
			$paywall_info['name']          = $pay_wall->post_title;
			$paywall_info['description']   = $pay_wall->post_content;
			$paywall_info['title']         = get_post_meta( $pay_wall->ID, '_rg_title', true );
			$paywall_info['access_to']     = get_post_meta( $pay_wall->ID, '_rg_access_to', true );
			$paywall_info['access_entity'] = get_post_meta( $pay_wall->ID, '_rg_access_entity', true );
			$paywall_info['preview_id']    = get_post_meta( $pay_wall->ID, '_rg_preview_id', true );
			$paywall_info['order']         = get_post_meta( $pay_wall->ID, '_rg_options_order', true );
		}

		return $paywall_info;
	}

	/**
	 * Get paywall based on specific post it has been applied.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return int | false if no paywall found.
	 */
	public function get_paywall_for_specific_post( $post_id ) {

		$query_args = [
			'post_type'      => static::SLUG,
			'post_status'    => [ 'publish' ],
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'orderby'        => 'modified',
			'order'          => 'DESC',
		];

		$meta_query = [
			'relation' => 'AND',
			[
				'key'     => '_rg_specific_posts',
				'compare' => 'LIKE',
				'value'   => '"' . $post_id . '"',
			],
			[
				'key'     => '_rg_access_to',
				'value'   => 'specific_post',
				'compare' => '=',
			],
		];

		$query_args['meta_query'] = $meta_query;

		$query = new \WP_Query( $query_args );

		if ( ! empty( $query->posts ) ) {
			$paywall_id = $query->posts;
			return $paywall_id[0];
		}

		return false;
	}

	/**
	 * Get individual pricing information.
	 *
	 * @param int $paywall_id Paywall ID.
	 *
	 * @return mixed
	 */
	public function get_individual_purchase_option_data( $paywall_id ) {
		return get_post_meta( $paywall_id, '_rg_individual_option', true );
	}

	/**
	 * Remove purchase option data.
	 *
	 * @param int $paywall_id Paywall ID.
	 */
	public function remove_individual_purchase_option( $paywall_id ) {
		if ( ! empty( $paywall_id ) ) {
			$this->update_paywall_individual_option( $paywall_id, [] );

			// Unset order if found.
			$current_order = get_post_meta( $paywall_id, '_rg_options_order', true );
			if ( isset( $current_order['individual'] ) ) {
				unset( $current_order['individual'] );
				update_post_meta( $paywall_id, '_rg_options_order', $current_order );
			}
		}
	}

	/**
	 * Get related paywall information.
	 *
	 * @param int $paywall_id Paywall ID.
	 *
	 * @return array
	 */
	public function get_purchase_option_data_by_paywall_id( $paywall_id ) {
		$paywall_info = [];
		$paywall      = get_post( $paywall_id );
		if ( ! empty( $paywall ) ) {
			if ( static::SLUG === $paywall->post_type ) {
				$paywall_info['id']             = $paywall->ID;
				$paywall_info['name']           = $paywall->post_title;
				$paywall_info['description']    = $paywall->post_content;
				$paywall_info['title']          = get_post_meta( $paywall->ID, '_rg_title', true );
				$paywall_info['access_to']      = get_post_meta( $paywall->ID, '_rg_access_to', true );
				$paywall_info['access_entity']  = get_post_meta( $paywall->ID, '_rg_access_entity', true );
				$paywall_info['preview_id']     = get_post_meta( $paywall->ID, '_rg_preview_id', true );
				$paywall_info['order']          = get_post_meta( $paywall->ID, '_rg_options_order', true );
				$paywall_info['is_active']      = get_post_meta( $paywall->ID, '_rg_is_active', true );
				$paywall_info['specific_posts'] = get_post_meta( $paywall->ID, '_rg_specific_posts', true );
			}
		}

		return $paywall_info;
	}

	/**
	 * Remove the paywall.
	 *
	 * @param int $paywall_id Paywall ID.
	 *
	 * @return array
	 */
	public function remove_paywall( $paywall_id ) {
		$post_types = Post_Types::get_instance();
		if ( ! empty( $paywall_id ) ) {
			$paywall_data = $this->get_purchase_option_data_by_paywall_id( $paywall_id );

			$access_to     = $paywall_data['access_to'];
			$access_entity = $paywall_data['access_entity'];

			// Remove category meta.
			if ( 'category' === $access_to || 'exclude_category' === $access_to ) {
				$category_instance = Categories::get_instance();
				$category_instance->clear_category_paywall_meta( $access_entity );
			} elseif ( 'supported' === $access_to ) {
				$paywall_instance = Post_Types::get_instance();
				$paywall_instance->clear_post_paywall_meta( $access_entity );
			}

			$preview_id = $paywall_data['preview_id'];

			if ( empty( $preview_id ) ) {
				$preview_id = $post_types->get_latest_post_for_preview();
			}

			// Delete the paywall.
			$result = wp_delete_post( $paywall_id, true );
			if ( empty( $result ) ) {
				return [
					'success'         => false,
					'preview_post_id' => $preview_id,
				];
			} else {
				return [
					'success'         => true,
					'preview_post_id' => $preview_id,
				];
			}
		}

		return [
			'success'         => false,
			'preview_post_id' => $post_types->get_latest_post_for_preview(),
		];
	}

	/**
	 * Get paywall related to the post id.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool|mixed
	 */
	public function get_connected_paywall_by_post( $post_id ) {
		$paywall_info = $this->get_purchase_option_data_by_post_id( $post_id );
		if ( empty( $paywall_info['id'] ) ) {
			return false;
		} else {
			return $paywall_info['id'];
		}
	}

	/**
	 * Disable the paywall and update activation meta.
	 *
	 * @param int $paywall_id Paywall Id.
	 *
	 * @return bool|int
	 */
	public function disable_paywall( $paywall_id ) {
		if ( ! empty( $paywall_id ) ) {
			return update_post_meta( $paywall_id, '_rg_is_active', 0 );
		}

		return false;
	}

	/**
	 * Activate the paywall and update activation meta.
	 *
	 * @param int $paywall_id Paywall Id.
	 */
	public function activate_paywall( $paywall_id ) {
		if ( ! empty( $paywall_id ) ) {
			update_post_meta( $paywall_id, '_rg_is_active', 1 );
		}
	}

	/**
	 * Get paywall related to the category data.
	 *
	 * @param array $categories Categories Data.
	 *
	 * @return string|\WP_Post
	 */
	public function get_connected_paywall_by_categories( $categories ) {
		$paywall_id = '';
		foreach ( $categories as $category_id ) {
			$paywall_id = $this->get_purchase_option_data_by_category_id( $category_id );

			// If no paywall found check for paywall in parent category.
			if ( empty( $paywall_id ) ) {
				$parent_id       = false;
				$category_object = get_category( $category_id );

				// Verify the category exists before accessing the parent info.
				if ( ! is_wp_error( $category_object ) && ! empty( $category_object ) && isset( $category_object->parent ) ) {
					$parent_id = $category_object->parent;
				}

				while ( $parent_id ) {
					$paywall_id = $this->get_purchase_option_data_by_category_id( $parent_id );
					if ( empty( $paywall_id ) ) {
						$parent_id = get_category( $parent_id )->parent;
						continue;
					} else {
						return $paywall_id;
					}
					break;
				}
			} else {
				return $paywall_id;
			}
		}

		return $paywall_id;
	}

	/**
	 * Get related Paywall ID for excluded categories.
	 *
	 * @param array $categories Categories array.
	 *
	 * @return string|\WP_Post
	 */
	public function get_connected_paywall_in_excluded_categories( $categories ) {

		$paywalls         = array();
		$is_post_excluded = false; // flag to check one of mutiple categories is excluded.

		if ( ! empty( $categories ) && is_array( $categories ) ) {

			foreach ( $categories as $category ) {
				$query_args = [
					'post_type'      => static::SLUG,
					'post_status'    => [ 'publish' ],
					'posts_per_page' => 1,
				];

				$meta_query = [
					'relation' => 'AND',
					[
						'key'     => '_rg_access_entity',
						'compare' => 'NOT LIKE',
						'value'   => '"' . $category . '"',
					],
					[
						'key'     => '_rg_access_to',
						'value'   => 'exclude_category',
						'compare' => '=',
					],
					[
						'key'     => '_rg_is_active',
						'value'   => '1',
						'compare' => '=',
					],
				];

				$query_args['meta_query'] = $meta_query;

				$paywall_posts = new \WP_Query( $query_args );

				$paywalls = $paywall_posts->posts;

				// Set flag to true when any one of category returns with no paywall.
				if ( empty( $paywalls ) && ! $is_post_excluded ) {
					$is_post_excluded = true;
				}
			}
		}

		// if paywall exists and post is not in excluded category return it.
		if ( ! empty( $paywalls[0] ) && ! $is_post_excluded ) {
			return $paywalls[0]->ID;
		}

		return '';
	}

	/**
	 * Get related Paywall ID for only posts.
	 *
	 * @return string|\WP_Post
	 */
	public function get_paywall_for_only_posts() {
		$query_args = [
			'post_type'      => static::SLUG,
			'post_status'    => [ 'publish' ],
			'posts_per_page' => 1,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		];

		$meta_query = [
			'relation' => 'AND',
			[
				'key'     => '_rg_access_to',
				'value'   => 'posts',
				'compare' => '=',
			],
			[
				'key'     => '_rg_is_active',
				'value'   => '1',
				'compare' => '=',
			],
		];

		$query_args['meta_query'] = $meta_query;

		$query = new \WP_Query( $query_args );

		$current_post = $query->posts;

		if ( ! empty( $current_post[0] ) ) {
			return $current_post[0]->ID;
		}

		return '';
	}

	/**
	 * Get related Paywall ID for all posts.
	 *
	 * @return string|\WP_Post
	 */
	public function get_paywall_for_all_posts() {
		$query_args = [
			'post_type'      => static::SLUG,
			'post_status'    => [ 'publish' ],
			'posts_per_page' => 1,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		];

		$meta_query = [
			'relation' => 'AND',
			[
				'key'     => '_rg_access_to',
				'value'   => 'all',
				'compare' => '=',
			],
			[
				'key'     => '_rg_is_active',
				'value'   => '1',
				'compare' => '=',
			],
		];

		$query_args['meta_query'] = $meta_query;

		$query = new \WP_Query( $query_args );

		$current_post = $query->posts;

		if ( ! empty( $current_post[0] ) ) {
			return $current_post[0]->ID;
		}

		return '';
	}

	/**
	 * Get Paywall ID for all posts.
	 *
	 * @param string $post_type Post type slug.
	 *
	 * @return string|\WP_Post
	 */
	public function get_paywall_for_post_type( $post_type = '' ) {
		if ( empty( $post_type ) ) {
			return '';
		}

		$query_args = [
			'post_type'      => static::SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		];

		$meta_query = [
			'relation' => 'AND',
			[
				'key'     => '_rg_access_to',
				'value'   => $post_type,
				'compare' => '=',
			],
			[
				'key'     => '_rg_is_active',
				'value'   => '1',
				'compare' => '=',
			],
		];

		$query_args['meta_query'] = $meta_query;

		$query = new \WP_Query( $query_args );

		$current_post = $query->posts;

		if ( ! empty( $current_post[0] ) ) {
			return $current_post[0]->ID;
		}

		return '';
	}

	/**
	 * Get related Paywall ID for category.
	 *
	 * @param int $category_id Category ID.
	 *
	 * @return string|int
	 */
	public function get_purchase_option_data_by_category_id( $category_id ) {
		$query_args = [
			'post_type'      => static::SLUG,
			'post_status'    => [ 'publish' ],
			'posts_per_page' => -1,
		];

		$meta_query = [
			'relation' => 'AND',
			[
				'key'     => '_rg_access_entity',
				'compare' => 'LIKE',
				'value'   => '"' . $category_id . '"',
			],
			[
				'key'     => '_rg_access_to',
				'value'   => 'category',
				'compare' => '=',
			],
			[
				'key'     => '_rg_is_active',
				'value'   => '1',
				'compare' => '=',
			],
		];

		$query_args['meta_query'] = $meta_query;

		$query = new \WP_Query( $query_args );

		$paywall_posts = $query->posts;

		$new_paywall_order = array();
		foreach ( $paywall_posts as $paywall_post ) {
			$paywall_categories                             = get_post_meta( $paywall_post->ID, '_rg_access_entity', true );
			$paywall_categories_count                       = count( $paywall_categories );
			$new_paywall_order[ $paywall_categories_count ] = $paywall_post;
		}

		// Sort them by keys ( number of categories ).
		ksort( $new_paywall_order );

		// reset keys.
		array_multisort( $new_paywall_order, SORT_ASC );

		if ( ! empty( $new_paywall_order[0] ) ) {
			return $new_paywall_order[0]->ID;
		}

		return '';
	}

	/**
	 * Get all paywalls.
	 *
	 * @param array $paywall_args Paywall search args.
	 *
	 * @return array
	 */
	public function get_all_paywalls( $paywall_args ) {
		$query_args = [
			'post_type'      => static::SLUG,
			'post_status'    => [ 'publish' ],
			'posts_per_page' => 100,
			'no_found_rows'  => true,
		];

		// Merge default params and extra args.
		$query_args = array_merge( $query_args, $paywall_args );

		// Initialize WP_Query without args.
		$get_paywalls_query = new \WP_Query();

		// Get posts for requested args.
		$posts    = $get_paywalls_query->query( $query_args );
		$paywalls = [];

		foreach ( $posts as $key => $post ) {
			$paywalls[ $key ] = $this->formatted_paywall( $post );
		}

		return $paywalls;
	}

	/**
	 * Get user id of the user who last updated the paywall.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string|int
	 */
	private function get_last_modified_author_id( $post_id ) {
		$last_id = get_post_meta( $post_id, '_edit_last', true );
		if ( $last_id ) {
			return $last_id;
		}

		return '';
	}

	/**
	 * Returns relevant fields for paywalls of given WP_Post
	 *
	 * @param \WP_Post $post Post to transform.
	 *
	 * @return array Time Pass instance as array
	 */
	private function formatted_paywall( $post ) {
		$post_meta          = get_post_meta( $post->ID );
		$post_meta          = $this->formatted_post_meta( $post_meta );
		$last_modified_user = $this->get_last_modified_author_id( $post->ID );
		$post_author        = empty( $last_modified_user ) ? $post->post_author : $last_modified_user;
		$post_modified_date = get_the_modified_date( '', $post->ID );
		$post_modified_time = get_the_modified_time( '', $post->ID );
		$post_updated_info  = sprintf(
			/* translators: %1$s modified date, %2$s modified time */
			__( 'Last updated on %1$s at %2$s by %3$s' ),
			$post_modified_date,
			$post_modified_time,
			get_the_author_meta( 'display_name', $post_author )
		);

		$pay_wall                      = [];
		$pay_wall['id']                = $post->ID;
		$pay_wall['name']              = $post->post_title;
		$pay_wall['description']       = $post->post_content;
		$pay_wall['title']             = $post_meta['title'];
		$pay_wall['access_to']         = $post_meta['access_to'];
		$pay_wall['access_entity']     = $post_meta['access_entity'];
		$pay_wall['is_active']         = $post_meta['is_active'];
		$pay_wall['updated_timestamp'] = strtotime( "{$post_modified_date} $post_modified_time" );
		$pay_wall['updated']           = $post_updated_info;
		$is_active                     = 1 === absint( $pay_wall['is_active'] ) ? true : false;
		$saved_message                 = $is_active ? __( 'Published', 'revenue-generator' ) : __( 'Saved', 'revenue-generator' );

		// Compose message based on paywall attributes.
		if ( 'category' === $pay_wall['access_to'] || 'exclude_category' === $pay_wall['access_to'] ) {

			$categories_id = '';

			// backward compatibility.
			if ( isset( $pay_wall['access_entity'] ) && is_serialized( $pay_wall['access_entity'] ) ) {
				$categories_id = maybe_unserialize( $pay_wall['access_entity'] );
			} else {
				$categories_id = array( $pay_wall['access_entity'] );
			}

			$categories_message = '';

			if ( ! empty( $categories_id ) && is_array( $categories_id ) ) {
				$categories_in_message = array();
				foreach ( $categories_id as $category_id ) {
					$category_object         = get_category( $category_id );
					$categories_in_message[] = $category_object->name;
				}
				$categories_message = implode( ', ', $categories_in_message );
			}

			if ( 'category' === $pay_wall['access_to'] ) {
				$published_on = sprintf(
					/* translators: %1$s static string PUBLISHED/SAVED, %2$s category name */
					__( '<b>%1$s</b> on <b>all posts</b> in the category <b>%2$s</b>', 'revenue-generator' ),
					$saved_message,
					$categories_message
				);
			} else {
				$published_on = sprintf(
					/* translators: %1$s static string PUBLISHED/SAVED, %2$s category name */
					__( '<b>%1$s</b> on <b>all posts</b> except the category <b>%2$s</b>', 'revenue-generator' ),
					$saved_message,
					$categories_message
				);
			}
		} elseif ( 'supported' === $pay_wall['access_to'] ) {
			$rg_post_object = get_post( $pay_wall['access_entity'] );
			$published_on   = sprintf(
				/* translators: %1$s static string PUBLISHED/SAVED, %2$s post name */
				__( '<b>%1$s</b> on <b>post</b> <b>%2$s</b>', 'revenue-generator' ),
				$saved_message,
				$rg_post_object->post_title
			);
		} elseif ( 'specific_post' === $pay_wall['access_to'] ) {
			$published_on = sprintf(
				/* translators: %s static string PUBLISHED/SAVED */
				__( '<b>%s</b> on <b>specific posts & pages</b>', 'revenue-generator' ),
				$saved_message
			);
		} elseif ( in_array( $pay_wall['access_to'], Post_Types::get_allowed_post_types() ) ) {
			$post_type       = Post_Types::get_post_type_object( $pay_wall['access_to'] );
			$post_type_label = __( 'deleted post type', 'revenue-generator' );

			if ( ! empty( $post_type ) ) {
				$post_type_label = strtolower( $post_type->label );
			}

			$published_on = sprintf(
				/* translators: %1$s static string PUBLISHED/SAVED, %2$s post type label */
				__( '<b>%1$s</b> on <b>all %2$s</b>', 'revenue-generator' ),
				$saved_message,
				$post_type_label
			);
		} else {
			$published_on = sprintf(
				/* translators: %s static string PUBLISHED/SAVED */
				__( '<b>%s</b> on <b>all posts</b>', 'revenue-generator' ),
				$saved_message
			);
		}

		$pay_wall['published_on'] = $published_on;

		return $pay_wall;
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
		 * _rg_title - store the paywall name.
		 * _rg_access_to - store the content to which the time pass will allow access, can be category / all.
		 * _rg_access_entity - store the entity id.
		 * _rg_is_active - store paywall status.
		 */
		$post_meta_data['title']         = ( isset( $post_meta['_rg_title'][0] ) ) ? $post_meta['_rg_title'][0] : '';
		$post_meta_data['access_to']     = ( isset( $post_meta['_rg_access_to'][0] ) ) ? $post_meta['_rg_access_to'][0] : '';
		$post_meta_data['access_entity'] = ( isset( $post_meta['_rg_access_entity'][0] ) ) ? $post_meta['_rg_access_entity'][0] : '';
		$post_meta_data['is_active']     = ( isset( $post_meta['_rg_is_active'][0] ) ) ? $post_meta['_rg_is_active'][0] : '';

		return $post_meta_data;
	}

	/**
	 * Convert received purchase options data into an ordered array for dashboard for preview.
	 *
	 * @param array $purchase_options Raw un ordered options data.
	 *
	 * @return array
	 */
	public static function convert_to_purchase_options_preview( $purchase_options ) {
		$final_purchase_options = [];
		$options                = [];

		$final_purchase_options['paywall'] = isset( $purchase_options['paywall'] ) ? $purchase_options['paywall'] : [];
		$individual_purchase_option        = isset( $purchase_options['individual'] ) ? $purchase_options['individual'] : [];
		$time_passes_purchase_option       = isset( $purchase_options['time_passes'] ) ? $purchase_options['time_passes'] : [];
		$subscriptions_purchase_option     = isset( $purchase_options['subscriptions'] ) ? $purchase_options['subscriptions'] : [];

		// Remove data not required for preview.
		unset(
			$final_purchase_options['paywall']['name'],
			$final_purchase_options['paywall']['access_to'],
			$final_purchase_options['paywall']['access_entity'],
			$final_purchase_options['paywall']['preview_id']
		);

		// Check if an order exists for the paywall.
		if ( isset( $final_purchase_options['paywall']['order'] ) ) {

			if ( ! empty( $final_purchase_options ) ) {
				$current_orders  = $final_purchase_options['paywall']['order'];
				$new_order_count = count( $current_orders );

				if ( ! empty( $individual_purchase_option ) ) {
					// Remove data not required for preview.
					unset(
						$individual_purchase_option['revenue'],
						$individual_purchase_option['type'],
						$individual_purchase_option['purchase_type']
					);

					// Set order for individual option.
					if ( isset( $current_orders['individual'] ) ) {
						$individual_purchase_option['order'] = $current_orders['individual'];
					} else {
						++ $new_order_count;
						$individual_purchase_option['order'] = $new_order_count;
					}
					$individual_purchase_option['order']         = $current_orders['individual'];
					$individual_purchase_option['purchase_type'] = 'individual';
					$options[]                                   = $individual_purchase_option;
				}

				if ( ! empty( $time_passes_purchase_option ) ) {
					// Set order for time pass option.
					foreach ( $time_passes_purchase_option as $time_pass ) {
						$tlp_id                     = 'tlp_' . $time_pass['id'];
						$time_pass['purchase_type'] = 'timepass';
						if ( isset( $current_orders[ $tlp_id ] ) ) {
							$time_pass['order'] = $current_orders[ $tlp_id ];
						} else {
							++ $new_order_count;
							$time_pass['order'] = $new_order_count;
						}

						// Remove data not required for preview.
						unset(
							$time_pass['id'],
							$time_pass['revenue'],
							$time_pass['duration'],
							$time_pass['period'],
							$time_pass['is_active'],
							$time_pass['access_to'],
							$time_pass['purchase_type'],
							$time_pass['access_entity']
						);

						$options[] = $time_pass;
					}
				}

				if ( ! empty( $subscriptions_purchase_option ) ) {
					// Set order for subscription option.
					foreach ( $subscriptions_purchase_option as $subscription ) {
						$sub_id                        = 'sub_' . $subscription['id'];
						$subscription['purchase_type'] = 'subscription';
						if ( isset( $current_orders[ $sub_id ] ) ) {
							$subscription['order'] = $current_orders[ $sub_id ];
						} else {
							++ $new_order_count;
							$subscription['order'] = $new_order_count;
						}

						// Remove data not required for preview.
						unset(
							$subscription['id'],
							$subscription['revenue'],
							$subscription['duration'],
							$subscription['period'],
							$subscription['is_active'],
							$subscription['access_to'],
							$subscription['purchase_type'],
							$subscription['access_entity']
						);

						$options[] = $subscription;
					}
				}
			}
		} else {
			$order = 1;

			// Add individual order.
			if ( ! empty( $individual_purchase_option ) && ! isset( $individual_purchase_option['order'] ) ) {
				$individual_purchase_option['order'] = $order;

				// Remove data not required for preview.
				unset(
					$individual_purchase_option['revenue'],
					$individual_purchase_option['type']
				);
			}
			$options[] = $individual_purchase_option;

			// Add time pass options order.
			foreach ( $time_passes_purchase_option as $time_pass ) {
				++ $order;
				if ( ! isset( $time_pass['order'] ) ) {
					$time_pass['order'] = $order;
				}

				// Remove data not required for preview.
				unset(
					$time_pass['id'],
					$time_pass['revenue'],
					$time_pass['duration'],
					$time_pass['period'],
					$time_pass['is_active'],
					$time_pass['access_to'],
					$time_pass['access_entity']
				);

				$options[] = $time_pass;
			}

			// Add subscription options order.
			foreach ( $subscriptions_purchase_option as $subscription ) {
				++ $order;
				if ( ! isset( $subscription['order'] ) ) {
					$subscription['order'] = $order;
				}

				// Remove data not required for preview.
				unset(
					$subscription['id'],
					$subscription['revenue'],
					$subscription['duration'],
					$subscription['period'],
					$subscription['is_active'],
					$subscription['access_to'],
					$subscription['access_entity']
				);

				$options[] = $subscription;
			}
		}

		if ( ! empty( $options ) ) {
			// Sort by order.
			$keys = array_column( $options, 'order' );
			if ( ! empty( $keys ) ) {
				array_multisort( $keys, SORT_ASC, $options );
				$final_purchase_options['options'] = $options;
			}
		}

		return $final_purchase_options;
	}

	/**
	 * Create a mini preview of the paywall for dashboard.
	 *
	 * @param int $paywall_id Paywall ID.
	 */
	public static function generate_paywall_mini_preview( $paywall_id ) {
		if ( ! empty( $paywall_id ) ) {

			// Post Types instance.
			$post_types = Post_Types::get_instance();

			// Get paywall options data.
			$purchase_options_data = $post_types->get_post_purchase_options_by_paywall_id( $paywall_id );
			$purchase_options      = self::convert_to_purchase_options_preview( $purchase_options_data );

			// Set currency symbol.
			$config_data = Config::get_global_options();
			$symbol      = '';
			if ( ! empty( $config_data['merchant_currency'] ) ) {
				$symbol = 'USD' === $config_data['merchant_currency'] ? '$' : '€';
			}

			$paywall_preview_data = [
				'paywall_id'            => $paywall_id,
				'purchase_options_data' => $purchase_options,
				'merchant_symbol'       => $symbol,
			];

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output is escaped in template file.
			echo View::render_template( 'backend/dashboard/paywall-preview', $paywall_preview_data );
		} else {
			echo '';
		}
	}

	/**
	 * Get count of existing paywalls.
	 *
	 * @return int number of defined paywalls.
	 */
	public function get_paywalls_count() {
		$paywall_count = wp_count_posts( static::SLUG );
		$result        = $paywall_count->publish;

		return absint( $result );
	}

	/**
	 * Return a sorted list by priority if requested.
	 *
	 * Priority is defined as follows.
	 *
	 * 1 - Paywalls applied to Selected post or page (ordered by last_modified desc)
	 * 2 - Paywalls applied to category (ordered by last_modified desc)
	 * 3 - Paywalls applied to except for category (ordered by last_modified desc)
	 * 4 - Paywalls applied to all posts and pages (ordered by last_modified desc)
	 *
	 * @param array $dashboard_paywalls array of existing paywalls.
	 *
	 * @return array
	 */
	public function sort_paywall_by_priority( $dashboard_paywalls ) {
		if ( ! empty( $dashboard_paywalls ) ) {
			$all_paywalls       = [
				'supported'          => [],
				'specific_post'      => [],
				'category'           => [],
				'categories'         => [],
				'exclude_category'   => [],
				'exclude_categories' => [],
				'posts'              => [],
				'all'                => [],
			];
			$published_paywalls = [];
			$saved_paywalls     = [];

			// Store all paywall data in one place for easier udpates.
			foreach ( $dashboard_paywalls as $paywall ) {
				if ( ! empty( $paywall ) ) {

					// Sort by Number of categories and execept categories.
					if ( ! empty( $paywall['access_to'] ) && 'category' === $paywall['access_to'] && ! empty( $paywall['access_entity'] ) ) {
						$access_entity = maybe_unserialize( $paywall['access_entity'] );
						if ( count( $access_entity ) > 1 ) {
							$all_paywalls['categories'][] = $paywall;
						} else {
							$all_paywalls['category'][] = $paywall;
						}
					} elseif ( ! empty( $paywall['access_to'] ) && 'exclude_category' === $paywall['access_to'] && ! empty( $paywall['access_entity'] ) ) {
						$access_entity = maybe_unserialize( $paywall['access_entity'] );
						if ( count( $access_entity ) > 1 ) {
							$all_paywalls['exclude_categories'][] = $paywall;
						} else {
							$all_paywalls['exclude_category'][] = $paywall;
						}
					} else {
						$all_paywalls[ $paywall['access_to'] ][] = $paywall;
					}
				}
			}

			// Loop through all paywalls, sort them internally by updated date and time first before adding them to final array.
			foreach ( $all_paywalls as $paywall_subtype ) {
				if ( ! empty( $paywall_subtype ) ) {
					$paywall_time_keys = array_column( $paywall_subtype, 'updated_timestamp' );
					array_multisort( $paywall_time_keys, SORT_DESC, $paywall_subtype );

					foreach ( $paywall_subtype as $paywall ) {
						if ( 1 === absint( $paywall['is_active'] ) ) {
							$published_paywalls[] = $paywall;
						} else {
							$saved_paywalls[] = $paywall;
						}
					}
				}
			}

			// Merge published and saved paywalls to create final array of paywalls.
			$sorted_paywalls = array_merge( $published_paywalls, $saved_paywalls );

			if ( ! empty( $sorted_paywalls ) ) {
				return $sorted_paywalls;
			}

			return $dashboard_paywalls;
		}

		return [];
	}

	/**
	 * Get the paywall to be displayed in suggestions based on searched term.
	 *
	 * @param string $search_term The paywall name to search for.
	 *
	 * @return array
	 */
	public function get_paywall_by_name( $search_term ) {
		// Query args for post preview search.
		$query_args = [
			'post_type'        => static::SLUG,
			'post_status'      => 'publish',
			'rg_paywall_title' => $search_term,
			'posts_per_page'   => 5,
		];

		// Add and remove our custom filter for LIKE based search by title.
		add_filter( 'posts_where', [ $this, 'rg_paywall_title_filter' ], 10, 2 );
		$query         = new \WP_Query();
		$current_posts = $query->query( $query_args );
		remove_filter( 'posts_where', [ $this, 'rg_paywall_title_filter' ], 10 );

		// Create formatted data for preview suggestions.
		$preview_posts = [];
		foreach ( $current_posts as $key => $preview_post ) {
			$preview_posts[ $key ] = $this->formatted_paywall( $preview_post );
		}

		return $preview_posts;
	}

	/**
	 * Filter to modify the search of paywall data.
	 *
	 * @param string    $sql   SQL string.
	 * @param \WP_Query $query Query object.
	 *
	 * @return string
	 */
	public function rg_paywall_title_filter( $sql, $query ) {
		global $wpdb;

		// If our custom query var is set modify the query.
		if ( ! empty( $query->query['rg_paywall_title'] ) ) {
			$term = $wpdb->esc_like( $query->query['rg_paywall_title'] );
			$sql  .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . $term . '%\'';
		}

		return $sql;
	}

	/**
	 * Check if there are paywalls in the database.
	 *
	 * @return boolean
	 */
	public static function has_paywalls() {
		$paywalls = get_posts(
			[
				'post_type'      => static::SLUG,
				'post_status'    => 'publish',
				'posts_per_page' => '1',
				'fields'         => 'ID',
			]
		);

		if ( empty( $paywalls ) ) {
			return false;
		}

		return true;
	}

}

<?php
/**
 * Load all classes that registers a post type and define methods to handle supported post types.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc;

use LaterPay\Revenue_Generator\Inc\Traits\Singleton;
use LaterPay\Revenue_Generator\Inc\Post_Types\Time_Pass;
use LaterPay\Revenue_Generator\Inc\Post_Types\Subscription;
use LaterPay\Revenue_Generator\Inc\Post_Types\Paywall;
use LaterPay\Revenue_Generator\Inc\Post_Types\Contribution;
use LaterPay\Revenue_Generator\Inc\Post_Types\Contribution_Preview;

defined( 'ABSPATH' ) || exit;

/**
 * Class Post_Types
 */
class Post_Types {

	use Singleton;

	/**
	 * To store instance of post type.
	 *
	 * @var array List of instance of post type.
	 */
	protected static $instances = [];

	/**
	 * Construct method.
	 */
	protected function __construct() {
		$this->register_post_types();
	}

	/**
	 * To initiate all post type instance.
	 *
	 * @return void
	 */
	protected function register_post_types() {

		self::$instances = [
			Time_Pass::SLUG    => Time_Pass::get_instance(),
			Subscription::SLUG => Subscription::get_instance(),
			Paywall::SLUG      => Paywall::get_instance(),
			Contribution::SLUG => Contribution::get_instance(),
			Contribution_Preview::SLUG => Contribution_Preview::get_instance(),
		];

	}

	/**
	 * To get instance of all post types.
	 *
	 * @return array List of instances of the post types.
	 */
	public static function get_instances() {
		return self::$instances;
	}

	/**
	 * Get slug list of all registered custom post types.
	 *
	 * @return array List of slugs.
	 */
	public static function get_registered_post_types() {
		return array_keys( self::$instances );
	}

	/**
	 * Get slug list of all post types whose content is allowed for sale..
	 *
	 * @return array List of slugs.
	 */
	public static function get_allowed_post_types() {
		$post_types         = get_post_types( [], 'objects' );
		$allowed_post_types = [ 'page' ];

		foreach ( $post_types as $post_type ) {
			if ( in_array( $post_type->name, [ 'attachment', 'wp-block' ] ) ) {
				continue;
			}

			if ( false === $post_type->public ) {
				continue;
			}

			if ( false === $post_type->publicly_queryable ) {
				continue;
			}

			if ( false === $post_type->show_ui ) {
				continue;
			}

			$allowed_post_types[] = $post_type->name;
		}

		return apply_filters( 'rg_allowed_post_types', $allowed_post_types );
	}

	/**
	 * Get the latest published posts ID from supported types, defaults to POST.
	 *
	 * @return bool|int
	 */
	public function get_latest_post_for_preview() {
		// Check for latest post which doesn't have a paywall.
		$query_args = [
			'post_type'      => self::get_allowed_post_types(),
			'post_status'    => [ 'publish' ],
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		];

		// Meta query to get posts without a paywall.
		$meta_query = [
			[
				'key'     => '_rg_has_paywall',
				'compare' => 'NOT EXISTS',
			],
		];

		// Meta query used to get posts without paywall.
		$query_args['meta_query'] = $meta_query;

		// Initialize WP_Query without args.
		$post_preview_query = new \WP_Query();

		// Get posts for requested args.
		$latest_post = $post_preview_query->query( $query_args );

		// Check if latest post has data, if yes return latest ID of supported type.
		if ( ! empty( $latest_post ) && isset( $latest_post[0] ) ) {
			return $latest_post[0]->ID;
		}

		return false;
	}

	/**
	 * Return a structured data for preview in paywall creation.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array
	 */
	public function get_formatted_post_data( $post_id ) {

		if ( empty( $post_id ) ) {
			return [];
		}

		// Get all post data and return select fields for preview.
		$post_data = get_post( $post_id );

		if ( empty( $post_data ) ) {
			return [];
		}

		if ( ! in_array( $post_data->post_type, self::get_allowed_post_types() ) ) {
			return [];
		}

		// Generate a teaser for the post, if excerpt is empty.
		$post_content = do_blocks( $post_data->post_content );

		// Remove all the tags.
		$post_content = wp_strip_all_tags( $post_content );

		// Remove shortcodes.
		$post_content = strip_shortcodes( $post_content );

		$teaser_content = Utility::truncate(
			preg_replace( '/\s+/', ' ', $post_content ),
			Utility::determine_number_of_words( $post_content ),
			[
				'html'  => true,
				'words' => true,
			]
		);

		$post_content_for_preview = Utility::truncate(
			preg_replace( '/\s+/', ' ', $post_content ),
			Utility::determine_number_of_words( $post_content ),
			[
				'html'  => true,
				'words' => true,
			]
		);

		return [
			'ID'           => $post_data->ID,
			'title'        => $post_data->post_title,
			'excerpt'      => $post_data->post_excerpt,
			'teaser'       => $teaser_content,
			'type'         => $post_data->post_type,
			'post_content' => empty( $post_data->post_excerpt ) ? $post_content_for_preview : $post_data->post_excerpt,
		];
	}

	/**
	 * Check and verify if Time Passes and Subscriptions exist or not, if they do send the counts.
	 */
	public function has_purchase_options_available() {
		$time_passes_count   = Time_Pass::get_time_passes_count( true );
		$subscriptions_count = Subscription::get_subscriptions_count( true );

		if ( empty( $time_passes_count ) && empty( $subscriptions_count ) ) {
			return false;
		} else {
			return [
				'time_pass'    => $time_passes_count,
				'subscription' => $subscriptions_count,
			];
		}
	}

	/**
	 * Get pricing options based on post data.
	 *
	 * @param int   $post_id   Post ID.
	 * @param array $post_data Post data for preview.
	 *
	 * @return array
	 */
	public function get_post_purchase_options_by_post_id( $post_id, $post_data ) {

		$purchase_options = $this->get_current_post_purchase_options_by_post_id( $post_id );

		if ( ! empty( $purchase_options ) ) {
			return $purchase_options;
		} else {
			// Global options data.
			$current_global_options = Config::get_global_options();

			// Get information on the post to create a pricing configuration.
			$purchase_options     = [];
			$post_tier            = empty( $post_data['post_content'] ) ? 'tier_1' : $this->get_post_tier( $post_data['post_content'] );
			$purchase_options_all = Config::get_pricing_defaults( $current_global_options['average_post_publish_count'] );

			// Get individual article pricing based on post content word count, i.e "tier".
			$purchase_options['individual']         = $purchase_options_all['single_article'][ $post_tier ];
			$purchase_options['individual']['type'] = 'dynamic';

			// Check if we have existing time passes or subscriptions.
			$current_purchase_options = $this->has_purchase_options_available();

			/**
			 * - If no option exits, set the default pricing values.
			 * - If one of either options exist, add it to the pricing config.
			 */
			if ( false === $current_purchase_options ) {
				$purchase_options['time_passes'][]   = $purchase_options_all['time_pass'];
				$purchase_options['subscriptions'][] = $purchase_options_all['subscription'];

				if ( 'low' === $current_global_options['average_post_publish_count'] ) {
					unset( $purchase_options['time_passes'] );
				}
			} elseif ( $current_purchase_options['subscription'] > 0 && empty( $current_purchase_options['time_pass'] ) ) {
				$purchase_options['time_passes'][] = $purchase_options_all['time_pass'];
				$purchase_options['subscriptions'] = Subscription::get_instance()->get_subscriptions_by_criteria( true );

				if ( 'low' === $current_global_options['average_post_publish_count'] ) {
					unset( $purchase_options['time_passes'] );
				}
			} elseif ( $current_purchase_options['time_pass'] > 0 && empty( $current_purchase_options['subscription'] ) ) {
				$purchase_options['subscriptions'][] = $purchase_options_all['subscription'];
				$purchase_options['time_passes']     = Time_Pass::get_instance()->get_time_passes_by_criteria( true );
			} else {
				$purchase_options['time_passes']   = Time_Pass::get_instance()->get_time_passes_by_criteria( true );
				$purchase_options['subscriptions'] = Subscription::get_instance()->get_subscriptions_by_criteria( true );
			}

			return $purchase_options;
		}
	}

	/**
	 * Get purchase options related to post content.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array
	 */
	public function get_current_post_purchase_options_by_post_id( $post_id ) {
		$paywall_instance      = Paywall::get_instance();
		$time_pass_instance    = Time_Pass::get_instance();
		$subscription_instance = Subscription::get_instance();

		// Get information on the post to create a pricing configuration.
		$purchase_options = [];

		$paywall_data = $paywall_instance->get_purchase_option_data_by_post_id( $post_id );

		// Get paywall data.
		if ( ! empty( $paywall_data ) ) {
			$purchase_options['paywall'] = $paywall_data;
		}

		// Get individual purchase options data.
		if ( ! empty( $purchase_options['paywall'] ) ) {
			$purchase_options['individual'] = $paywall_instance->get_individual_purchase_option_data( $purchase_options['paywall']['id'] );

			// Get time passes and subscriptions purchase options data.
			$time_passes   = $time_pass_instance->get_applicable_time_passes();
			$subscriptions = $subscription_instance->get_applicable_subscriptions();

			if ( ! empty( $time_passes ) ) {
				$purchase_options['time_passes'] = $time_passes;
			}

			if ( ! empty( $subscriptions ) ) {
				$purchase_options['subscriptions'] = $subscriptions;
			}
		}

		return $purchase_options;
	}

	/**
	 * Get post tier based on post content.
	 *
	 * @param string $post_content Content of the paid post.
	 *
	 * @return string
	 */
	public function get_post_tier( $post_content ) {
		// Get content length.
		$content_length = Utility::get_word_count( $post_content );

		// Predefined content limit for tiers.
		if ( $content_length <= 250 ) {
			return 'tier_1';
		} elseif ( $content_length > 250 && $content_length <= 500 ) {
			return 'tier_2';
		} elseif ( $content_length > 500 ) {
			return 'tier_3';
		}

		return 'tier_1';
	}

	/**
	 * Get valid durations.
	 *
	 * @return array array of options
	 */
	public static function get_duration_options() {
		return range( 1, 24 );
	}

	/**
	 * Get valid periods.
	 *
	 * @return array array of options
	 */
	public static function get_period_options() {
		// singular periods.
		$periods = [
			'h' => __( 'Hour', 'revenue-generator' ),
			'd' => __( 'Day', 'revenue-generator' ),
			'w' => __( 'Week', 'revenue-generator' ),
			'm' => __( 'Month', 'revenue-generator' ),
			'y' => __( 'Year', 'revenue-generator' ),
		];

		return $periods;
	}

	/**
	 * Get time pass select options by type.
	 *
	 * @param string $type type of select.
	 *
	 * @return string of options
	 */
	public static function get_select_options( $type ) {
		$options_html  = '';
		$default_value = null;

		switch ( $type ) {
			case 'duration':
				$elements      = self::get_duration_options();
				$default_value = 1;
				break;

			case 'period':
				$elements      = self::get_period_options();
				$default_value = 'd';
				break;

			default:
				return $options_html;
		}

		if ( $elements && is_array( $elements ) ) {
			foreach ( $elements as $id => $name ) {
				$options_html .= sprintf( '<option value="%1$s" %2$s>%3$s</option>', esc_html( $id ), esc_html( selected( $default_value, $id, false ) ), esc_html( $name ) );
			}
		}

		return $options_html;
	}

	/**
	 * Get the default purchase option to be added for new options.
	 *
	 * @return array
	 */
	public function get_default_purchase_option() {
		return Config::default_purchase_option();
	}

	/**
	 * Convert received purchase options data into an ordered array for preview.
	 *
	 * @param array $purchase_options Raw un ordered options data.
	 *
	 * @return array
	 */
	public function convert_to_purchase_options( $purchase_options ) {
		$final_purchase_options = [];
		$options                = [];

		$final_purchase_options['paywall'] = isset( $purchase_options['paywall'] ) ? $purchase_options['paywall'] : [];
		$individual_purchase_option        = isset( $purchase_options['individual'] ) ? $purchase_options['individual'] : [];
		$time_passes_purchase_option       = isset( $purchase_options['time_passes'] ) ? $purchase_options['time_passes'] : [];
		$subscriptions_purchase_option     = isset( $purchase_options['subscriptions'] ) ? $purchase_options['subscriptions'] : [];

		// Check if an order exists for the paywall.
		if ( isset( $final_purchase_options['paywall']['order'] ) ) {

			if ( ! empty( $final_purchase_options ) ) {
				$current_orders  = $final_purchase_options['paywall']['order'];
				$new_order_count = count( $current_orders );

				if ( ! empty( $individual_purchase_option ) ) {
					// Set order for individual option.
					if ( isset( $current_orders['individual'] ) ) {
						$individual_purchase_option['order'] = $current_orders['individual'];
					} else {
						++$new_order_count;
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
							++$new_order_count;
							$time_pass['order'] = $new_order_count;
						}
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
							++$new_order_count;
							$subscription['order'] = $new_order_count;
						}
						$options[] = $subscription;
					}
				}
			}
		} else {
			$order = 1;

			// Add individual order.
			if ( ! empty( $individual_purchase_option ) && ! isset( $individual_purchase_option['order'] ) ) {
				$individual_purchase_option['order']         = $order;
				$individual_purchase_option['purchase_type'] = 'individual';
			}
			$options[] = $individual_purchase_option;

			// Add time pass options order.
			foreach ( $time_passes_purchase_option as $time_pass ) {
				++$order;
				if ( ! isset( $time_pass['order'] ) ) {
					$time_pass['order']         = $order;
					$time_pass['purchase_type'] = 'timepass';
				}
				$options[] = $time_pass;
			}

			// Add subscription options order.
			foreach ( $subscriptions_purchase_option as $subscription ) {
				++$order;
				if ( ! isset( $subscription['order'] ) ) {
					$subscription['order']         = $order;
					$subscription['purchase_type'] = 'subscription';
				}
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
	 * Get purchase options by Paywall ID.
	 *
	 * @param int $paywall_id Paywall ID.
	 *
	 * @return array
	 */
	public function get_post_purchase_options_by_paywall_id( $paywall_id ) {
		$paywall_instance      = Paywall::get_instance();
		$time_pass_instance    = Time_Pass::get_instance();
		$subscription_instance = Subscription::get_instance();
		$purchase_options      = [];

		$paywall_data = $paywall_instance->get_purchase_option_data_by_paywall_id( $paywall_id );

		// Get paywall data.
		if ( ! empty( $paywall_data ) ) {
			$purchase_options['paywall'] = $paywall_data;

			// Get individual purchase options data.
			if ( ! empty( $purchase_options['paywall'] ) ) {
				$purchase_options['individual'] = $paywall_instance->get_individual_purchase_option_data( $purchase_options['paywall']['id'] );
			}

			// @todo make the retrieval conditional to handle access based passes and subscription.
			// Get time passes and subscriptions purchase options data.
			$time_passes   = $time_pass_instance->get_applicable_time_passes();
			$subscriptions = $subscription_instance->get_applicable_subscriptions();

			if ( ! empty( $time_passes ) ) {
				$purchase_options['time_passes'] = $time_passes;
			}

			if ( ! empty( $subscriptions ) ) {
				$purchase_options['subscriptions'] = $subscriptions;
			}
		}

		return $purchase_options;
	}

	/**
	 * Get post content for preview associated in Paywall.
	 *
	 * @param int $paywall_id Paywall ID.
	 *
	 * @return array|string
	 */
	public function get_post_post_content_by_paywall_id( $paywall_id ) {
		$paywall_instance = Paywall::get_instance();
		$paywall_data     = $paywall_instance->get_purchase_option_data_by_paywall_id( $paywall_id );

		// Check paywall data.
		if ( ! empty( $paywall_data ) ) {
			$access_entity  = $paywall_data['access_entity'];
			$access_preview = $paywall_data['preview_id'];

			if ( ! empty( $access_preview ) ) {
				return $this->get_formatted_post_data( $access_preview );
			} else {
				return $this->get_formatted_post_data( $access_entity );
			}
		}

		return '';
	}

	/**
	 * Filter to modify the search of preview content.
	 *
	 * @param string    $sql   SQL string.
	 * @param \WP_Query $query Query object.
	 *
	 * @return string
	 */
	public function rg_preview_title_filter( $sql, $query ) {
		global $wpdb;

		// If our custom query var is set modify the query.
		if ( ! empty( $query->query['rg_preview_title'] ) ) {
			$term = $wpdb->esc_like( $query->query['rg_preview_title'] );
			$sql  .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . $term . '%\'';
		}

		return $sql;
	}

	/**
	 * Get the posts to be displayed in suggestions based on searched term.
	 *
	 * @param string $search_term The post title part to search for.
	 *
	 * @return array
	 */
	public function get_preview_content_selection( $search_term ) {
		// Query args for post preview search.
		$query_args = [
			'post_type'        => self::get_allowed_post_types(),
			'post_status'      => 'publish',
			'rg_preview_title' => $search_term,
			'posts_per_page'   => 5,
		];

		// Add and remove our custom filter for LIKE based search by title.
		add_filter( 'posts_where', [ $this, 'rg_preview_title_filter' ], 10, 2 );
		$query         = new \WP_Query();
		$current_posts = $query->query( $query_args );
		remove_filter( 'posts_where', [ $this, 'rg_preview_title_filter' ], 10 );

		// Create formatted data for preview suggestions.
		$preview_posts = [];
		foreach ( $current_posts as $key => $preview_post ) {
			$preview_posts[ $key ] = $this->formatted_post_for_preview( $preview_post );
		}

		return $preview_posts;
	}

	/**
	 * Returns relevant fields for supported content of given WP_Post.
	 *
	 * @param \WP_Post $post Post to transform.
	 *
	 * @return array
	 */
	private function formatted_post_for_preview( $post ) {
		$rg_post          = [];
		$rg_post['id']    = $post->ID;
		$rg_post['title'] = $post->post_title;
		$rg_post['type']  = $post->post_type;

		return $rg_post;
	}

	/**
	 * Clear post metadata on paywall removal.
	 *
	 * @param int $rg_post_id Post ID to clear meta information from.
	 *
	 * @return bool
	 */
	public function clear_post_paywall_meta( $rg_post_id ) {
		return delete_post_meta( $rg_post_id, '_rg_has_paywall' );
	}

	/**
	 * Get post type label from post type slug.
	 *
	 * @param string $post_type_slug Post type slug.
	 *
	 * @return null|object
	 */
	public static function get_post_type_object( $post_type_slug = '' ) {
		if ( empty( $post_type_slug ) ) {
			return null;
		}

		$post_types = get_post_types( [], 'objects' );

		if ( ! isset( $post_types[ $post_type_slug ] ) ) {
			return null;
		}

		$post_type = $post_types[ $post_type_slug ];

		return $post_type;
	}

}

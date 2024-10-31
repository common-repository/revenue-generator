<?php
/**
 * Revenue Generator Plugin Fronted Post Class.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc;

use LaterPay\Revenue_Generator\Inc\Post_Types;
use LaterPay\Revenue_Generator\Inc\Post_Types\Paywall;
use LaterPay\Revenue_Generator\Inc\Post_Types\Subscription;
use LaterPay\Revenue_Generator\Inc\Post_Types\Time_Pass;
use \LaterPay\Revenue_Generator\Inc\Traits\Singleton;
use \LaterPay\Revenue_Generator\Inc\Post_Types\Contribution_Preview;

defined( 'ABSPATH' ) || exit;

/**
 * Class Frontend_Post
 */
class Frontend_Post {

	use Singleton;

	/**
	 * Paywall ID applicable to the post.
	 *
	 * @var int
	 */
	protected $connected_paywall_id;

	/**
	 * Current content post ID.
	 *
	 * @var int
	 */
	protected $current_post_id;

	/**
	 * Individual article purchase option tile.
	 *
	 * @var string
	 */
	protected $individual_article_title;

	/**
	 * Individual article purchase option description.
	 *
	 * @var string
	 */
	protected $individual_article_description;

	/**
	 * Merchant region.
	 *
	 * @var string
	 */
	protected $merchant_region;

	/**
	 * Merchant currency.
	 *
	 * @var string
	 */
	protected $merchant_currency;

	/**
	 * Merchant API key.
	 *
	 * @var string
	 */
	protected $merchant_api_key;

	/**
	 * Paywall Header Title.
	 *
	 * @var string
	 */
	protected $paywall_header_title;

	/**
	 * Store common values used in the plugin.
	 *
	 * @var array Common values used for api related info throughout the plugin.
	 */
	public static $connector_scripts = [
		'EU' => [
			'sandbox' => [
				'us' => 'https://connector-script.laterpay.net/3.15.0/eu/sbx/app-en-us.js',
				'eu' => 'https://connector-script.laterpay.net/3.15.0/eu/sbx/app-de-de.js',
			],
			'live'    => [
				'us' => 'https://connector-script.laterpay.net/3.15.0/eu/prod/app-en-us.js',
				'eu' => 'https://connector-script.laterpay.net/3.15.0/eu/prod/app-de-de.js',
			],
		],
		'US' => [
			'sandbox' => [
				'us' => 'https://connector-script.uselaterpay.com/3.15.0/us/sbx/app-en-us.js',
				'eu' => 'https://connector-script.uselaterpay.com/3.15.0/us/sbx/app-de-de.js',
			],
			'live'    => [
				'us' => 'https://connector-script.uselaterpay.com/3.15.0/us/prod/app-en-us.js',
				'eu' => 'https://connector-script.uselaterpay.com/3.15.0/us/prod/app-de-de.js',
			],
		],
	];

	/**
	 * Class Client_Account construct method.
	 */
	protected function __construct() {
		$client_account_instance = Client_Account::get_instance();
		$global_options          = Config::get_global_options();
		$is_merchant_verified    = $global_options['is_merchant_verified'];

		// Go ahead if merchant is setup properly.
		if ( ! empty( $is_merchant_verified ) ) {
			$this->merchant_region   = $global_options['merchant_region'];
			$this->merchant_currency = 'US' === $this->merchant_region ? 'USD' : 'EUR';
			$merchant_credentials    = $client_account_instance->get_merchant_credentials();
			if ( ! empty( $merchant_credentials['merchant_key'] ) ) {
				$this->merchant_api_key = $merchant_credentials['merchant_key'];
			}
		}

		$this->setup_hooks( $is_merchant_verified );
	}

	/**
	 * Setup options.
	 */
	protected function setup_hooks() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_connector_assets' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_preview_scripts_and_styles' ] );
		add_action( 'wp_ajax_rg_contribution_contribute', [ $this, 'ajax_contribute_contribution' ] );
		add_action( 'wp_ajax_nopriv_rg_contribution_contribute', [ $this, 'ajax_contribute_contribution' ] );

		$global_options       = Config::get_global_options();
		$is_merchant_verified = $global_options['is_merchant_verified'];

		if ( ! empty( $is_merchant_verified ) ) {
			add_action( 'wp_head', [ $this, 'add_connector_config' ] );
			add_filter( 'the_content', [ $this, 'revenue_generator_post_content' ] );
		}
	}

	/**
	 * Add connector config required for adding paywall on the post.
	 */
	public function add_connector_config() {
		if ( is_singular( Post_Types::get_allowed_post_types() ) ) {
			$appearance_config = $this->get_purchase_overlay_config();
			$deleted_articles  = $this->get_deleted_article_ids();

			$this->current_post_id = ( ! empty( $this->current_post_id ) ) ? $this->current_post_id : get_the_ID();
			$paywall_data          = $this->get_connected_paywall_id( $this->current_post_id );

			// Don't add connector to every page.
			if ( empty( $paywall_data ) ) {
				wp_dequeue_script( 'revenue-generator-classic-connector' );
				return;
			}

			$this->paywall_header_title = ( ! empty( $paywall_data['title'] ) ) ? $paywall_data['title'] : esc_html__( 'Keep Reading', 'revenue-generator' );

			$connector_config = [
				'appearance'   => $appearance_config,
				'translations' => [
					'purchaseOverlay' => [
						'heading'                   => $this->paywall_header_title,
						'currentArticle'            => empty( $this->individual_article_title ) ? esc_html__( 'Access Article Now', 'revenue-generator' ) : esc_html( $this->individual_article_title ),
						'currentArticleDescription' => empty( $this->individual_article_description ) ? esc_html__( 'You\'ll only be charged once you\'ve reached $5.', 'revenue-generator' ) : esc_html( $this->individual_article_description ),
					],
				],
			];

			if ( ! empty( $deleted_articles ) ) {
				$connector_config['articleId'] = $deleted_articles;
			}

			$connector_config['callbacks'] = [
				'onUserHasAccess' => 'revenueGeneratorHideTeaserContent',
			];

			$post_payload_data = $this->get_post_payload();

			if ( ! empty( $post_payload_data ) ) {
				$payload = json_decode( $post_payload_data['payload'] );

				foreach ( $payload as $key => $item ) {
					$connector_config[ $key ] = $item;
				}
			}
			?>
			<script>
				function revenueGeneratorHideTeaserContent() {
					document.querySelector('.lp-teaser-content').style.display = 'none';
				}
			</script>
			<!-- LaterPay Connector In-Page Configuration for appearance layout and deleted purchase options. -->
			<script type="application/json" id="laterpay-connector">
				<?php echo wp_json_encode( $connector_config ); ?>
			</script>
			<?php
			if ( ! empty( $post_payload_data ) ) {
				?>
				<meta property="laterpay:connector:config_token" content="<?php echo esc_attr( $post_payload_data['token'] ); ?>" />
				<?php
			} else {
				wp_dequeue_script( 'revenue-generator-classic-connector' );
			}
		}
	}

	/**
	 * Enqueue the connector script required for paywall.
	 */
	public function register_connector_assets() {
		if ( ! is_singular( Post_Types::get_allowed_post_types() ) && Contribution_Preview::SLUG !== get_post_type() ) {
			return;
		}

		$assets_instance = Assets::get_instance();

		// Enqueue frontend styling for purchase overlay.
		wp_enqueue_style(
			'revenue-generator-frontend',
			REVENUE_GENERATOR_BUILD_URL . 'css/revenue-generator-frontend.css',
			[],
			$assets_instance->get_asset_version( 'css/revenue-generator-frontend.css' )
		);

		// Return early if merchant region is empty or when paywalls feature is disabled.
		if ( empty( $this->merchant_region ) || ! REVENUE_GENERATOR_HAS_PAYWALLS ) {
			return;
		}

		$region_connectors     = self::$connector_scripts[ $this->merchant_region ];
		$region_connector_urls = $region_connectors['live'];

		// If development mode is enabled use snbox environment.
		if ( defined( 'REVENUE_GENERATOR_ENABLE_SANDBOX' ) && true === REVENUE_GENERATOR_ENABLE_SANDBOX ) {
			$region_connector_urls = $region_connectors['sandbox'];
		}

		// @todo make sure to select eu based on locale, once upstream LaterPay starts supporting them.
		$connector_url = $region_connector_urls['us'];

		// Append merchant ID and plugin version to Connector script URL.
		$credentials = Client_Account::get_merchant_credentials();
		$merchant_id = ( ! empty( $credentials ) ) ? $credentials['merchant_id'] : '';

		$connector_url = add_query_arg(
			[
				'wp'     => $merchant_id,
				'revgen' => REVENUE_GENERATOR_VERSION,
			],
			$connector_url
		);

		// Enqueue connector script based on region and environment.
		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NoExplicitVersion -- Upstream script.
		wp_enqueue_script(
			'revenue-generator-classic-connector',
			$connector_url,
			[],
			false,
			true
		);
	}

	/**
	 * Add revenue generator wrapper to add paywall attributes.
	 *
	 * @param string $post_content Post content.
	 *
	 * @return string
	 */
	public function revenue_generator_post_content( $post_content ) {
		// Bail early, if unsupported post type.
		if ( ! is_singular( Post_Types::get_allowed_post_types() ) ) {
			return $post_content;
		}

		// Return early if paywalls feature is disabled.
		if ( ! REVENUE_GENERATOR_HAS_PAYWALLS ) {
			return $post_content;
		}

		$this->current_post_id = ( ! empty( $this->current_post_id ) ) ? $this->current_post_id : get_the_ID();
		$paywall_data          = $this->get_connected_paywall_id( $this->current_post_id );

		if ( empty( $paywall_data ) ) {
			return $post_content;
		}

		$teaser_tempalte = '<div class="lp-teaser-content" data-lp-replacement-content>%s</div>';
		$teaser          = '';
		$rg_post         = get_post();

		if ( ! empty( $rg_post ) ) {
			if ( ! empty( $rg_post->post_excerpt ) ) {
				$teaser = sprintf( $teaser_tempalte, $rg_post->post_excerpt );
			} else {
				$teaser_content = Utility::truncate(
					preg_replace( '/\s+/', ' ', $post_content ),
					Utility::determine_number_of_words( $post_content ),
					[
						'html'  => true,
						'words' => true,
					]
				);
				$teaser_content = wp_kses( $teaser_content, [ 'div', 'p' ] );
				$teaser         = sprintf( $teaser_tempalte, $teaser_content );
			}
		}

		$post_content = sprintf( '<div class="lp-main-content" data-lp-show-on-access>%s</div>', $post_content );
		if ( ! empty( $teaser ) ) {
			$rg_post_content = $teaser . $post_content;
		} else {
			$rg_post_content = $post_content;
		}

		return $rg_post_content;
	}

	/**
	 * Convert purchase option data to connector config.
	 *
	 * @param array  $purchase_option Purchase option data.
	 * @param string $type            Option type.
	 * @param int    $entity_id       Entity id.
	 *
	 * @return array
	 */
	private function covert_to_connector_purchase_option( $purchase_option, $type, $entity_id ) {
		$purchase_option_revenue = 'ppu' === $purchase_option['revenue'] ? 'pay_later' : 'pay_now';
		$merchant_currency       = $this->merchant_currency;
		if ( 'individual' === $type ) {
			return [
				'article_id'  => 'article_' . $entity_id,
				'price'       => [
					'amount'        => Config::get_connector_price( $purchase_option['price'] ),
					'currency'      => $merchant_currency,
					'payment_model' => $purchase_option_revenue,
				],
				'sales_model' => 'single_purchase',
				'title'       => empty( $purchase_option['title'] ) ? esc_html__( 'Access Article Now', 'revenue-generator' ) : $purchase_option['title'],
			];
		} elseif ( 'timepass' === $type ) {
			return [
				'article_id'  => 'tlp_' . $entity_id,
				'price'       => [
					'amount'        => Config::get_connector_price( $purchase_option['price'] ),
					'currency'      => $merchant_currency,
					'payment_model' => $purchase_option_revenue,
				],
				'sales_model' => 'timepass',
				'title'       => $purchase_option['title'],
				'description' => $purchase_option['description'],
				'expiry'      => [
					'unit'  => $purchase_option['duration'],
					'value' => $purchase_option['period'],
				],
			];
		} else {
			return [
				'article_id'  => 'sub_' . $entity_id,
				'price'       => [
					'amount'        => Config::get_connector_price( $purchase_option['price'] ),
					'currency'      => $merchant_currency,
					'payment_model' => $purchase_option_revenue,
				],
				'sales_model' => 'subscription',
				'title'       => $purchase_option['title'],
				'description' => $purchase_option['description'],
				'expiry'      => [
					'unit'  => $purchase_option['duration'],
					'value' => $purchase_option['period'],
				],
			];
		}
	}

	/**
	 * Create appearance configuration for the purchase overlay.
	 *
	 * @return array
	 */
	private function get_purchase_overlay_config() {
		return [
			'variant'             => 'raw-white',
			'primaryColor'        => '#2e2e2e',
			'secondaryColor'      => '#ebebeb',
			'purchaseButtonColor' => '#2e2e2e',
			'showPaymentMethods'  => false,
		];
	}

	/**
	 * Return deleted time pass, subscription and article ids to config, to make sure user has access to content,
	 * even if it was deleted by the merchant.
	 *
	 * @return array
	 */
	private function get_deleted_article_ids() {
		// Check if we have post id set for current post.
		if ( empty( $this->current_post_id ) ) {
			// Post data.
			$rg_post = get_post();

			if ( ! empty( $rg_post ) ) {
				$this->current_post_id = $rg_post->ID;
			} else {
				return [];
			}
		}

		// Bail if post data is not available.
		if ( empty( $this->current_post_id ) ) {
			return [];
		}

		$deleted_options          = [];
		$paywall_instance         = Paywall::get_instance();
		$time_pass_instance       = Time_Pass::get_instance();
		$subscription_instance    = Subscription::get_instance();
		$deleted_time_pass_ids    = $time_pass_instance->get_inactive_time_pass_tokenized_ids();
		$deleted_subscription_ids = $subscription_instance->get_inactive_subscription_tokenized_ids();
		$deleted_options          = array_merge( $deleted_options, $deleted_time_pass_ids, $deleted_subscription_ids );

		// Set correct paywall id for the post.
		$this->get_connected_paywall_id( $this->current_post_id );

		$individual_option_data = $paywall_instance->get_individual_purchase_option_data( $this->connected_paywall_id );

		/**
		 * It is possible user has purchased individual article, which was later deleted by merchant,
		 * we need to make sure user has access to it, if user has purchased then the post will be free.
		 */
		$deleted_options[] = sprintf( 'article_%s', $this->current_post_id );

		if ( ! empty( $individual_option_data ) ) {
			$this->individual_article_title       = isset( $individual_option_data['title'] ) ? $individual_option_data['title'] : '';
			$this->individual_article_description = isset( $individual_option_data['description'] ) ? $individual_option_data['description'] : '';
		}

		return $deleted_options;
	}

	/**
	 * Create final config for supported post.
	 *
	 * @return array
	 */
	public function get_post_payload() {
		// Required class instances.
		$config_data           = Config::get_global_options();
		$post_types            = Post_Types::get_instance();
		$paywall_instance      = Paywall::get_instance();
		$subscription_instance = Subscription::get_instance();
		$time_pass_instance    = Time_Pass::get_instance();

		// Check if we have post id set for current post.
		if ( empty( $this->current_post_id ) ) {
			// Post data.
			$rg_post = get_post();

			if ( ! empty( $rg_post ) ) {
				$this->current_post_id = $rg_post->ID;
			} else {
				return [];
			}
		}

		// Bail if post data is not available.
		if ( empty( $this->current_post_id ) ) {
			return [];
		}

		$purchase_options       = [];
		$final_purchase_options = [];

		// Get applicable paywall based on the post.
		$paywall_data = $this->get_connected_paywall_id( $this->current_post_id );

		// Create purchase options if we have a valid paywall.
		if ( ! empty( $paywall_data ) ) {
			$paywall_options_order = $paywall_data['order'];

			// Get global time passes and subscriptions.
			$active_time_pass_ids    = $time_pass_instance->get_active_time_pass_tokenized_ids();
			$active_subscription_ids = $subscription_instance->get_active_subscription_tokenized_ids();

			// Loop through each active time passes and add to paywall if not set already.
			foreach ( $active_time_pass_ids as $active_time_pass_id ) {
				if ( ! isset( $paywall_options_order[ $active_time_pass_id ] ) ) {
					$paywall_options_order[ $active_time_pass_id ] = count( $paywall_options_order ) + 1;
				}
			}

			// Loop through each active subscriptions and add to paywall if not set already.
			foreach ( $active_subscription_ids as $all_subscription_id ) {
				if ( ! isset( $paywall_options_order[ $all_subscription_id ] ) ) {
					$paywall_options_order[ $all_subscription_id ] = count( $paywall_options_order ) + 1;
				}
			}

			if ( ! empty( $paywall_options_order ) ) {
				// Add individual option to purchase options.
				if ( ! empty( $paywall_options_order['individual'] ) ) {
					$order                      = $paywall_options_order['individual'];
					$individual_purchase_option = $paywall_instance->get_individual_purchase_option_data( $this->connected_paywall_id );

					if ( ! empty( $individual_purchase_option ) ) {
						if ( 'dynamic' === $individual_purchase_option['type'] ) {
							// Get individual article pricing based on post content word count, i.e "tier" for dynamic pricing option.
							$formatted_post_data                   = $post_types->get_formatted_post_data( $this->current_post_id );
							$post_tier                             = empty( $formatted_post_data['post_content'] ) ? 'tier_1' : $post_types->get_post_tier( $formatted_post_data['post_content'] );
							$purchase_options_all                  = Config::get_pricing_defaults( $config_data['average_post_publish_count'] );
							$post_dynamic_pricing_data             = $purchase_options_all['single_article'][ $post_tier ];
							$individual_purchase_option['price']   = $post_dynamic_pricing_data['price'];
							$individual_purchase_option['revenue'] = $post_dynamic_pricing_data['revenue'];
						}
						$individual_option          = $this->covert_to_connector_purchase_option( $individual_purchase_option, 'individual', $this->current_post_id );
						$purchase_options[ $order ] = $individual_option;
					}
				}

				// Add time pass options to purchase options.
				$time_pass_ids = $time_pass_instance->get_time_pass_ids( array_keys( $paywall_options_order ) );
				if ( ! empty( $time_pass_ids ) ) {
					foreach ( $time_pass_ids as $time_pass_id ) {
						$order                    = $paywall_options_order[ 'tlp_' . $time_pass_id ];
						$timepass_purchase_option = $time_pass_instance->get_time_pass_by_id( $time_pass_id );

						if ( ! empty( $timepass_purchase_option['is_active'] ) && 1 === absint( $timepass_purchase_option['is_active'] ) ) {
							$timepass_option            = $this->covert_to_connector_purchase_option( $timepass_purchase_option, 'timepass', $time_pass_id );
							$purchase_options[ $order ] = $timepass_option;
						}
					}
				}

				// Add subscription options to purchase options.
				$subscription_ids = $subscription_instance->get_subscription_ids( array_keys( $paywall_options_order ) );
				if ( ! empty( $subscription_ids ) ) {
					foreach ( $subscription_ids as $subscription_id ) {
						$order                        = $paywall_options_order[ 'sub_' . $subscription_id ];
						$subscription_purchase_option = $subscription_instance->get_subscription_by_id( $subscription_id );

						if ( ! empty( $subscription_purchase_option['is_active'] ) && 1 === absint( $subscription_purchase_option['is_active'] ) ) {
							$subscription_option        = $this->covert_to_connector_purchase_option( $subscription_purchase_option, 'subscription', $subscription_id );
							$purchase_options[ $order ] = $subscription_option;
						}
					}
				}
			}
		}

		// Sort purchase options based on order.
		ksort( $purchase_options );

		// This is done to avoid a mismatch in order of paywall items.
		if ( ! empty( $purchase_options ) ) {
			foreach ( $purchase_options as $purchase_option ) {
				$final_purchase_options[] = $purchase_option;
			}
		}

		// Setup overlay configurations.
		if ( ! empty( $final_purchase_options ) ) {
			$payload = wp_json_encode(
				[
					'purchase_options'                 => $final_purchase_options,
					'ignore_database_single_purchases' => true,
					'ignore_database_subscriptions'    => true,
					'ignore_database_timepasses'       => true,
				]
			);

			return [
				'payload' => $payload,
				'token'   => $this->get_signed_token( $payload ),
			];
		}

		return [];
	}

	/**
	 * Create signed token for in page connector configuration.
	 *
	 * @param string $payload Payload data array.
	 *
	 * @return string
	 */
	private function get_signed_token( $payload ) {
		$jwt_header           = wp_json_encode(
			[
				'typ' => 'JWT',
				'alg' => 'HS256',
			]
		);
		$base64_url_header    = $this->base64url_encode( $jwt_header );
		$base64_url_payload   = $this->base64url_encode( $payload );
		$signature            = hash_hmac( 'sha256', $base64_url_header . '.' . $base64_url_payload, $this->merchant_api_key, true );
		$base64_url_signature = $this->base64url_encode( $signature );

		return sprintf( '%1$s.%2$s.%3$s', $base64_url_header, $base64_url_payload, $base64_url_signature );
	}

	/**
	 * From https://base64.guru/developers/php/examples/base64url.
	 *
	 * @param string $data Data to be encoded for connector.
	 *
	 * @return bool|string
	 */
	private function base64url_encode( $data ) {
		// First of all you should encode $data to Base64 string.
		$b64 = base64_encode( $data );
		// Make sure you get a valid result, otherwise, return FALSE, as the base64_encode() function do.
		if ( false === $b64 ) {
			return false;
		}
		// Convert Base64 to Base64URL by replacing “+” with “-” and “/” with “_”.
		$url = strtr( $b64, '+/', '-_' );

		// Remove padding character from the end of line and return the Base64URL result.
		return rtrim( $url, '=' );
	}

	/**
	 * Check if a paywall is active or not.
	 *
	 * @param array $paywall_data Paywall data.
	 *
	 * @return bool
	 */
	private function is_paywall_active( $paywall_data ) {
		return ( ! empty( $paywall_data ) && 1 === absint( $paywall_data['is_active'] ) ) ? true : false;
	}

	/**
	 * Get connected paywall data for the current post.
	 *
	 * Check for paywalls in order of this priority.
	 *
	 * - Individual Post Paywall.
	 * - Paywall based on categories in the post.
	 * - Paywall based on categories not in the post.
	 * - Global Paywall.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return array
	 */
	public function get_connected_paywall_id( $post_id ) {
		$paywall_instance = Paywall::get_instance();
		if ( empty( $this->connected_paywall_id ) ) {
			$paywall_id   = $paywall_instance->get_connected_paywall_by_post( $post_id );
			$paywall_data = $paywall_instance->get_purchase_option_data_by_paywall_id( $paywall_id );

			// Check for Specific Post Paywall.
			if ( ! $this->is_paywall_active( $paywall_data ) ) {
				$paywall_id   = $paywall_instance->get_paywall_for_specific_post( $post_id );
				$paywall_data = $paywall_instance->get_purchase_option_data_by_paywall_id( $paywall_id );
			}

			// Check for Post Type paywall.
			if ( ! $this->is_paywall_active( $paywall_data ) && in_array( get_post_type( $post_id ), Post_Types::get_allowed_post_types() ) ) {
				$paywall_id = $paywall_instance->get_paywall_for_post_type( get_post_type( $post_id ) );
				$paywall_data = $paywall_instance->get_purchase_option_data_by_paywall_id( $paywall_id );
			}

			// If post doesn't have an individual paywall, check for paywall on categories.
			if ( ! $this->is_paywall_active( $paywall_data ) ) {
				// Check if paywall is found on post categories.
				$post_terms = wp_get_post_categories( $post_id );

				// Check the post type and if post has categories.
				if ( ! empty( $post_terms ) && 'post' === get_post_type( $post_id ) ) {
					$paywall_id   = $paywall_instance->get_connected_paywall_by_categories( $post_terms );
					$paywall_data = $paywall_instance->get_purchase_option_data_by_paywall_id( $paywall_id );

					// If paywall is not found on post categories, check for paywalls on excluded categories.
					if ( ! $this->is_paywall_active( $paywall_data ) ) {
						$paywall_id   = $paywall_instance->get_connected_paywall_in_excluded_categories( $post_terms );
						$paywall_data = $paywall_instance->get_purchase_option_data_by_paywall_id( $paywall_id );
					}
				}
			}

			// Get paywall applied for only post_type == post.
			if ( ! $this->is_paywall_active( $paywall_data ) && 'post' === get_post_type( $post_id ) ) {
				$paywall_id   = $paywall_instance->get_paywall_for_only_posts();
				$paywall_data = $paywall_instance->get_purchase_option_data_by_paywall_id( $paywall_id );
			}

			// If no paywall found in categories and individual post, check for paywall applied on all posts as last resort.
			if ( ! $this->is_paywall_active( $paywall_data ) && 'post' === get_post_type( $post_id ) ) {
				$paywall_id = $paywall_instance->get_paywall_for_all_posts();
			}

			// Setup purchase options.
			$paywall_data = $paywall_instance->get_purchase_option_data_by_paywall_id( $paywall_id );
			if ( $this->is_paywall_active( $paywall_data ) ) {
				$this->connected_paywall_id = $paywall_data['id'];

				return $paywall_data;
			} else {
				return [];
			}
		} else {
			return $paywall_instance->get_purchase_option_data_by_paywall_id( $this->connected_paywall_id );
		}
	}

	/**
	 * Enqueue styles and scripts needed for preview pane.
	 *
	 * @hooked action `wp_enqueue_scripts`
	 *
	 * @return void
	 */
	public function enqueue_preview_scripts_and_styles() {
		if ( Contribution_Preview::SLUG !== get_post_type() ) {
			return;
		}

		wp_enqueue_style(
			'revenue-generator-preview',
			REVENUE_GENERATOR_BUILD_URL . '/css/revenue-generator-preview.css',
			[],
			REVENUE_GENERATOR_VERSION
		);

		wp_register_script(
			'revenue-generator-shepherd',
			REVENUE_GENERATOR_BUILD_URL . 'vendor/shepherd/shepherd.min.js',
			[],
			'8.0.1',
			true
		);

		wp_enqueue_script(
			'revenue-generator-preview-script',
			REVENUE_GENERATOR_BUILD_URL . '/revenue-generator-preview.js',
			array(
				'backbone',
				'revenue-generator-shepherd',
			),
			REVENUE_GENERATOR_VERSION
		);
	}

	/**
	 * Handle custom contribution form submit.
	 *
	 * @hooked action `wp_ajax_rg_contribution_contribute`
	 * @hooked action `wp_ajax_nopriv_rg_contribution_contribute`
	 *
	 * @return void
	 */
	public function ajax_contribute_contribution() {
		check_ajax_referer( 'rg_contribution_contribute', 'nonce' );

		$amount      = ( isset( $_REQUEST['amount'] ) ) ? (float) $_REQUEST['amount'] : 0;
		$campaign_id = ( isset( $_REQUEST['campaign_id'] ) ) ? sanitize_text_field( $_REQUEST['campaign_id'] ) : '';
		$title       = ( isset( $_REQUEST['title'] ) ) ? sanitize_text_field( $_REQUEST['title'] ) : '';
		$url         = ( isset( $_REQUEST['url'] ) ) ? esc_url_raw( $_REQUEST['url'] ) : '';
		$is_amp      = ( isset( $_REQUEST['is_amp'] ) && 1 === (int) $_REQUEST['is_amp'] );

		// If amount is empty, there's nothing to contribute so return early.
		if ( empty( $amount ) ) {
			wp_send_json_error(
				__( 'Contribution amount cannot be empty.', 'revenue-generator' )
			);
		}

		// We need campaign details in order to create URL, so no fun if not supplied.
		if ( empty( $campaign_id ) || empty( $title ) || empty( $url ) ) {
			wp_send_json_error(
				__( 'Campaign details cannot be empty.', 'revenue-generator' )
			);
		}

		$client_account   = Client_Account::get_instance();
		$amount_in_cents  = $amount * 100;
		$contribution_url = $client_account->get_custom_contribution_url( $amount_in_cents, $campaign_id, $title, $url );

		if ( is_wp_error( $contribution_url ) ) {
			wp_send_json_error();
		}

		// If it's a submit from AMP, pass headers so AMP handles redirect.
		if ( $is_amp ) {
			header( 'AMP-Redirect-To: ' . $contribution_url );
			header( 'Access-Control-Expose-Headers: AMP-Redirect-To' );
		}

		wp_send_json_success(
			$contribution_url
		);
	}
}

<?php
/**
 * Revenue Generator Plugin Client Account Class.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc;

use \LaterPay\Revenue_Generator\Inc\Traits\Singleton;
use \LaterPay\Revenue_Generator\Inc\Revenue_Generator_Client;

defined( 'ABSPATH' ) || exit;

/**
 * Class Client_Account
 */
class Client_Account {

	use Singleton;

	/**
	 * API key.
	 *
	 * @var string
	 */
	protected $merchant_api_key;

	/**
	 * Connector endpoint root.
	 *
	 * @var string
	 */
	protected $connector_root;

	/**
	 * API endpoint root.
	 *
	 * @var string
	 */
	protected $api_root;

	/**
	 * Merchant ID.
	 *
	 * @var string
	 */
	protected $merchant_id;

	/**
	 * Merchant region.
	 *
	 * @var string
	 */
	protected $merchant_region;

	/**
	 * Web endpoint.
	 *
	 * @var string
	 */
	protected $web_endpoint;

	/**
	 * Algorithm used to sign the key.
	 *
	 * @var string
	 */
	protected $secret_algo = 'sha224';

	/**
	 * Boolean whether credentials are valid.
	 *
	 * @var bool
	 */
	protected $credentials_valid = false;

	/**
	 * Boolean whether credentials were validated.
	 *
	 * @var bool
	 */
	protected $checked_credentials = false;

	/**
	 * Client instance.
	 *
	 * @var Revenue_Generator_Client
	 */
	protected $client = null;

	/**
	 * Store connector endpoint used in the plugin.
	 *
	 * @var array Common values used for api related info throughout the plugin.
	 */
	public static $connector_endpoints = [
		'EU' => [
			'sandbox' => 'https://connector.sandbox.laterpaytest.net',
			'live'    => 'https://connector.laterpay.net',
		],
		'US' => [
			'sandbox' => 'https://connector.sandbox.uselaterpaytest.com',
			'live'    => 'https://connector.uselaterpay.com',
		],
	];

	/**
	 * Store api endpoints used in the plugin.
	 *
	 * @var array Common values used for api related info throughout the plugin.
	 */
	public static $api_endpoints = [
		'EU' => [
			'sandbox' => 'https://api.sandbox.laterpaytest.net',
			'live'    => 'https://api.laterpay.net/validatesignature',
		],
		'US' => [
			'sandbox' => 'https://api.sandbox.uselaterpaytest.com',
			'live'    => 'https://api.uselaterpay.com',
		],
	];

	/**
	 * Web API endpoints.
	 *
	 * @var array Common values used for web related info throughout the plugin.
	 */
	public static $web_endpoints = [
		'EU' => [
			'sandbox' => 'https://web.sandbox.laterpaytest.net',
			'live'    => 'https://web.laterpay.net',
		],
		'US' => [
			'sandbox' => 'https://web.sandbox.uselaterpaytest.com',
			'live'    => 'https://web.uselaterpay.com',
		],
	];

	/**
	 * Currency info.
	 *
	 * @var array Currencies categorized by the region.
	 */
	public static $currency_details = [
		'EU' => [
			'code'   => 'EUR',
			'symbol' => '€',
		],
		'US' => [
			'code'   => 'USD',
			'symbol' => '$',
		],
	];

	/**
	 * Class Client_Account construct method.
	 */
	protected function __construct() {
		$this->setup_options();
	}

	/**
	 * Setup account default options.
	 */
	protected function setup_options() {
		// Fresh install.
		if ( empty( self::get_merchant_credentials() ) ) {
			// Set default data for merchant credentials.
			update_option(
				'lp_rg_merchant_credentials',
				[
					'merchant_id'  => '',
					'merchant_key' => '',
				]
			);
		}
	}

	/**
	 * Returns merchant credentials.
	 *
	 * @return array
	 */
	public static function get_merchant_credentials() {
		return get_option( 'lp_rg_merchant_credentials', [] );
	}

	/**
	 * Validate provided merchant credentials and make test sure domain is valid.
	 *
	 * @param bool $force_validation Whether to force running through the whole method instead of returning previously
	 *                               gathered result.
	 *
	 * @return bool
	 */
	public function validate_merchant_account( $force_validation = false ) {
		if ( ! $force_validation && $this->checked_credentials ) {
			return $this->credentials_valid;
		}

		$global_options = Config::get_global_options();
		$region         = $global_options['merchant_region'];

		// Setup web roots for API Call.
		$this->merchant_region = $region;

		// Bail early, if no region set.
		if ( empty( $region ) ) {
			return false;
		}

		$region_connector_endpoints = self::$connector_endpoints[ $region ];
		$region_api_endpoints       = self::$api_endpoints[ $region ];
		$this->connector_root       = $region_connector_endpoints['live'];
		$this->api_root             = $region_api_endpoints['live'];
		$this->web_endpoint         = self::$web_endpoints[ $this->merchant_region ]['live'];

		// If development mode is enabled use snbox environment.
		if ( defined( 'REVENUE_GENERATOR_ENABLE_SANDBOX' ) && true === REVENUE_GENERATOR_ENABLE_SANDBOX ) {
			$this->connector_root = $region_connector_endpoints['sandbox'];
			$this->api_root       = $region_api_endpoints['sandbox'];
			$this->web_endpoint   = self::$web_endpoints[ $this->merchant_region ]['sandbox'];
		}

		// Setup merchant credentials.
		$merchant_credentials = self::get_merchant_credentials();

		if ( empty( $merchant_credentials ) ) {
			return false;
		}

		if ( ! empty( $merchant_credentials['merchant_id'] ) ) {
			$this->merchant_id = $merchant_credentials['merchant_id'];
		}

		if ( ! empty( $merchant_credentials['merchant_key'] ) ) {
			$this->merchant_api_key = $merchant_credentials['merchant_key'];
		}

		$this->checked_credentials = true;

		// Check if account credentials are valid.
		$are_credentials_valid = $this->test_merchant_credentials();

		// If account credentials are valid, proceed to checking the merchant domain.
		if ( true === $are_credentials_valid ) {
			$this->credentials_valid = true;

			return $this->test_merchant_domain();
		}

		return false;
	}

	/**
	 * Handle account verification.
	 *
	 * @return bool
	 */
	private function test_merchant_domain() {
		// Request to fetch endpoint, and add home url as origin.
		$fetch_url = $this->build_test_fetch_url();
		$home_url  = get_home_url();
		$response  = wp_remote_get(
			$fetch_url,
			[
				// phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- Request done only once during initial setup.
				'timeout' => 60,
				'headers' => [
					'Origin' => $home_url,
				],
			]
		);

		// Check and verify allowed origin matches merchant domain.
		$allowed_origin_header = wp_remote_retrieve_header( $response, 'access-control-allow-origin' );

		if ( ! empty( $allowed_origin_header ) && $allowed_origin_header === $home_url ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Create url for test whitelisting of account.
	 *
	 * @return string
	 */
	private function build_test_fetch_url() {
		// Demo params to verify merchant domain.
		$fetch_url_params = build_query(
			[
				'article_title' => rawurlencode( 'Revenue Generator Demo Page' ),
				'article_url'   => rawurlencode( get_home_url() ),
			]
		);

		return $this->get_api_fetch_url() . '?' . $fetch_url_params;
	}

	/**
	 * Handle account credentials validation.
	 *
	 * @return bool
	 */
	private function test_merchant_credentials() {
		$validate_signature_url = $this->build_validate_signature_url();
		$response               = wp_remote_get(
			$validate_signature_url,
			[
				'headers' => [
					'User-Agent' => sprintf(
						'\[Revenue Generator: v%s] \[Merchant ID: %s]',
						REVENUE_GENERATOR_VERSION,
						$this->merchant_id
					),
				],
			]
		);
		$response_body          = wp_remote_retrieve_body( $response );
		$response_status        = wp_remote_retrieve_response_code( $response );

		// Check if the request was successful and account is valid.
		if ( ! empty( $response_status ) && 200 === $response_status ) {
			// Check if current config is valid or not.
			$validation_data = json_decode( $response_body, true );
			if ( ! empty( $validation_data['is_valid'] ) && true === $validation_data['is_valid'] ) {
				return true;
			} else {
				return false;
			}
		}

		return false;
	}

	/**
	 * Create validate signature url for testing account credentials.
	 *
	 * @return string
	 */
	private function build_validate_signature_url() {
		$this->validate_merchant_account();

		$validation_url_params = build_query(
			[
				'cp'   => $this->merchant_id,
				'salt' => md5( microtime( true ) ),
				'ts'   => time(),
			]
		);
		$encoded_params        = urlencode( $validation_url_params );
		$message               = 'GET&' . urlencode( $this->get_validate_signature_url() ) . '&' . $encoded_params;
		$hmac                  = hash_hmac( $this->secret_algo, $message, $this->merchant_api_key );

		return $this->get_validate_signature_url() . '?' . $validation_url_params . '&hmac=' . $hmac;
	}

	/**
	 * Get API fetch URL to verify whitelisted merchant domain.
	 *
	 * @return string
	 */
	private function get_api_fetch_url() {
		return $this->connector_root . '/api/v2/fetch';
	}

	/**
	 * Get dialog add URL.
	 *
	 * @return string
	 */
	private function get_validate_signature_url() {
		return $this->api_root . '/validatesignature';
	}
	/**
	 * Get connector, API, and Web endpoints from merchant credentials.
	 *
	 * @return array
	 */
	public function get_endpoints() {
		$this->validate_merchant_account();

		return [
			'connector' => $this->connector_root,
			'api'       => $this->api_root,
			'web'       => $this->web_endpoint,
		];
	}

	/**
	 * Get currency from region.
	 *
	 * @return array Currency code and symbol.
	 */
	public function get_currency() {
		return self::$currency_details[ $this->merchant_region ];
	}

	/**
	 * Returns instance of `Revenue_Generator_Client` class.
	 *
	 * @return Revenue_Generator_Client
	 */
	public function get_client_instance() {
		if ( ! is_null( $this->client ) ) {
			return $this->client;
		}

		$this->validate_merchant_account();

		$this->client = new Revenue_Generator_Client(
			$this->merchant_id,
			$this->merchant_api_key,
			$this->api_root,
			$this->web_endpoint
		);

		return $this->client;
	}

	/**
	 * Get contribution URL based on parameters.
	 *
	 * @param int    $amount_in_cents Amount in cents.
	 * @param string $campaign_id     Campaign ID.
	 * @param string $title           Campaign title.
	 * @param string $referral_url    URL to redirect to after contribution.
	 *
	 * @return string URL to redirect to for completing contribution.
	 */
	public function get_custom_contribution_url( $amount_in_cents, $campaign_id, $title, $referral_url ) {
		if ( 0 >= (int) $amount_in_cents ) {
			return new \WP_Error( 'invalid_amount', __( 'Amount cannot be zero.', 'revenue-generator' ) );
		}

		if ( empty( $campaign_id ) || empty( $title ) ) {
			return new \WP_Error(
				'invalid_campaign_details',
				__( 'Campaign ID and Title are required params.', 'revenue-generator' )
			);
		}

		if ( empty( $referral_url ) ) {
			return new \WP_Error(
				'invalid_referral_url',
				__( 'Referral URL cannot be empty.', 'revenue-generator' )
			);
		}

		$client       = $this->get_client_instance();
		$currency     = $this->get_currency();
		$revenue_type = $this->get_revenue_type( $amount_in_cents );

		// Params as required by `get_single_contribution_url`.
		$params = [
			'campaign_id' => $campaign_id,
			'title'       => $title,
			'url'         => $referral_url,
			'revenue'     => $revenue_type,
		];

		$url = $client->get_single_contribution_url( $params );

		// Append query arg with custom price to the URL.
		$url = add_query_arg(
			'custom_pricing',
			$currency['code'] . $amount_in_cents,
			$url
		);

		return $url;
	}

	/**
	 * Get revenue type by amount.
	 *
	 * @param int $amount_in_cents Amount in cents.
	 *
	 * @return string Revenue type.
	 */
	public function get_revenue_type( $amount_in_cents ) {
		$amount_in_cents = (int) $amount_in_cents;

		// Default revenue type for amounts less than 5.00.
		$revenue_type = 'ppu';

		if ( 500 <= $amount_in_cents ) {
			$revenue_type = 'sis';
		}

		return $revenue_type;
	}
}

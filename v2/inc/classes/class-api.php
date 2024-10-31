<?php
/**
 * Laterpay API class.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc;

use \LaterPay\Revenue_Generator\Inc\Traits\Singleton;
use \LaterPay\Revenue_Generator\Inc\Encryption;
use \Firebase\JWT\JWT;

defined( 'ABSPATH' ) || exit;

/**
 * Laterpay API wrapper.
 */
class API {

	use Singleton;

	/**
	 * Base URL for API.
	 *
	 * @const string
	 */
	const TAPPER_API_URL = 'https://tapi.laterpay.net';

	/**
	 * Base URL for sandbox API.
	 *
	 * @const string
	 */
	const TAPPER_SBX_API_URL = 'https://tapi.sbx.laterpay.net';

	/**
	 * JWT key for prod.
	 *
	 * @const string
	 */
	const JWT_PROD_KEY = 'ts323vHeTP160ZB1OyOzPWRWIGKx78tg';

	/**
	 * JWT key for SBX.
	 *
	 * @const string
	 */
	const JWT_SBX_KEY = '0tuKkb0jCKSnLmyfb4z5zS6ji9ba6Rfr';

	/**
	 * Auth instance.
	 *
	 * @var Api\Auth
	 */
	public static $auth = null;

	/**
	 * Session key to manage meta.
	 *
	 * @var string
	 */
	public $session_key = '';

	/**
	 * Hashed session key.
	 *
	 * @var string
	 */
	private $hashed_key = '';

	/**
	 * Constructs the API wrapper.
	 */
	public function __construct() {
		// Get auth instance and immediately authenticate.
		self::$auth = Api\Auth::get_instance();
	}

	/**
	 * Get default headers for API calls.
	 *
	 * @return array
	 */
	public static function get_default_headers() {
		return [
			'User-Agent'   => sprintf(
				'\[Revenue Generator: v%s] \[Client ID: %s]',
				REVENUE_GENERATOR_VERSION,
				self::$auth->get_client_id()
			),
			'Content-Type' => 'application/json',
		];
	}

	/**
	 * Get API URL based on requested environment.
	 *
	 * @return string
	 */
	public function get_api_url() {
		$url = static::TAPPER_API_URL;

		if ( defined( 'REVENUE_GENERATOR_SANDBOX_MODE' ) && REVENUE_GENERATOR_SANDBOX_MODE ) {
			$url = static::TAPPER_SBX_API_URL;
		}

		return $url;
	}

	/**
	 * Get Authorization part of the header.
	 *
	 * Get token from Auth class, then create an array with Bearer in
	 * Authorization header.
	 *
	 * @return array
	 */
	private function get_auth_header() {
		$token = self::$auth->get_access_token();

		return [
			'Authorization' => sprintf(
				'Bearer %s',
				$token
			),
		];
	}

	/**
	 * Construct the request and handle the raw response.
	 *
	 * @param string $endpoint API endpoint with leading slash such as `/v1/purchase`.
	 * @param string $method   HTTP request method.
	 * @param array  $body     Request body.
	 *
	 * @return array Response array on success, empty array on failure.
	 */
	private function make_request( $endpoint, $method = 'POST', $body = [] ) {
		$base_url   = $this->get_api_url();
		$headers    = array_merge(
			self::get_default_headers(),
			$this->get_auth_header()
		);

		// Request properties.
		$req_props = [
			'url'    => "{$base_url}{$endpoint}",
			'args'   => [
				'method'  => $method,
				'headers' => $headers,
			],
		];

		// Default response.
		$res = [];

		if ( ! empty( $body ) ) {
			$req_props['args']['body'] = json_encode( $body );
		}

		$req = wp_remote_request(
			$req_props['url'],
			$req_props['args']
		);

		if ( ! is_wp_error( $req ) ) {
			$res = [
				'code' => wp_remote_retrieve_response_code( $req ),
				'body' => json_decode( wp_remote_retrieve_body( $req ) ),
			];
		}

		return $res;
	}

	/**
	 * Add item to user's tab.
	 *
	 * Docs: https://tapi.laterpay.net/docs#/Tabs/purchase_item_v1_purchase_post
	 *
	 * @param int    $item_id     The ID of the item to be purchased. Usually contribution ID or article ID.
	 * @param int    $price       Amount in cents.
	 * @param string $summary     Summary to label the purchase.
	 * @param string $sales_model Sales model as expected by Laterpay API.
	 *
	 * @return array|WP_error Response array on success, WP_Error on
	 * incorrect params passed.
	 */
	public function add_to_tab( $item_id = 0, $price = 0, $summary = '', $sales_model = 'contribution' ) {
		// Return early if the required $item_id is not specified.
		if ( empty( $item_id ) ) {
			return new \WP_Error(
				'rgv2_purchase_field_required',
				'Revenue Generator: item_id cannot be empty.'
			);
		}

		// Return early if the required price is empty.
		if ( empty( $price ) ) {
			return new \WP_Error(
				'rgv2_purchase_field_required',
				'Revenue Generator: price cannot be empty and has to be greater than 0.'
			);
		}

		// Return early if the required summary is empty.
		if ( empty( $summary ) ) {
			return new \WP_Error(
				'rgv2_summary_field_required',
				'Revenue Generator: summary cannot be empty.'
			);
		}

		$payment_model = 'pay_now';

		if ( 199 > $price ) {
			$payment_model = 'pay_later';
		}

		$req_body = [
			'offering_id' => (string) $item_id,
			'price'       => [
				'amount'   => $price,
				'currency' => 'USD',
			],
			'payment_model' => $payment_model,
			'summary'       => $summary,
			'sales_model'   => $sales_model,
		];

		$res = $this->make_request( '/v1/purchase', 'POST', $req_body );

		return $res;
	}

	/**
	 * Get user's tab.
	 *
	 * Docs: https://tapi.laterpay.net/docs#/Tabs/tabs_list_user_v1_tabs_get
	 *
	 * @return array Response array.
	 */
	public function get_user_tab() {
		$res = $this->make_request( '/v1/tabs', 'GET' );

		return $res;
	}

	/**
	 * Checks if user is authorized / has access token.
	 *
	 * @return boolean
	 */
	public function is_user_authorized() {
		if ( false === self::$auth->get_access_token() ) {
			return false;
		}

		return true;
	}

	/**
	 * Set session key.
	 *
	 * @param string $session_key Session key to set.
	 */
	public function set_session( $session_key = '' ) {
		$this->session_key = $session_key;
		$this->hashed_key  = wp_hash( $this->session_key );
	}

	/**
	 * Set client meta in database.
	 *
	 * @param string $data       Data to be stored.
	 * @param string $type       Type of meta. Default: `meta`.
	 * @param int    $expiration The expiration after which data will be deleted.
	 *
	 * @return void
	 */
	public function set_client_meta( $data = '', $type = 'meta', $expiration = 5 * MINUTE_IN_SECONDS ) {
		$transient_key = "{$this->hashed_key}_{$type}";

		set_transient( $transient_key, $data, $expiration );
	}

	/**
	 * Get client meta from the database.
	 *
	 * @param string $type Type of meta. Default: `meta`.
	 *
	 * @return mixed
	 */
	public function get_client_meta( $type = 'meta' ) {
		$transient_key = "{$this->hashed_key}_{$type}";

		return get_transient( $transient_key );
	}

	/**
	 * Delete client meta from the database.
	 *
	 * @param string $type Type of meta to be deleted. Default: `meta`.
	 *
	 * @return void
	 */
	public function delete_client_meta( $type = 'meta' ) {
		$transient_key = "{$this->hashed_key}_{$type}";

		delete_transient( $transient_key );
	}

	/**
	 * Encrypt payment payload to pass to payment component in
	 * AMP context.
	 *
	 * @return string
	 */
	public function encrypt_amp_payment_payload() {
		$payment_meta = $this->get_client_meta( 'payment' );

		$payload = [
			'token' => self::$auth->get_access_token(),
			'tabId' => $payment_meta['tab_id'],
		];

		$jwt_key = static::JWT_PROD_KEY;

		if ( defined( 'REVENUE_GENERATOR_SANDBOX_MODE' ) && REVENUE_GENERATOR_SANDBOX_MODE ) {
			$jwt_key = static::JWT_SBX_KEY;
		}

		$jwt       = JWT::encode( $payload, $jwt_key );
		$encrypted = Encryption::encrypt( $jwt );

		return $encrypted;
	}

}

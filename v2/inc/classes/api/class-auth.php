<?php
/**
 * Revenue Generator Laterpay API auth class for credentials and token
 * management.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc\Api;

use \LaterPay\Revenue_Generator\Inc\API;
use \LaterPay\Revenue_Generator\Inc\Traits\Singleton;
use \League\OAuth2\Client\OptionProvider\HttpBasicAuthOptionProvider;
use \League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use \League\OAuth2\Client\Token\AccessToken;
use \Firebase\JWT\JWT;
use \LaterPay\Revenue_Generator\Inc\Encryption;

defined( 'ABSPATH' ) || exit;

/**
 * Auth class for credentials and token management.
 */
class Auth {

	use Singleton;

	/**
	 * Name of the option where credentials are stored.
	 *
	 * @const string
	 */
	const CREDENTIALS_OPTION_NAME = 'lp_rgv2_credentials';

	/**
	 * Production Auth base URL.
	 *
	 * @const string
	 */
	const BASE_TAPPER_AUTH_URL = 'https://auth.laterpay.net';

	/**
	 * Sandbox Auth base URL.
	 *
	 * @const string
	 */
	const BASE_TAPPER_AUTH_URL_SBX = 'https://auth.sbx.laterpay.net';

	/**
	 * Base path for auth REST API routes.
	 *
	 * @const string
	 */
	const REST_BASE_PATH = 'auth';

	/**
	 * User capability required to operate REST API endpoints.
	 *
	 * @const string
	 */
	const REST_API_CAP = 'manage_options';

	/**
	 * Var to store credentials.
	 *
	 * @var array
	 */
	private $credentials = [];

	/**
	 * OAuth 2.0 Client provider instance. Set to `null` on init.
	 *
	 * @var Auth_Provider
	 */
	private $provider = null;

	/**
	 * Access token.
	 *
	 * @var string
	 */
	private $token = '';

	/**
	 * Token transient prefix.
	 *
	 * @var string
	 */
	private $transient_prefix = 'rgt_';

	/**
	 * Constructs the instance. Initialize provider, get credentials
	 * and server key.
	 *
	 * Call `hook` method to start hooking to WordPress.
	 */
	protected function __construct() {
		$this->credentials = $this->get_credentials();

		if ( ! empty( $this->credentials ) ) {
			$this->provider = $this->get_provider();

			$this->hook();
		}
	}

	/**
	 * Get URL for the authorization endpoint.
	 *
	 * @return string
	 */
	private function get_auth_url() {
		$url = self::BASE_TAPPER_AUTH_URL;

		if ( defined( 'REVENUE_GENERATOR_SANDBOX_MODE' ) && REVENUE_GENERATOR_SANDBOX_MODE ) {
			$url = self::BASE_TAPPER_AUTH_URL_SBX;
		}

		return $url;
	}

	/**
	 * Get provider instance.
	 *
	 * @return Auth_Provider
	 */
	public function get_provider() {
		if ( ! is_null( $this->provider ) ) {
			return $this->provider;
		}

		return new Auth_Provider(
			[
				'clientId'     => $this->credentials['client_id'],
				'clientSecret' => $this->credentials['client_secret'],
				'redirectUri'  => site_url(),
				'authUri'      => $this->get_auth_url(),
			],
			[
				'optionProvider' => new HttpBasicAuthOptionProvider(),
			]
		);
	}

	/**
	 * Hook to WordPress.
	 */
	private function hook() {
		add_action( 'init', [ $this, 'handle_authorization_code_flow' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
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
			self::REST_BASE_PATH,
			[
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'rest_save_credentials' ],
					'permission_callback' => [ $this, 'rest_check_permission' ],
				],
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'rest_get_client_data' ],
					'permission_callback' => [ $this, 'rest_check_permission' ],
				],
			]
		);
	}

	/**
	 * Get token from database if key is set.
	 *
	 * @return JWT
	 */
	public function get_token_from_db() {
		$api = API::get_instance();

		// Return false if session key is not set.
		if ( empty( $api->session_key ) ) {
			return false;
		}

		// Get token by session key.
		$tokens = get_transient( $this->transient_prefix . wp_hash( $api->session_key ) );
		$tokens = $this->decrypt_tokens( $tokens, $api->session_key );

		if ( is_wp_error( $tokens ) ) {
			return false;
		}

		return $tokens;
	}

	/**
	 * Get access token.
	 *
	 * This looks for a token in database first, returns false if it's not found so we can then
	 * start the authorization code flow.
	 *
	 * If the token is close to expiration time, it requests a new token from the provider
	 * using a refresh_token grant type.
	 */
	public function get_access_token() {
		// Return early if token is already in memory.
		if ( ! empty( $this->token ) ) {
			return $this->token;
		}

		// Get tokens from the database.
		$tokens = $this->get_token_from_db();

		if ( ! $tokens ) {
			return false;
		}

		// If token is still within expiration time, use it.
		if ( time() <= (int) $tokens['expires'] ) {
			$this->token = $tokens['access'];
		} else {
			// If token is past expiration time, request new token using refresh_token grant type.
			$this->token = $this->get_tokens_from_provider( 'refresh_token', $tokens['refresh'] );
		}

		return $this->token;
	}

	/**
	 * Get token from authorization code.
	 *
	 * @hooked action `admin_init`
	 *
	 * @return void
	 */
	public function handle_authorization_code_flow() {
		if ( ! isset( $_GET['code'] ) ) {
			return;
		}

		if ( ! isset( $_GET['state'] ) ) {
			return;
		}

		$state = sanitize_text_field( $_GET['state'] );

		$api = API::get_instance();
		$api->set_session( $state );

		$tokens = $this->get_tokens_from_provider(
			'authorization_code',
			sanitize_text_field( $_GET['code'] ),
			$state
		);

		$client_data = $api->get_client_meta();

		// If this is AMP, pair token to AMP client ID instead.
		if ( isset( $client_data['amp_id_key'] ) ) {
			delete_transient( wp_hash( $state ) );

			$this->save_tokens( $tokens, $client_data['amp_id_key'] );
		}

		$redirect_uri = site_url();

		if ( isset( $client_data['redirect_uri'] ) && ! empty( $client_data['redirect_uri'] ) ) {
			$redirect_uri = $client_data['redirect_uri'];
		}

		$api->delete_client_meta();

		wp_safe_redirect( $redirect_uri );
		exit();
	}

	/**
	 * Gets tokens from provider.
	 *
	 * @param string $grant_type Grant type to use when obtaining a token.
	 * @param string $key        A key to use with the flow (auth code or refresh token).
	 * @param string $state      OAuth state.
	 *
	 * @return AccessToken|boolean Token class instance. Boolean on failure.
	 */
	public function get_tokens_from_provider( $grant_type = 'refresh_token', $key = '', $state = '' ) {
		if ( empty( $key ) ) {
			return false;
		}

		$params = [];

		switch ( $grant_type ) {
			case 'refresh_token':
				$params['refresh_token'] = $key;
				break;

			case 'authorization_code':
				$params['code'] = $key;
				break;

			default:
				break;
		}

		if ( empty( $params ) ) {
			return false;
		}

		try {
			$tokens = $this->provider->getAccessToken( $grant_type, $params );

			$this->save_tokens( $tokens, $state );

			return $tokens;
		} catch ( IdentityProviderException $e ) {
			$this->handle_identity_provider_exception( $e );

			return false;
		}
	}

	/**
	 * Save tokens to client.
	 *
	 * @param AccessToken $tokens AccessToken instance.
	 * @param string      $state  OAuth state.
	 *
	 * @return string Access token.
	 */
	public function save_tokens( AccessToken $tokens, $state ) {
		$this->token      = $tokens->getToken();
		$encrypted_tokens = $this->encrypt_tokens( $tokens, $state );

		delete_transient( wp_hash( $state ) );
		set_transient( $this->transient_prefix . wp_hash( $state ), $encrypted_tokens, 1 * MONTH_IN_SECONDS );

		return $this->token;
	}

	/**
	 * Encode tokens with JWT before passing them to client.
	 *
	 * @param AccessToken $tokens AccessToken instance.
	 * @param string      $key    Key to encode tokens with.
	 *
	 * @return mixed JWT token on success, FALSE on failure.
	 */
	private function encrypt_tokens( AccessToken $tokens, $key = '' ) {
		if ( empty( $key ) ) {
			return false;
		}

		$payload = [
			'access'  => $tokens->getToken(),
			'refresh' => $tokens->getRefreshToken(),
			'expires' => $tokens->getExpires(),
		];

		$message   = serialize( $payload );
		$encrypted = Encryption::encrypt( $message, '', $key );

		return $encrypted;
	}

	/**
	 * Decode tokens using server key.
	 *
	 * @param string $message String to decrypt.
	 * @param string $key     Secret key.
	 *
	 * @return JSON|WP_Error JSON object on success. WP_Error object on failure.
	 */
	private function decrypt_tokens( $message, $key = '' ) {
		if ( empty( $message ) ) {
			return new \WP_Error( 'empty_message', 'Message parameter cannot be empty to decrypt it.' );
		}

		if ( empty( $key ) ) {
			return new \WP_Error( 'empty_key', 'Key needs to be provided to decrypt a token.' );
		}

		$decrypted = Encryption::decrypt( $message, $key );
		$decrypted = maybe_unserialize( $decrypted );

		return $decrypted;
	}

	/**
	 * Gets default credentials array.
	 *
	 * @return array
	 */
	private function get_default_credentials() {
		return [
			'client_id'     => '',
			'client_secret' => '',
		];
	}

	/**
	 * Gets credentials from the database.
	 *
	 * @return array
	 */
	public function get_credentials() {
		return get_option( self::CREDENTIALS_OPTION_NAME, $this->get_default_credentials() );
	}

	/**
	 * Get client ID from credentials.
	 *
	 * @return string
	 */
	public function get_client_id() {
		$credentials = $this->get_credentials();
		$client_id   = ( isset( $credentials['client_id'] ) ) ? $credentials['client_id'] : '';

		return $client_id;
	}

	/**
	 * Saves credentials to the database.
	 *
	 * @param array $credentials New credentials.
	 *
	 * @return boolean
	 */
	public function save_credentials( array $credentials ) {
		if ( empty( $credentials ) ) {
			return false;
		}

		$credentials = wp_parse_args(
			$credentials,
			$this->get_default_credentials()
		);

		$validate_credentials = $this->validate_credentials( $credentials );

		if ( ! $validate_credentials ) {
			return false;
		}

		update_option( self::CREDENTIALS_OPTION_NAME, $credentials );

		return true;
	}

	/**
	 * Validate credentials against Token endpoint.
	 *
	 * @param array $credentials Submitted credentials.
	 *
	 * @return boolean
	 */
	public function validate_credentials( array $credentials = [] ) {
		if ( empty( $credentials ) ) {
			return false;
		}

		$credentials = wp_parse_args(
			$credentials,
			$this->get_default_credentials()
		);

		$encoded_credentials = base64_encode(
			sprintf(
				'%s:%s',
				$credentials['client_id'],
				$credentials['client_secret']
			)
		);

		$test_endpoint = $this->get_auth_url() . '/oauth2/token';

		$headers = [
			'Content-Type'  => 'application/x-www-form-urlencoded',
			'Authorization' => 'Basic ' . $encoded_credentials,
		];

		$headers = wp_parse_args( $headers, API::get_default_headers() );

		$response = wp_remote_post(
			$test_endpoint,
			[
				'headers' => $headers,
				'body' => [
					'grant_type' => 'authorization_code',
				],
			]
		);

		$response_code = wp_remote_retrieve_response_code( $response );

		// 401 is the code returned in case of invalid credentials.
		if ( 401 === $response_code ) {
			return false;
		}

		return true;
	}

	/**
	 * Handles identity provider exception which is returned on failure
	 * of try catch block in `_get_tokens`.
	 *
	 * @param IdentityProviderException $e Exception instance.
	 *
	 * @return void
	 */
	private function handle_identity_provider_exception( IdentityProviderException $e ) {
		// Get exception's body.
		$body  = $e->getResponseBody();

		// Log the exception.
		$this->log( $body['status_code'], $body['error'], $body['error_verbose'] );
	}

	/**
	 * Prints logged events to the screen or writes them to WP debug log
	 * if it's allowed in WP config.
	 *
	 * @param string $code Code of the message.
	 * @param string $id   Short identifier of the message.
	 * @param string $text Message to log.
	 * @param string $type Type of the message.
	 *
	 * @return void
	 */
	private function log( $code, $id, $text, $type = 'error' ) {
		// Return early if WP_DEBUG constant is set to `false`.
		if ( ! WP_DEBUG ) {
			return;
		}

		$message = sprintf(
			'Laterpay API (%d) [%s:%s] - %s',
			$code,
			$type,
			$id,
			$text
		);

		if ( WP_DEBUG_LOG ) {
			error_log( $message ); /* phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log */
		}

		if ( WP_DEBUG_DISPLAY ) {
			echo esc_html( $message ) . "\n";
		}
	}

	/**
	 * Check if merchant credentials are added.
	 *
	 * @return boolean
	 */
	public function is_merchant_connected() {
		$credentials = $this->get_credentials();

		if ( empty( $credentials['client_id'] ) || empty( $credentials['client_secret'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get authorization code flow URL.
	 *
	 * @return string
	 */
	public function get_auth_code_url() {
		$url = $this->provider->getAuthorizationUrl();

		return $url;
	}

	/**
	 * Get state from the authorization code flow URL.
	 *
	 * @param string $url URL to get the state from.
	 *
	 * @return string
	 */
	public function get_state_from_auth_url( $url = '' ) {
		if ( empty( $url ) ) {
			return '';
		}

		$parsed_url = parse_url( $url );
		parse_str( $parsed_url['query'], $query_params );

		if ( ! isset( $query_params['state'] ) ) {
			return '';
		}

		return sanitize_text_field( $query_params['state'] );
	}

	/**
	 * Handle REST API endpoint to save credentials.
	 *
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response|\WP_Error Response object on success, WP_Error on failure.
	 */
	public function rest_save_credentials( $request ) {
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

		$required_body_params = [
			'client_id',
			'client_secret',
		];

		$required_body_params_diff = array_diff( array_values( $required_body_params ), array_keys( $body ) );

		if ( ! empty( $required_body_params_diff ) ) {
			return new \WP_Error(
				'rg_rest_missing_body_params',
				sprintf(
					/* translators: %s is a comma separated list of missing parameters */
					__( 'The following body params are required but missing: %s', 'revenue-generator' ),
					implode( ', ', $required_body_params_diff )
				),
				[
					'status' => 400,
				]
			);
		}

		$credentials = [
			'client_id'     => $body['client_id'],
			'client_secret' => $body['client_secret'],
		];

		$save = $this->save_credentials( $credentials );

		if ( ! $save ) {
			return new \WP_Error(
				'rg_rest_error_saving_credentials',
				__( 'There was an error saving credentials. Reason: invalid credentials.', 'revenue-generator' ),
				[
					'status' => 400,
				]
			);
		}

		$response = [
			'success' => true,
		];

		return rest_ensure_response( $response );
	}

	/**
	 * Check if user has a permission to operate REST API endpoints related to Auth.
	 *
	 * @return void|WP_Error Nothing on fulfilling requirements, WP_Error otherwise.
	 */
	public function rest_check_permission() {
		if ( ! current_user_can( self::REST_API_CAP ) ) {
			return new \WP_Error(
				'rg_rest_forbidden_context',
				__( 'Sorry, you are not authorized for this endpoint.', 'revenue-generator' ),
				[
					'status' => rest_authorization_required_code(),
				]
			);
		}

		return true;
	}

	/**
	 * Get client ID when making `GET` request to REST API endpoint.
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_get_client_data() {
		$data = [
			'clientId' => $this->get_client_id(),
		];

		return rest_ensure_response( $data );
	}

}

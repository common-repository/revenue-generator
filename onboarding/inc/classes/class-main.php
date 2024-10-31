<?php
/**
 * Onboarding class file.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Onboarding;

use \LaterPay\Revenue_Generator\Inc\Traits\Singleton;
use \LaterPay\Revenue_Generator\Inc\View;
use \LaterPay\Revenue_Generator\Inc\Admin;
use \LaterPay\Revenue_Generator\Inc\Post_Types\Contribution;

use \LaterPay\Revenue_Generator\Main as Plugin_Main;

/**
 * Main plugin class.
 */
class Main {

	use Singleton;

	/**
	 * Signup URL.
	 *
	 * @const string
	 */
	const SIGNUP_URL = 'https://app.laterpay.net/';

	/**
	 * Onboarding class constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_script' ] );
		add_action( 'wp_ajax_rgv2_verify_credentials', [ $this, 'ajax_verify_account_credentials' ] );
	}

	/**
	 * Callback for welcome screen used for onboarding.
	 */
	public function welcome_screen_callback() {
		Admin::load_assets();

		$plugin_main = Plugin_Main::get_instance();

		$template_path = REVENUE_GENERATOR_PLUGIN_DIR . '/onboarding/templates/welcome-new.php';

		if ( $plugin_main->is_existing_merchant() ) {
			$template_path = REVENUE_GENERATOR_PLUGIN_DIR . '/onboarding/templates/welcome-existing.php';
		}

		include $template_path;
	}

	/**
	 * Helper method to get signup URL constant.
	 *
	 * @return const SIGNUP_URL
	 */
	public static function get_signup_url() {
		return static::SIGNUP_URL;
	}

	/**
	 * Enqueue admin script for onboarding.
	 */
	public function enqueue_admin_script() {
		$current_screen = get_current_screen();

		if ( ! $current_screen || false === strpos( $current_screen->base, 'revenue-generator' ) ) {
			return;
		}

		/**
		 * If V2 script is registered (so V2 integration is loaded already),
		 * enqueue it since it contains everything we need for onboarding.
		 */
		if ( wp_script_is( 'revenue-generator-v2', 'registered' ) ) {
			wp_enqueue_script( 'revenue-generator-v2' );
		} else {
			/**
			 * Otherwise (if in V1 context) enqueue minimal script for onboarding.
			 */
			wp_register_script(
				'revenue-generator-v2-onboarding',
				REVENUE_GENERATOR_PLUGIN_URL . '/onboarding/assets/build/app.js',
				[
					'jquery',
					'backbone',
					'underscore',
				],
				REVENUE_GENERATOR_VERSION,
				true
			);

			$script_data = [
				'ajaxUrl'                  => admin_url( 'admin-ajax.php' ),
				'rgv2_global_config_nonce' => wp_create_nonce( 'rgv2_global_config_nonce' ),
				'contributionDashboardURL' => Contribution::get_dashboard_url(),
			];

			wp_localize_script(
				'revenue-generator-v2-onboarding',
				'rgOptions',
				$script_data
			);

			wp_enqueue_script( 'revenue-generator-v2-onboarding' );
		}
	}

	/**
	 * Process entered credentials passed by ajax.
	 *
	 * @return void
	 */
	public function ajax_verify_account_credentials() {
		// Verify authenticity.
		check_ajax_referer( 'rgv2_settings_nonce', 'security' );

		// Get all data and sanitize it.
		$client_id     = sanitize_text_field( filter_input( INPUT_POST, 'client_id', FILTER_SANITIZE_STRING ) );
		$client_secret = sanitize_text_field( filter_input( INPUT_POST, 'client_secret', FILTER_SANITIZE_STRING ) );

		// Set empty array for credentials.
		$credentials = [];

		// Assign `client_id` and `client_secret` keys to the array and fill it.
		$credentials['client_id']     = sanitize_text_field( $client_id );
		$credentials['client_secret'] = sanitize_text_field( $client_secret );

		/**
		 * If `Api\Auth` class exists, it means we're in V2 context. We have
		 * built-in methods there to verify credentials, so we'll just call
		 * them.
		 */
		if ( class_exists( 'Api\Auth' ) ) {
			$auth        = Api\Auth::get_instance();
			$credentials = $auth->get_credentials();

			$is_valid = $auth->validate_credentials( $credentials );
		} else {
			/**
			 * If we're in V1 instead, we validate credentials using the minimal
			 * method in this class.
			 */
			$is_valid = $this->validate_credentials( $credentials );
		}

		// Process response from `validate_credentials`.
		if ( true === $is_valid ) {
			// If in V2, save credentials using method in `Api\Auth`.
			if ( class_exists( 'Api\Auth' ) ) {
				$auth->save_credentials( $credentials );
			} else {
				// In V1, update option with credentials manually.
				update_option(
					'lp_rgv2_credentials',
					$credentials
				);

				/**
				 * Set "just onboarded" flag so we know the user just migrated
				 * to V2 and we know we can run necessary cleanup.
				 */
				update_option( 'lp_rgv2_just_onboarded', 1 );
			}

			$response = array(
				'success' => true,
				'msg'     => esc_html__( 'Saved valid credentials!', 'revenue-generator' ),
			);
		} else {
			$response = array(
				'success' => false,
				'msg'     => esc_html__( 'Invalid credentials!', 'revenue-generator' ),
			);
		}

		// Send success message.
		wp_send_json( $response );
	}

	/**
	 * Minimal method to validate credentials against live endpoint.
	 *
	 * @param array $credentials Credentials.
	 *
	 * @return bool
	 */
	private function validate_credentials( $credentials = [] ) {
		// Return early if empty.
		if ( empty( $credentials ) ) {
			return false;
		}

		// Encode credentials as required by API endpoint.
		$encoded_credentials = base64_encode(
			sprintf(
				'%s:%s',
				$credentials['client_id'],
				$credentials['client_secret']
			)
		);

		$auth_url = 'https://auth.laterpay.net/oauth2/token';

		if ( defined( 'REVENUE_GENERATOR_SANDBOX_MODE' ) && true === REVENUE_GENERATOR_SANDBOX_MODE ) {
			$auth_url = 'https://auth.sbx.laterpay.net/oauth2/token';
		}

		$headers = [
			'Content-Type'  => 'application/x-www-form-urlencoded',
			'Authorization' => 'Basic ' . $encoded_credentials,
		];

		$response = wp_remote_post(
			$auth_url,
			[
				'headers' => $headers,
				'body'    => [
					'grant_type' => 'authorization_code',
				],
			]
		);

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 401 === $response_code ) {
			return false;
		}

		return true;
	}

}

<?php
/**
 * Revenue Generator Plugin Main Class.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc;

use \LaterPay\Revenue_Generator\Inc\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 */
class Plugin {

	use Singleton;

	/**
	 * Class Plugin construct method.
	 */
	protected function __construct() {
		// Define required constants.
		$this->add_constants();

		// Initialize API.
		API::get_instance();

		// Initialize assets.
		Assets::get_instance();

		// Initialize plugin options.
		Config::get_instance();

		// Initialize plugin custom post types.
		Post_Types::get_instance();

		// Initialize admin backend class.
		Admin::get_instance();

		// Initialize frontend post class.
		Frontend_Post::get_instance();

		// Initialize settigns class.
		Settings::get_instance();

		// Intialize Shortcode class.
		Shortcodes::get_instance();

		// Setup required hooks.
		$this->setup_hooks();
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	protected function setup_hooks() {
		add_action( 'admin_init', [ $this, 'setup_migrations' ] );
	}

	/**
	 * Define required plugin constants.
	 */
	protected function add_constants() {
		define( 'REVENUE_GENERATOR_BUILD_DIR', REVENUE_GENERATOR_PLUGIN_DIR . '/v2/assets/build/' );
		define( 'REVENUE_GENERATOR_BUILD_URL', plugins_url( '/v2/assets/build/', REVENUE_GENERATOR_PLUGIN_FILE ) );
		define( 'REVENUE_GENERATOR_REST_NAMESPACE', 'revenue-generator/v1' );
	}

	/**
	 * Register migrations and run them.
	 *
	 * @hooked action `admin_init`
	 *
	 * @return void
	 */
	public function setup_migrations() {
		$migrations = Migrations::get_instance();

		$migrations->register( new Migrations\Migrate_V1_Options() );

		$migrations->run();
	}

	/**
	 * Get Laterpay signup URL.
	 *
	 * @return string
	 */
	public static function get_signup_url() {
		return 'https://app.laterpay.net/sign-up';
	}
}

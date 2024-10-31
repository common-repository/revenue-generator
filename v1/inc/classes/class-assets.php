<?php
/**
 * Revenue Generator Plugin Assets Class.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc;

use \LaterPay\Revenue_Generator\Inc\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Assets
 */
class Assets {

	use Singleton;

	/**
	 * Class Asset construct method.
	 */
	protected function __construct() {
		// Initialize plugin options.
		Config::get_instance();

		// Setup required hooks.
		$this->setup_hooks();
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	protected function setup_hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'register_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'load_admin_assets' ], 11 );
	}

	/**
	 * Register the required scripts.
	 */
	public function register_scripts() {

		// Register required library script for post/category selection.
		wp_register_script(
			'revenue-generator-select2',
			REVENUE_GENERATOR_BUILD_URL . 'vendor/select2/select2.min.js',
			[ 'jquery' ],
			$this->get_asset_version( 'vendor/select2/select2.min.js' ),
			true
		);

		wp_register_style(
			'revenue-generator-select2',
			REVENUE_GENERATOR_BUILD_URL . 'vendor/select2/select2.min.css',
			[],
			$this->get_asset_version( 'vendor/select2/select2.min.css' )
		);

		// Register required library script for plugin tour.
		wp_register_script(
			'revenue-generator-shepherd',
			REVENUE_GENERATOR_BUILD_URL . 'vendor/shepherd/shepherd.min.js',
			[],
			$this->get_asset_version( 'vendor/shepherd/shepherd.min.js' ),
			true
		);

		// Register required library scripts for tooltip.
		wp_register_script(
			'revenue-generator-popper',
			REVENUE_GENERATOR_BUILD_URL . 'vendor/tippy/popper.min.js',
			[],
			$this->get_asset_version( 'vendor/tippy/popper.min.js' ),
			true
		);

		wp_register_script(
			'revenue-generator-tippy',
			REVENUE_GENERATOR_BUILD_URL . 'vendor/tippy/tippy.min.js',
			[ 'revenue-generator-popper' ],
			$this->get_asset_version( 'vendor/tippy/tippy.min.js' ),
			true
		);

		wp_register_script(
			'revenue-generator',
			REVENUE_GENERATOR_BUILD_URL . 'revenue-generator-admin.js',
			[
				'jquery',
				'backbone',
				'underscore',
				'revenue-generator-shepherd',
				'revenue-generator-select2',
				'revenue-generator-tippy',
				'wp-util',
			],
			$this->get_asset_version( 'revenue-generator-admin.js' ),
			true
		);

		// Sets translated strings for JS script.
		wp_set_script_translations(
			'revenue-generator',
			'revenue-generator',
			REVENUE_GENERATOR_PLUGIN_DIR . 'languages/'
		);

		wp_register_style(
			'revenue-generator',
			REVENUE_GENERATOR_BUILD_URL . 'css/revenue-generator-dashboard.css',
			[],
			$this->get_asset_version( 'css/revenue-generator-dashboard.css' )
		);

		wp_register_style(
			'revenue-generator-admin',
			REVENUE_GENERATOR_BUILD_URL . 'css/revenue-generator-admin.css',
			[],
			$this->get_asset_version( 'css/revenue-generator-admin.css' )
		);
	}

	/**
	 * Enqueue the registered scripts.
	 */
	public function load_admin_assets() {
		global $current_screen;

		if ( $current_screen && false !== strpos( $current_screen->base, 'revenue-generator' ) ) {
			wp_enqueue_style( 'revenue-generator' );
		}

		wp_enqueue_style( 'revenue-generator-admin' );
	}

	/**
	 * Gets the file modified time for asset version.
	 *
	 * @param string $file Path to file.
	 *
	 * @return string Current modified time of the file.
	 *
	 * @codeCoverageIgnore  -- method checked in enqueue
	 */
	public function get_asset_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) {
			return filemtime( REVENUE_GENERATOR_BUILD_DIR . $file );
		} else {
			return REVENUE_GENERATOR_VERSION;
		}
	}
}

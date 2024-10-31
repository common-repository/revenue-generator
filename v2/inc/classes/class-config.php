<?php
/**
 * Revenue Generator Plugin Config Class.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc;

use \LaterPay\Revenue_Generator\Inc\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Config
 */
class Config {

	use Singleton;

	const OPTION_NAME = 'lp_rgv2_global_options';

	/**
	 * Store common values used in the plugin.
	 *
	 * @var array Common values used throughout the plugin.
	 */
	public static $plugin_defaults = [
		'img_dir' => REVENUE_GENERATOR_BUILD_URL . 'img/',
	];

	/**
	 * Class Config construct method.
	 */
	protected function __construct() {
		$this->setup_options();
	}

	/**
	 * Setup plugin options.
	 */
	protected function setup_options() {
		// Set default global options.
		$value = [
			'is_welcome_done'            => '',
			'contribution_tutorial_done' => 0,
			'is_merchant_verified'       => 0,
		];

		// Fresh install.
		if ( false === get_option( self::OPTION_NAME, false ) ) {
			update_option( self::OPTION_NAME, $value );
		}

		// Onboarding from V1.
		if ( false !== get_option( 'lp_rgv2_just_onboarded', false ) ) {
			$value['is_welcome_done']      = 'contribution';
			$value['is_merchant_verified'] = 1;

			update_option( self::OPTION_NAME, $value );

			delete_option( 'lp_rgv2_just_onboarded' );
		}
	}

	/**
	 * Returns plugin global options.
	 *
	 * @return array
	 */
	public static function get_global_options() {
		return get_option( self::OPTION_NAME, [] );
	}

	/**
	 * Get option by key.
	 *
	 * @param string $key Option key.
	 *
	 * @return string
	 */
	public static function get_option( $key = '' ) {
		if ( empty( $key ) ) {
			return;
		}

		$options = self::get_global_options();

		if ( ! isset( $options[ $key ] ) ) {
			return;
		}

		return $options[ $key ];
	}

	/**
	 * Updates the global options array.
	 *
	 * @param string $key   Key of the option to be updated.
	 * @param string $value New value for the option.
	 *
	 * @return void
	 */
	public static function update_global_options( $key, $value ) {
		$options         = self::get_global_options();
		$options[ $key ] = $value;

		update_option( self::OPTION_NAME, $options );
	}

	/**
	 * Get currency symbol.
	 *
	 * @return string
	 */
	public static function get_currency_symbol() {
		return '$';
	}

}

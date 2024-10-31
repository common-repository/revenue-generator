<?php
/**
 * Revenue Generator Plugin Settings Class.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc;

use \LaterPay\Revenue_Generator\Inc\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin
 */
class Settings {

	use Singleton;

	const OPTION_NAME = 'lp_rgv2_settings_options';

	/**
	 * Class Admin construct method.
	 */
	protected function __construct() {
		// Setup required hooks.
		$this->setup_hooks();
		// Setup settings Options.
		$this->setup_options();
	}

	/**
	 * Setup plugin options.
	 */
	protected function setup_options() {

		$settings_options = get_option( self::OPTION_NAME );

		// Fresh install or We don't have all the options.
		if (
			false === get_option( self::OPTION_NAME )
			|| ( ! empty( $settings_options )
			&& is_array( $settings_options )
			&& count( $settings_options ) < 4 )
		) {

			// Set default settings.
			update_option(
				self::OPTION_NAME,
				[
					'ga_personal_enabled_status' => 0,
					'ga_enabled_status'          => 0,
					'laterpay_ga_ua_id'          => 'UA-50448165-9',
					'personal_ga_ua_id'          => '',
				]
			);
		}
	}

	/**
	 * Returns plugin settings options.
	 *
	 * @return array
	 */
	public static function get_settings_options() {
		return get_option( self::OPTION_NAME, [] );
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	protected function setup_hooks() {
		add_action( 'wp_ajax_rgv2_update_settings_options', [ $this, 'update_settings_options' ] );
	}

	/**
	 * Update the settings with provided data.
	 *
	 * @param string $config_key settings configuration key.
	 * @param string $config_value settings configuration value.
	 * @return boolean
	 */
	public static function update_settings_options( $config_key, $config_value ) {

		$rgv2_settings_options = self::get_settings_options();

		// Check if the settings exists already.
		if ( ! isset( $rgv2_settings_options[ $config_key ] ) ) {
			wp_send_json(
				[
					'success' => false,
					'msg'     => __( 'Invalid data passed!', 'revenue-generator' ),
				]
			);
		}

		// Check and verify updated option.
		if ( ! empty( $config_value ) ) {
			$rgv2_settings_options[ $config_key ] = $config_value;
		} elseif ( ( 'ga_personal_enabled_status' === $config_key || 'ga_enabled_status' === $config_key ) && ! is_null( $config_value ) ) {
			$rgv2_settings_options[ $config_key ] = 0;
		} else {
			$rgv2_settings_options[ $config_key ] = '';
		}

		// Update the option value.
		if ( update_option( self::OPTION_NAME, $rgv2_settings_options ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Get Tracking Id of specified type.
	 *
	 * @param string $type Type whose tracking id to get.
	 *
	 * @return string
	 */
	public static function get_tracking_id( $type = '' ) {

		$rgv2_settings = get_option( self::OPTION_NAME, [] );

		if ( 'user' === $type ) {

			// Check if Personal Tracking Setting is Enabled.
			$is_enabled_user_tracking = ( ! empty( $rgv2_settings['ga_personal_enabled_status'] ) &&
			1 === intval( $rgv2_settings['ga_personal_enabled_status'] ) );

			// Add user tracking id if enabled.
			if ( $is_enabled_user_tracking && ! empty( $rgv2_settings['personal_ga_ua_id'] ) ) {
				return $rgv2_settings['personal_ga_ua_id'];
			}
		} else {

			// Check if LaterPay Tracking Setting is Enabled.
			$is_enabled_lp_tracking = ( ! empty( $rgv2_settings['ga_enabled_status'] ) &&
			1 === intval( $rgv2_settings['ga_enabled_status'] ) );

			// Add LaterPay Tracking Id if enabled. We will be using config value, not the one stored in option,
			// to make sure correct tracking id is, available for GA.
			if ( $is_enabled_lp_tracking && ! empty( $rgv2_settings['laterpay_ga_ua_id'] ) ) {
				return $rgv2_settings['laterpay_ga_ua_id'];
			}
		}

		return '';
	}
}

<?php
/**
 * Rev Gen options migration class file.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc\Migrations;

use LaterPay\Revenue_Generator\Inc\Settings;
use LaterPay\Revenue_Generator\Inc\Config;
use LaterPay\Revenue_Generator\Inc\Api;

/**
 * Rev Gen options migration class.
 */
final class Migrate_V1_Options extends Migration {

	/**
	 * Migration version.
	 *
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * We migrate following here:
	 *
	 * - Google Analytics settings.
	 * - Whether Contribution tutorial was completed.
	 *
	 * @return bool
	 */
	public function run() {
		$auth = Api\Auth::get_instance();

		if ( ! $auth->is_merchant_connected() ) {
			return false;
		}

		$v1_options        = get_option( 'lp_rg_settings_options', [] );
		$v1_global_options = get_option( 'lp_rg_global_options', [] );

		if ( ! empty( $v1_options ) ) {
			$rg_ga_enabled       = $v1_options['rg_ga_enabled_status'];

			$personal_ga_enabled = $v1_options['rg_ga_personal_enabled_status'];
			$personal_ga_ua_id   = $v1_options['rg_personal_ga_ua_id'];

			Settings::update_settings_options( 'ga_enabled_status', $rg_ga_enabled );
			Settings::update_settings_options( 'ga_personal_enabled_status', $personal_ga_enabled );
			Settings::update_settings_options( 'personal_ga_ua_id', $personal_ga_ua_id );
		}

		if ( ! empty( $v1_global_options ) ) {
			$contribution_tutorial_completed = $v1_global_options['contribution_tutorial_done'];

			Config::update_global_options( 'contribution_tutorial_done', $contribution_tutorial_completed );
		}

		return true;
	}

}

<?php
/**
 * Main class file.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator;

use \LaterPay\Revenue_Generator\Inc\Plugin;
use \LaterPay\Revenue_Generator\Inc\Traits\Singleton;

/**
 * Main plugin class.
 */
class Main {

	use Singleton;

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	private $integration_type = 'v2';

	/**
	 * Flag if onboarding is required.
	 *
	 * @var bool
	 */
	private $require_onboarding = false;

	/**
	 * Class constructor. Calls `decide_integration` to get the integration
	 * version. Calls onboarding if required.
	 */
	public function __construct() {
		$this->decide_integration();

		if ( $this->is_onboarding_required() ) {
			Onboarding\Main::get_instance();
		}

		$this->hook();
	}

	/**
	 * Register WP hooks.
	 */
	public function hook() {
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
	}

	/**
	 * Get integration type value.
	 *
	 * @return string
	 */
	public function get_integration_type() {
		return $this->integration_type;
	}

	/**
	 * Load plugin instance. This is the instance of either `v1` or `v2`
	 * already.
	 */
	public function load_plugin() {
		Plugin::get_instance();
	}

	/**
	 * Return value of `require_onboarding` var.
	 *
	 * @return var `require_onboarding`
	 */
	public function is_onboarding_required() {
		return $this->require_onboarding && $this->is_existing_merchant();
	}

	/**
	 * Decide which integration to load (v1 or v2).
	 *
	 * @return void
	 */
	public function decide_integration() {
		// If "force" constant is defined, load V1.
		if ( defined( 'REVENUE_GENERATOR_FORCE_V1' ) && true === REVENUE_GENERATOR_FORCE_V1 ) {
			$this->integration_type = 'v1';

			return;
		}

		// Poll paywalls using minimal query.
		$rg_paywalls = get_posts(
			[
				'post_type'      => 'rg_paywall',
				'posts_per_page' => 1,
				'post_status'    => 'any',
				'fields'         => 'ID',
			]
		);

		// If paywalls are found, load v1.
		if ( ! empty( $rg_paywalls ) ) {
			$this->integration_type = 'v1';

			return;
		}

		// If credentials for V2 are not stored, require onboarding.
		if ( empty( get_option( 'lp_rgv2_credentials', [] ) ) ) {
			$this->require_onboarding = true;

			// If merchant is not new, load v1 and launch onboarding from there.
			if ( $this->is_existing_merchant() ) {
				$this->integration_type = 'v1';
			}
		}
	}

	/**
	 * Helper method to check if the merchant is coming from Connector integration
	 * (v1) or is new. The check is performed by checking options that are
	 * available in V2 but not in V1.
	 *
	 * @return bool
	 */
	public function is_existing_merchant() {
		if ( empty( get_option( 'lp_rgv2_credentials', [] ) ) && ! empty( get_option( 'lp_rg_global_options', [] ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @codeCoverageIgnore -- Doesn't have mo files in the plugin, thus verification won't be possible.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'revenue-generator', false, REVENUE_GENERATOR_PLUGIN_DIR . 'languages/' );
	}

}

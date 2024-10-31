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

		// Get version from constant.
		$version = REVENUE_GENERATOR_VERSION;

		// Compare constant version with DB version.
		if ( $version <= get_option( 'lp_rg_version' ) ) {
			return;
		}

		// Fresh install.
		if ( false === get_option( 'lp_rg_global_options' ) ) {
			// @todo, make region and currency empty and let the merchant choose, once EU is ready on upstream.
			// Set default global options.
			update_option(
				'lp_rg_global_options',
				[
					'is_welcome_done'                    => '',
					'average_post_publish_count'         => '',
					'merchant_currency'                  => 'USD',
					'merchant_region'                    => 'US',
					'paywall_tutorial_done'              => 0,
					'contribution_tutorial_done'         => 0,
					'is_merchant_verified'               => 0,
				]
			);
		}

		// Update settings on version 1.0.1.
		if ( '1.0.1' >= $version ) {

			$settings_options                         = get_option( 'lp_rg_settings_options' );
			$settings_options['rg_laterpay_ga_ua_id'] = 'UA-50448165-9';
			// Enables GA for laterpay by default for already installed plugin.
			$settings_options['rg_ga_enabled_status'] = 1;
			update_option( 'lp_rg_settings_options', $settings_options );
		}

		// Update new version.
		update_option( 'lp_rg_version', REVENUE_GENERATOR_VERSION );
	}

	/**
	 * Returns plugin global options.
	 *
	 * @return array
	 */
	public static function get_global_options() {
		return get_option( 'lp_rg_global_options', [] );
	}

	/**
	 * Get pricing default values for all types else for requested publish count.
	 *
	 * @param string $publish_count Get merchant post publish count.
	 *
	 * @return array|mixed
	 */
	public static function get_pricing_defaults( $publish_count = '' ) {
		$all_price_defaults = self::get_price_defaults();
		if ( ! empty( $publish_count ) ) {
			return $all_price_defaults[ $publish_count ];
		} else {
			return $all_price_defaults;
		}
	}

	/**
	 * Returns default pricing values.
	 *
	 * @return array
	 */
	protected static function get_price_defaults() {
		return [
			'low'  => [
				'single_article' => [
					'tier_1' => [ // 0-250 content length.
						'price'   => 1.99,
						'revenue' => 'sis',
					],
					'tier_2' => [ // 251-500 content length.
						'price'   => 2.49,
						'revenue' => 'sis',
					],
					'tier_3' => [ // 501+ content length.
						'price'   => 3.49,
						'revenue' => 'sis',
					],
				],
				'time_pass'      => [
					'title'       => esc_html__( '24 Hour Pass', 'revenue-generator' ),
					'description' => esc_html__( 'Enjoy unlimited access to all our content for 24 hours.', 'revenue-generator' ),
					'price'       => 2.49,
					'revenue'     => 'sis',
					'duration'    => 'h',
					'period'      => '24',
				],
				'subscription'   => [
					'title'       => esc_html__( '1 Month Subscription', 'revenue-generator' ),
					'description' => esc_html__( 'Enjoy unlimited access to all our content for one month.', 'revenue-generator' ),
					'price'       => 4.99,
					'revenue'     => 'sis',
					'duration'    => 'm',
					'period'      => '1',
				],
			],
			'high' => [
				'single_article' => [
					'tier_1' => [ // 0-250 content length.
						'price'   => 0.49,
						'revenue' => 'ppu',
					],
					'tier_2' => [ // 251-500 content length.
						'price'   => 0.99,
						'revenue' => 'ppu',
					],
					'tier_3' => [ // 501+ content length.
						'price'   => 1.49,
						'revenue' => 'ppu',
					],
				],
				'time_pass'      => [
					'title'       => esc_html__( '24 Hour Pass', 'revenue-generator' ),
					'description' => esc_html__( 'Enjoy unlimited access to all our content for 24 hours.', 'revenue-generator' ),
					'price'       => 2.49,
					'revenue'     => 'sis',
					'duration'    => 'h',
					'period'      => '24',
				],
				'subscription'   => [
					'title'       => esc_html__( '1 Month Subscription', 'revenue-generator' ),
					'description' => esc_html__( 'Enjoy unlimited access to all our content for one month.', 'revenue-generator' ),
					'price'       => 4.99,
					'revenue'     => 'sis',
					'duration'    => 'm',
					'period'      => '1',
				],
			],
		];
	}

	/**
	 * Returns price value to work in connector config.
	 *
	 * @param float|int $price Price of the purchase option.
	 *
	 * @return string
	 */
	public static function get_connector_price( $price ) {
		return number_format( $price * 100, 0, '', '' );
	}

	/**
	 * Get current default limit prices.
	 *
	 * @return array
	 */
	public static function get_currency_limits() {
		return [
			'EUR' => [
				'ppu_min'        => 0.05,
				'ppu_only_limit' => 1.48,
				'ppu_max'        => 4.99,
				'sis_min'        => 1.49,
				'sis_only_limit' => 5.00,
				'sis_max'        => 1000.00,
			],
			'USD' => [
				'ppu_min'        => 0.05,
				'ppu_only_limit' => 1.98,
				'ppu_max'        => 4.99,
				'sis_min'        => 1.99,
				'sis_only_limit' => 5.00,
				'sis_max'        => 1000.00,
			],
		];
	}

	/**
	 * Default option data when new option is added.
	 *
	 * @return array
	 */
	public static function default_purchase_option() {
		return [
			'title'       => esc_html__( '1 Month Subscription', 'revenue-generator' ),
			'description' => esc_html__( 'Enjoy unlimited access to all our content for one month.', 'revenue-generator' ),
			'price'       => 4.99,
			'revenue'     => 'sis',
			'duration'    => 'm',
			'period'      => '1',
		];
	}

	/**
	 * Get currency symbol.
	 *
	 * @return string
	 */
	public static function get_currency_symbol() {
		$config_data = self::get_global_options();
		$symbol      = '';
		if ( ! empty( $config_data['merchant_currency'] ) ) {
			$symbol = 'USD' === $config_data['merchant_currency'] ? '$' : '€';
		}
		return $symbol;
	}

}

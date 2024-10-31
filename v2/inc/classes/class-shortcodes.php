<?php
/**
 * Revenue Generator Plugin Settings Class.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc;

use \LaterPay\Revenue_Generator\Inc\Traits\Singleton;
use \LaterPay\Revenue_Generator\Inc\Post_Types\Contribution;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin
 */
class Shortcodes {

	use Singleton;

	/**
	 * Shortcode namespace.
	 *
	 * @const string
	 */
	const SHORTCODE_NAMESPACE = 'laterpay_contribution';

	/**
	 * Class Admin construct method.
	 */
	protected function __construct() {
		// Setup required hooks.
		add_action( 'init', [ $this, 'setup_shortcodes' ] );
	}

	/**
	 * Setup Shortcodes.
	 */
	public function setup_shortcodes() {
		add_shortcode( self::SHORTCODE_NAMESPACE, array( $this, 'render_contribution_dialog' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_shortcode_assets' ) );
	}

	/**
	 * Adds Shortcode scripts.
	 */
	public function register_shortcode_assets() {
		global $post;

		$has_footer_contribution = ! empty( Contribution::get_footer_contribution_id() );

		if ( is_singular( Post_Types::get_allowed_post_types() ) || $has_footer_contribution ) {

			$global_options = Config::get_global_options();

			if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'laterpay_contribution' ) || $has_footer_contribution ) {
				$assets_instance = Assets::get_instance();
				// Enqueue frontend styling for shortcode.
				wp_enqueue_style(
					'revenue-generator-frontend',
					REVENUE_GENERATOR_BUILD_URL . 'css/frontend.css',
					[],
					$assets_instance->get_asset_version( 'css/frontend.css' )
				);

				// Register Fronted scripts for shortcode.
				wp_register_script(
					'revenue-generator-frontend-js',
					REVENUE_GENERATOR_BUILD_URL . '/frontend.js',
					[ 'jquery' ],
					$assets_instance->get_asset_version( 'frontend.js' ),
					true
				);

				// Eneque Scripts.
				wp_enqueue_script( 'revenue-generator-frontend-js' );
			}
		}
	}

	/**
	 * Display Contribution dialog for multiple amounts and Contribution amount button for single amount shortcode.
	 *
	 * The shortcode [laterpay_contribution] accepts these parameters:
	 * - type: Type of the Contribution, i.e Single / Multiple.
	 * - name: Name of the Campaign.
	 * - single_amount: Amount of Contribution, value in cents..
	 * - single_revenue: Revenue of the single amount, i.e Pay Now / Pay Later.
	 * - custom_amount: Custom Amount for Contribution dialog, if set amount will be pre-filled else empty.
	 * - all_amounts: A comma separated string containing configured amounts.
	 * - all_revenues: A comma separated string containing configured revenues.
	 * - selected_amount: Indicates default selected amount in the Contribution Dialog for Multiple Contributions.
	 *
	 * Basic example:
	 * [laterpay_contribution  name="Kerala Floods Relief" single_amount="400" single_revenue="ppu"]
	 * or:
	 * [laterpay_contribution  name="Dharamsala Animal Rescue" type="multiple" all_amounts="300,500,800" all_revenues="ppu,sis,sis" selected_amount="1"]
	 * or:
	 * [laterpay_contribution  name="Dharamsala Animal Rescue" type="multiple" custom_amount="1000" all_amounts="300,500" all_revenues="ppu,sis" selected_amount="1"]
	 *
	 * @param array $atts shortcode attributes.
	 */
	public function render_contribution_dialog( $atts ) {
		$default_atts = array(
			'id'                 => null,
			'type'               => 'multiple',
			'name'               => null,
			'dialog_header'      => __( 'Support the author', 'revenue-generator' ),
			'button_label'       => __( 'Support the author', 'revenue-generator' ),
			'dialog_description' => __( 'How much would you like to contribute?', 'revenue-generator' ),
			'custom_amount'      => null,
			'all_amounts'        => null,
			'selected_amount'    => null,
			'layout_type'        => 'box',
		);

		if ( isset( $atts['id'] ) && ! empty( $atts['id'] ) ) {
			$contribution_instance = Contribution::get_instance();
			$contribution_atts     = $contribution_instance->get( (int) $atts['id'] );

			/**
			 * If `$contribution_atts` returns `WP_Error`, it means that the
			 * Contribution was not found.
			 *
			 * Try to search contributions migrated from Revenue Generator
			 * in this case before giving up.
			 */
			if ( is_wp_error( $contribution_atts ) ) {
				$contribution_post = get_posts(
					[
						'post_type' => Contribution::SLUG,
						'posts_per_page' => 1,
						'meta_query' => [
							[
								'key'     => '_rgv2_rg_id',
								'value'   => (int) $atts['id'],
								'compare' => '=',
							],
						],
					]
				);

				if ( ! empty( $contribution_post ) ) {
					$found_id = $contribution_post[0]->ID;

					$contribution_atts = $contribution_instance->get( $found_id );
				}
			}

			$default_atts = $contribution_atts;

			// Finally, return early if Contribution was not found anywhere.
			if ( is_wp_error( $contribution_atts ) ) {
				if ( is_preview() ) {
					$message = __( 'This Contribution request has been deleted. Please delete this shortcode.', 'revenue-generator' );

					$html = sprintf(
						'<p class="%s">%s</p>',
						'rg-contribution-error',
						esc_html( $message )
					);

					return $html;
				} else {
					return;
				}
			}
		}

		$config_data = shortcode_atts(
			$default_atts,
			$atts
		);

		if ( ! isset( $config_data['ID'] ) ) {
			$config_data['ID'] = 0;
		}

		// Show error to current user?
		$show_error = is_user_logged_in() && current_user_can( 'manage_options' );

		// Validate shortcode attributes.
		$validation_result = self::is_contribution_config_valid( $config_data );

		// Display error if something went wrong.
		if ( $show_error && ! $validation_result ) {
			// Display Shortcode error.
			return sprintf( '<div class="rgv2-shortcode-error">%s</div>', $validation_result['message'] );
		}

		$global_options = Config::get_global_options();

		// backward compatibility.
		$campaign_name = ( isset( $config_data['name'] ) ) ? $config_data['name'] : $config_data['post_title'];

		$campaign_id = str_replace( ' ', '-', strtolower( $campaign_name ) ) . '-' . (string) time();

		// Get all amounts and revenues from shortcode.
		$multiple_amounts = array();

		if ( is_array( $config_data['all_amounts'] ) ) {
			$multiple_amounts = $config_data['all_amounts'];
		} else {
			$multiple_amounts  = explode( ',', $config_data['all_amounts'] );
		}

		$payment_config = [];

		// Loop through each amount  and configure amount attributes.
		foreach ( $multiple_amounts as $key => $value ) {
			$contribute_url = '#';

			$payment_config['amounts'][ $key ]['amount'] = $multiple_amounts[ $key ];
			$payment_config['amounts'][ $key ]['url']    = '#';
		}

		// View data for v2/templates/frontend/contribution/dialog-{type}.php.
		$view_args = array(
			'currency_symbol'    => '$',
			'contribution_id'    => $config_data['ID'],
			'dialog_header'      => $config_data['dialog_header'],
			'dialog_content'     => $config_data['dialog_content'],
			'button_label'       => $config_data['button_label'],
			'dialog_description' => $config_data['dialog_description'],
			'type'               => $config_data['type'],
			'name'               => $campaign_name,
			'payment_config'     => $payment_config,
			'layout_type'        => $config_data['layout_type'],
			'action_icons'       => [
				'back_arrow_icon' => Config::$plugin_defaults['img_dir'] . 'back-arrow.svg',
			],
			'is_amp'             => ( function_exists( '\is_amp_endpoint' ) && \is_amp_endpoint() ),
			'html_id'            => "rg_contribution_{$config_data['ID']}",
		);

		// Load the contributions dialog for User.
		return View::render_template( "frontend/contribution/dialog-{$config_data['layout_type']}", $view_args );

	}

	/**
	 * Check if the provided shortcode configuration for Contribution is valid or now.
	 *
	 * @param array $config_array Contribution configuration data.
	 *
	 * @return array|bool
	 */
	private static function is_contribution_config_valid( $config_array ) {

		// Check if campaign name is set.
		if ( empty( $config_array['name'] ) ) {
			return [
				'success' => false,
				'message' => __( 'Please enter a campaign name above.', 'revenue-generator' ),
			];
		}

		// Check if campaign amount is empty.
		if ( 'single' === $config_array['type'] ) {
			if ( floatval( $config_array['single_amount'] ) === floatval( 0.0 ) ) {
				return [
					'success' => false,
					'message' => __( 'Please enter a valid contribution amount above.', 'revenue-generator' ),
				];
			}
		}

		return true;
	}

}

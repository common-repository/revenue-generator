<?php
/**
 * Revenue Generator Plugin Settings Class.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc;

use \LaterPay\Revenue_Generator\Inc\Traits\Singleton;
use \LaterPay\Revenue_Generator\Inc\Revenue_Generator_Client;
use \LaterPay\Revenue_Generator\Inc\Post_Types\Contribution_Preview;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin
 */
class Shortcodes {

	use Singleton;

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
		add_shortcode( 'laterpay_contribution', array( $this, 'render_contribution_dialog' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_shortcode_assets' ) );
	}

	/**
	 * Adds Shortcode scripts.
	 */
	public function register_shortcode_assets() {
		global $post;
		if ( is_singular( Post_Types::get_allowed_post_types() ) ) {

			$global_options = Config::get_global_options();
			$region         = $global_options['merchant_region'];

			if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'laterpay_contribution' ) ) {
				$assets_instance = Assets::get_instance();
				// Enqueue frontend styling for shortcode.
				wp_enqueue_style(
					'revenue-generator-frontend',
					REVENUE_GENERATOR_BUILD_URL . 'css/revenue-generator-frontend.css',
					[],
					$assets_instance->get_asset_version( 'css/revenue-generator-frontend.css' )
				);

				// Register Fronted scripts for shortcode.
				wp_register_script(
					'revenue-generator-frontend-js',
					REVENUE_GENERATOR_BUILD_URL . '/revenue-generator-frontend.js',
					[ 'jquery' ],
					$assets_instance->get_asset_version( 'revenue-generator-frontend.js' ),
					true
				);

				// Localize scripts.
				wp_localize_script(
					'revenue-generator-frontend-js',
					'rgVars',
					array(
						'default_currency' => 'US' === $region ? 'USD' : 'EUR',
					)
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
	 * - thank_you: URL to which the user has to be redirected to, if empty redirect to shortcode page.
	 * - single_amount: Amount of Contribution, value in cents..
	 * - single_revenue: Revenue of the single amount, i.e Pay Now / Pay Later.
	 * - custom_amount: Custom Amount for Contribution dialog, if set amount will be pre-filled else empty.
	 * - all_amounts: A comma separated string containing configured amounts.
	 * - all_revenues: A comma separated string containing configured revenues.
	 * - selected_amount: Indicates default selected amount in the Contribution Dialog for Multiple Contributions.
	 *
	 * Basic example:
	 * [laterpay_contribution  name="Kerala Floods Relief" thank_you="" type="single" single_amount="400" single_revenue="ppu"]
	 * or:
	 * [laterpay_contribution  name="Dharamsala Animal Rescue" thank_you="" type="multiple" all_amounts="300,500,800" all_revenues="ppu,sis,sis" selected_amount="1"]
	 * or:
	 * [laterpay_contribution  name="Dharamsala Animal Rescue" thank_you="https://dharamsalaanimalrescue.org/" type="multiple" custom_amount="1000" all_amounts="300,500" all_revenues="ppu,sis" selected_amount="1"]
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
			'thank_you'          => null,
			'single_amount'      => null,
			'single_revenue'     => null,
			'custom_amount'      => null,
			'all_amounts'        => null,
			'all_revenues'       => null,
			'selected_amount'    => null,
			'layout_type'        => 'box',
		);

		if ( isset( $atts['id'] ) && ! empty( $atts['id'] ) ) {
			$contribution_instance = Post_Types\Contribution::get_instance();
			$contribution_atts     = $contribution_instance->get( (int) $atts['id'] );
			$default_atts          = $contribution_atts;

			if ( is_wp_error( $contribution_atts ) ) {
				if ( is_preview() ) {
					$message = __( 'This Contribution request has been deleted. Please delete this shortcode.', 'revenue-generator' );

					$html = sprintf(
						'<p class="%s">%s</p>',
						'rev-gen-contribution-error',
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

		// Show error to current user?
		$show_error = is_user_logged_in() && current_user_can( 'manage_options' );

		// Validate shortcode attributes.
		$validation_result = self::is_contribution_config_valid( $config_data );

		// Display error if something went wrong.
		if ( $show_error && ! $validation_result ) {
			// Display Shortcode error.
			return sprintf( '<div class="rg-shortcode-error">%s</div>', $validation_result['message'] );
		}

		// Set redirect URL, if empty use current page where shortcode resides.
		if ( ! empty( $config_data['thank_you'] ) ) {
			$current_url = $config_data['thank_you'];
		} else {
			global $wp;
			$current_url = trailingslashit( home_url( add_query_arg( array(), $wp->request ) ) );
		}

		$global_options = Config::get_global_options();
		$region         = $global_options['merchant_region'];

		// Bail early, if no region set.
		if ( empty( $region ) ) {
			return false;
		}

		$client_account = Client_Account::get_instance();
		$merchant_credentials = Client_Account::get_merchant_credentials();
		$endpoints = $client_account->get_endpoints();

		// Display Error Message if there are no credrentails.
		if ( is_wp_error( $endpoints ) ) {
			// Display Shortcode error.
			return sprintf( '<div class="rg-shortcode-error">%s</div>', __( 'Something went wrong, if you are a site admin please add merchant credentials in settings.', 'revenue-generator' ) );
		}

		// Configure contribution values.
		$payment_config    = [];
		$contribution_urls = '';
		$currency_config   = $client_account->get_currency();

		// backward compatibility.
		$campaign_name = $config_data['name'];
		if ( empty( $campaign_name ) ) {
			$campaign_name = $config_data['post_title'];
		}

		$campaign_id = str_replace( ' ', '-', strtolower( $campaign_name ) ) . '-' . (string) time();

		$client = $client_account->get_client_instance();

		if ( 'single' === $config_data['type'] ) {
			// Configure single amount contribution.
			$lp_revenue     = empty( $config_data['single_revenue'] ) ? 'ppu' : $config_data['single_revenue'];
			$payment_config = array(
				'amount'  => $config_data['single_amount'],
				'revenue' => $lp_revenue,
				'url'     => $client->get_single_contribution_url(
					array(
						'revenue'     => $lp_revenue,
						'campaign_id' => $campaign_id,
						'title'       => $campaign_name,
						'url'         => $current_url,
					)
				),
			);
		} else {
			// Get all amounts and revenues from shortcode.
			$multiple_amounts = array();

			if ( is_array( $config_data['all_amounts'] ) ) {
				$multiple_amounts = $config_data['all_amounts'];
			} else {
				$multiple_amounts  = explode( ',', $config_data['all_amounts'] );
			}

			if ( is_array( $config_data['all_revenues'] ) ) {
				$multiple_revenues = $config_data['all_revenues'];
			} else {
				$multiple_revenues = explode( ',', $config_data['all_revenues'] );
			}

			// Loop through each amount  and configure amount attributes.
			foreach ( $multiple_amounts as $key => $value ) {
				$contribute_url = $client->get_single_contribution_url(
					array(
						'revenue'     => $multiple_revenues[ $key ],
						'campaign_id' => $campaign_id,
						'title'       => $campaign_name,
						'url'         => $current_url,
					)
				);

				$payment_config['amounts'][ $key ]['amount']   = $multiple_amounts[ $key ];
				$payment_config['amounts'][ $key ]['revenue']  = $multiple_revenues[ $key ];
				$payment_config['amounts'][ $key ]['selected'] = absint( $config_data['selected_amount'] ) === $key + 1;
				$payment_config['amounts'][ $key ]['url']      = $contribute_url . '&custom_pricing=' . $currency_config['code'] . $multiple_amounts[ $key ];
			}

			// Only add custom amount if it was checked in backend.
			if ( isset( $config_data['custom_amount'] ) ) {
				$payment_config['custom_amount'] = $config_data['custom_amount'];

				// Generate contribution URL's for Pay Now and Pay Later revenue to handle custom amount.
				$contribution_urls = $client->get_contribution_urls(
					array(
						'campaign_id' => $campaign_id,
						'title'       => $campaign_name,
						'url'         => $current_url,
					)
				);
			}
		}

		// View data for revenue-generator/templates/frontend/contribution/dialog-{type}.php.
		$view_args = array(
			'currency_symbol'    => $currency_config['symbol'],
			'contribution_id'    => $config_data['ID'],
			'campaign_id'        => $campaign_id,
			'dialog_header'      => $config_data['dialog_header'],
			'button_label'       => $config_data['button_label'],
			'dialog_description' => $config_data['dialog_description'],
			'type'               => $config_data['type'],
			'name'               => $campaign_name,
			'thank_you'          => empty( $config_data['thank_you'] ) ? '' : $config_data['thank_you'],
			'contribution_urls'  => $contribution_urls,
			'payment_config'     => $payment_config,
			'action_icons'       => [
				'back_arrow_icon' => Config::$plugin_defaults['img_dir'] . 'back-arrow.svg',
			],
			'is_amp'             => ( function_exists( '\is_amp_endpoint' ) && \is_amp_endpoint() ),
			'is_preview'         => ( Contribution_Preview::SLUG === get_post_type() ),
			'html_id'            => "rev_gen_contribution_{$config_data['ID']}",
		);

		if ( $view_args['is_preview'] ) {
			$view_args['html_id'] = 'rev_gen_contribution_preview';
		}

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

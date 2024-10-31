<?php
/**
 * Revenue Generator Plugin Admin Class.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc;

use LaterPay\Revenue_Generator\Inc\Config;
use \LaterPay\Revenue_Generator\Inc\Post_Types\Paywall;
use LaterPay\Revenue_Generator\Inc\Post_Types\Contribution;
use LaterPay\Revenue_Generator\Inc\Post_Types\Subscription;
use LaterPay\Revenue_Generator\Inc\Post_Types\Time_Pass;
use LaterPay\Revenue_Generator\Inc\Settings;
use LaterPay\Revenue_Generator\Inc\Client_Account;
use \LaterPay\Revenue_Generator\Inc\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin
 */
class Admin {

	use Singleton;

	/**
	 * Class Admin construct method.
	 */
	protected function __construct() {
		// Setup required hooks.
		$this->setup_hooks();
	}

	/**
	 * Setup actions and filters.
	 *
	 * @return void
	 */
	protected function setup_hooks() {
		add_action( 'admin_menu', [ $this, 'revenue_generator_register_page' ] );
		add_action( 'admin_head', [ $this, 'hide_paywall' ] );
		add_action( 'current_screen', [ $this, 'redirect_merchant' ] );
		add_action( 'wp_ajax_rg_update_global_config', [ $this, 'update_global_config' ] );
		add_action( 'wp_ajax_rg_update_settings', [ $this, 'rg_update_settings' ] );
		add_action( 'wp_ajax_rg_update_paywall', [ $this, 'update_paywall' ] );
		add_action( 'wp_ajax_rg_update_currency_selection', [ $this, 'update_currency_selection' ] );
		add_action( 'wp_ajax_rg_remove_purchase_option', [ $this, 'remove_purchase_option' ] );
		add_action( 'wp_ajax_rg_remove_paywall', [ $this, 'remove_paywall' ] );
		add_action( 'wp_ajax_rg_search_preview_content', [ $this, 'search_preview_content' ] );
		add_action( 'wp_ajax_rg_select_preview_content', [ $this, 'select_preview_content' ] );
		add_action( 'wp_ajax_rg_search_term', [ $this, 'select_search_term' ] );
		add_action( 'wp_ajax_rg_search_post', [ $this, 'select_search_post' ] );
		add_action( 'wp_ajax_rg_clear_category_meta', [ $this, 'clear_category_meta' ] );
		add_action( 'wp_ajax_rg_complete_tour', [ $this, 'complete_tour' ] );
		add_action( 'wp_ajax_rg_verify_account_credentials', [ $this, 'verify_account_credentials' ] );
		add_action( 'wp_ajax_rg_post_permalink', [ $this, 'get_post_permalink' ] );
		add_action( 'wp_ajax_rg_activate_paywall', [ $this, 'activate_paywall' ] );
		add_action( 'wp_ajax_rg_disable_paywall', [ $this, 'disable_paywall' ] );
		add_action( 'wp_ajax_rg_restart_tour', [ $this, 'restart_tour' ] );
		add_action( 'wp_ajax_rg_set_paywall_order', [ $this, 'set_paywall_sort_order' ] );
		add_action( 'wp_ajax_rg_search_paywall', [ $this, 'search_paywall' ] );
		add_action( 'wp_ajax_rg_set_paywall_name', [ $this, 'rg_set_paywall_name' ] );
		add_action( 'wp_ajax_rg_contribution_save', [ $this, 'rg_contribution_save' ] );
		add_action( 'wp_ajax_rg_contribution_delete', [ $this, 'rg_contribution_delete' ] );
	}

	/**
	 * Handles Ajax Request for settings.
	 */
	public function rg_update_settings() {

		// Verify authenticity.
		check_ajax_referer( 'rg_global_config_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Cheating huh?', 'revenue-generator' ) );
		}

		$average_post_publish_count    = filter_input( INPUT_POST, 'average_post_publish_count', FILTER_SANITIZE_STRING );
		$merchant_id                   = filter_input( INPUT_POST, 'merchant_id', FILTER_SANITIZE_STRING );
		$merchant_key                  = filter_input( INPUT_POST, 'merchant_key', FILTER_SANITIZE_STRING );
		$rg_personal_ga_ua_id          = filter_input( INPUT_POST, 'rg_personal_ga_ua_id', FILTER_SANITIZE_STRING );
		$rg_ga_personal_enabled_status = filter_input( INPUT_POST, 'rg_ga_personal_enabled_status', FILTER_SANITIZE_NUMBER_INT );
		$rg_ga_enabled_status          = filter_input( INPUT_POST, 'rg_ga_enabled_status', FILTER_SANITIZE_NUMBER_INT );

		$rg_global_options = Config::get_global_options();

		$response = array();

		// Check and verify updated option.
		if ( ! empty( $average_post_publish_count ) ) {
			$rg_global_options['average_post_publish_count'] = $average_post_publish_count;

			update_option( 'lp_rg_global_options', $rg_global_options );
		}

		if ( ! empty( $rg_personal_ga_ua_id ) ) {
			Settings::update_settings_options( 'rg_personal_ga_ua_id', $rg_personal_ga_ua_id );
		}

		if ( isset( $rg_ga_personal_enabled_status ) ) {
			Settings::update_settings_options( 'rg_ga_personal_enabled_status', $rg_ga_personal_enabled_status );
		}

		if ( isset( $rg_ga_enabled_status ) ) {
			Settings::update_settings_options( 'rg_ga_enabled_status', $rg_ga_enabled_status );
		}

		// Verify Merchant Credentials.
		if ( ! empty( $merchant_id ) && ! empty( $merchant_key ) ) {

			$client_account_instance = Client_Account::get_instance();
			$rg_merchant_credentials = Client_Account::get_merchant_credentials();

			// Check and verify merchant id data.
			if ( ! empty( $merchant_id ) ) {
				$rg_merchant_credentials['merchant_id'] = $merchant_id;
			}

			// Check and verify merchant id data.
			if ( ! empty( $merchant_key ) ) {
				$rg_merchant_credentials['merchant_key'] = $merchant_key;
			}

			// Update the option value.
			update_option( 'lp_rg_merchant_credentials', $rg_merchant_credentials );

			$response['merchant'] = $client_account_instance->validate_merchant_account();

			// Set merchant status to verified.
			if ( true === $response['merchant'] ) {
				$rg_global_options                         = Config::get_global_options();
				$rg_global_options['is_merchant_verified'] = '1';
				update_option( 'lp_rg_global_options', $rg_global_options );
			}
		}

		if ( $response['merchant'] ) {
			$response['msg'] = esc_html__( 'Settings Saved!', 'revenue-generator' );
		} else {
			$response['msg'] = esc_html__( 'Wrong Merchant Crendetials', 'revenue-generator' );
		}

		wp_send_json_success( $response );

	}

	/**
	 * Load required assets in backend.
	 */
	public static function load_assets() {
		// Localize required data.
		$current_global_options = Config::get_global_options();

		$currency_limits   = Config::get_currency_limits();
		$merchant_currency = '';

		if ( ! empty( $current_global_options['merchant_currency'] ) ) {
			$merchant_currency = $currency_limits[ $current_global_options['merchant_currency'] ];
		}

		$lp_config_id            = Settings::get_tracking_id();
		$lp_user_tracking_id     = Settings::get_tracking_id( 'user' );
		$rg_merchant_credentials = Client_Account::get_merchant_credentials();

		$admin_menus  = self::get_admin_menus();
		$paywall_base = empty( $admin_menus['paywall'] ) ? '' : add_query_arg( [ 'page' => $admin_menus['paywall']['url'] ], admin_url( 'admin.php' ) );

		// Flag to pass to the script to check if merchant has paywalls too or just contributions.
		$has_contributions_only = false;

		// Try to fetch one paywall entry to see if there are any paywalls.
		$paywalls = get_posts(
			[
				'post_type'      => 'rg_paywall',
				'posts_per_page' => 1,
				'fields'         => 'ID',
			]
		);

		// If query didn't fetch a single paywall, set the flag to true.
		if ( empty( $paywalls ) ) {
			$has_contributions_only = true;
		}

		// Script date required for operations.
		$rg_script_data = [
			'globalOptions'         => $current_global_options,
			'ajaxUrl'               => admin_url( 'admin-ajax.php' ),
			'rg_paywall_nonce'      => wp_create_nonce( 'rg_paywall_nonce' ),
			'rg_setting_nonce'      => wp_create_nonce( 'rg_setting_nonce' ),
			'rg_contribution_nonce' => wp_create_nonce( 'rg_contribution_nonce' ),
			'rg_tracking_id'        => ( ! empty( $lp_config_id ) ) ? esc_html( $lp_config_id ) : '',
			'rg_user_tracking_id'   => ( ! empty( $lp_user_tracking_id ) ) ? esc_html( $lp_user_tracking_id ) : '',
			'currency'              => $merchant_currency,
			'locale'                => get_locale(),
			'paywallPageBase'       => $paywall_base,
			'rg_code_copy_msg'      => esc_html__( 'Code copied to clipboard', 'revenue-generator' ),
			'signupURL'             => [
				'US' => 'https://web.uselaterpay.com/dialog/entry/?redirect_to=/merchant/add/#/signup',
				'EU' => 'https://web.laterpay.net/dialog/entry/?redirect_to=/merchant/add/#/signup',
			],
			'defaultConfig'         => [
				'timepass'     => [
					'title'       => esc_html__( '24 Hour Pass', 'revenue-generator' ),
					'description' => esc_html__( 'Enjoy unlimited access to all our content for 24 hours.', 'revenue-generator' ),
					'price'       => 2.49,
					'revenue'     => 'sis',
					'duration'    => 'h',
					'period'      => '24',
				],
				'subscription' => [
					'title'       => esc_html__( '1 Month Subscription', 'revenue-generator' ),
					'description' => esc_html__( 'Enjoy unlimited access to all our content for one month.', 'revenue-generator' ),
					'price'       => 4.99,
					'revenue'     => 'sis',
					'duration'    => 'm',
					'period'      => '1',
				],
			],
			'welcome' => [
				'isMainWelcomeDone'    => ! empty( $current_global_options['is_welcome_done'] ),
				'isPaywallWelcomeDone' => ! empty( $current_global_options['average_post_publish_count'] ),
			],
			'has_contributions_only' => $has_contributions_only,
			'has_contributions'      => ( Contribution::has_contributions() ) ? 1 : 0,
			'has_paywalls'           => ( Paywall::has_paywalls() ) ? 1 : 0,
		];

		$rg_script_data['rg_global_config_nonce'] = wp_create_nonce( 'rg_global_config_nonce' );

		// Add Merchant ID for backend.
		if ( ! empty( $rg_merchant_credentials['merchant_id'] ) && is_admin() ) {
			$rg_script_data['merchant_id'] = $rg_merchant_credentials['merchant_id'];
		}

		// Create variable and add data.
		$rg_global_data = 'var revenueGeneratorGlobalOptions = ' . wp_json_encode( $rg_script_data ) . '; ';
		wp_add_inline_script( 'revenue-generator', $rg_global_data, 'before' );

		wp_enqueue_script( 'revenue-generator' );
		wp_enqueue_style( 'revenue-generator' );
		wp_enqueue_style( 'revenue-generator-select2' );
	}

	/**
	 * Hides paywall menu from submenu of plugin.
	 */
	public function hide_paywall() {
		// Hide paywall menu from submenu of plugin.
		remove_submenu_page( 'revenue-generator', 'revenue-generator-paywall' );
		remove_submenu_page( 'revenue-generator', 'revenue-generator' );
		remove_submenu_page( 'revenue-generator', 'revenue-generator-contribution' );
	}

	/**
	 * Register a new menu page for the Dashboard.
	 */
	public function revenue_generator_register_page() {
		$current_global_options = Config::get_global_options();

		// Check if setup is done, and load page accordingly.
		$is_welcome_setup_done = ( ! empty( $current_global_options['is_welcome_done'] ) ) ? $current_global_options['is_welcome_done'] : false;

		switch ( $is_welcome_setup_done ) {
			case 'contribution':
				$dashboard_callback = [ $this, 'load_contribution' ];
				break;

			case 'paywall':
				$dashboard_callback = [ $this, 'load_dashboard' ];
				break;

			default:
				$dashboard_callback = [ $this, 'load_welcome_screen' ];
				break;
		}

		$main = \LaterPay\Revenue_Generator\Main::get_instance();
		$onboarding_instance = \LaterPay\Revenue_Generator\Onboarding\Main::get_instance();

		if ( $main->is_onboarding_required() ) {
			$dashboard_callback = [ $onboarding_instance, 'welcome_screen_callback' ];
		}

		// Add main menu page.
		add_menu_page(
			__( 'Revenue Generator', 'revenue-generator' ),
			__( 'Revenue Generator', 'revenue-generator' ),
			'manage_options',
			'revenue-generator',
			$dashboard_callback,
			'dashicons-cto-logo',
			80
		);

		// Get all submenus and add it.
		$menus = self::get_admin_menus();
		if ( ! empty( $menus ) ) {
			foreach ( $menus as $key => $page_data ) {
				$slug          = $page_data['url'];
				$page_callback = [ $this, 'load_' . $page_data['method'] ];

				if ( $main->is_onboarding_required() ) {
					$page_callback = [ $onboarding_instance, 'welcome_screen_callback' ];
				}

				add_submenu_page(
					'revenue-generator',
					$page_data['title'] . ' | ' . __( 'Revenue Generator Settings', 'revenue-generator' ),
					$page_data['title'],
					$page_data['cap'],
					$slug,
					$page_callback
				);
			}
		}
	}

	/**
	 * Saves Contribution offer to the database and returns data like shortcode and edit link in JSON which are then
	 * used in JS.
	 *
	 * @since 1.1.0.
	 *
	 * @hooked action `wp_ajax_rg_contribution_save`
	 *
	 * @return void
	 */
	public function rg_contribution_save() {
		check_ajax_referer( 'rg_contribution_nonce', 'security' );

		$contribution_instance = Contribution::get_instance();

		$contribution_data = [
			'ID'                 => ( isset( $_REQUEST['ID'] ) ) ? intval( $_REQUEST['ID'] ) : 0,
			'name'               => ( isset( $_REQUEST['name'] ) ) ? sanitize_text_field( $_REQUEST['name'] ) : '',
			'thank_you'          => ( isset( $_REQUEST['thank_you'] ) ) ? esc_url_raw( $_REQUEST['thank_you'] ) : '',
			'dialog_header'      => ( isset( $_REQUEST['dialog_header'] ) ) ? sanitize_text_field( $_REQUEST['dialog_header'] ) : '',
			'dialog_description' => ( isset( $_REQUEST['dialog_description'] ) ) ? sanitize_text_field( $_REQUEST['dialog_description'] ) : '',
			'custom_amount'      => ( isset( $_REQUEST['custom-amount'] ) ) ? floatval( $_REQUEST['custom-amount'] ) : '',
			'code'               => ( isset( $_REQUEST['code'] ) ) ? sanitize_text_field( $_REQUEST['code'] ) : '',
			'layout_type'        => ( isset( $_REQUEST['layout_type'] ) ) ? sanitize_text_field( $_REQUEST['layout_type'] ) : '',
			'button_label'       => ( isset( $_REQUEST['button_label'] ) ) ? sanitize_text_field( $_REQUEST['button_label'] ) : '',
		];

		$is_edit = false;

		if ( ! empty( $contribution_data['ID'] ) ) {
			$is_edit = true;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$amounts      = isset( $_REQUEST['amounts'] ) ? $_REQUEST['amounts'] : [];
		$all_amounts  = [];
		$all_revenues = [];

		foreach ( $amounts as $index => $amount ) {
			$float_amount = (float) $amount;

			if ( 0.0 < $float_amount ) {
				$all_amounts[] = $float_amount * 100;
			}

			$all_revenues[] = ( 1.99 < $float_amount ) ? 'sis' : 'ppu';
		}

		$contribution_data['all_amounts']  = $all_amounts;
		$contribution_data['all_revenues'] = $all_revenues;

		$contribution_id   = $contribution_instance->save( $contribution_data );
		$message           = __( 'Oops! Something went wrong. Please try again.', 'revenue-generator' );
		$contribution_code = '';

		if ( ! empty( $contribution_id ) && ! is_wp_error( $contribution_id ) ) {
			$message = esc_html__( 'Successfully generated code, please paste at desired location.', 'revenue-generator' );

			if ( $is_edit ) {
				$message = esc_html__( 'Code successfully updated and copied to your clipboard.', 'revenue-generator' );
			}

			$generate_button_text = esc_html__( 'Code copied in your clipboard!', 'revenue-generator' );
			$contribution_code    = $contribution_instance->get_shortcode( $contribution_id );
		}

		wp_send_json(
			[
				'success'     => true,
				'msg'         => $message,
				'code'        => $contribution_code,
				'button_text' => $generate_button_text,
				'edit_link'   => $contribution_instance->get_edit_link( $contribution_id ),
			]
		);
	}

	/**
	 * Handles AJAX request for deleting Contribution offer from the database.
	 *
	 * @since 1.2.0.
	 *
	 * @hooked action `wp_ajax_rg_contribution_delete`
	 *
	 * @return void
	 */
	public function rg_contribution_delete() {
		check_ajax_referer( 'rg_contribution_delete_nonce', 'security' );

		$contribution_instance = Contribution::get_instance();
		$contribution_id       = ( isset( $_REQUEST['id'] ) ) ? (int) $_REQUEST['id'] : null;

		if ( is_null( $contribution_id ) ) {
			wp_send_json_error();
		}

		$delete = $contribution_instance->delete( $contribution_id );

		if ( is_wp_error( $delete ) ) {
			wp_send_json_error(
				[
					'msg' => $delete->get_error_message(),
				]
			);
		}

		wp_send_json_success(
			[
				'msg' => __( 'Contribution request deleted.', 'revenue-generator' ),
			]
		);
	}

	/**
	 * Load Contribution Dashboard.
	 */
	public function load_contributions() {
		self::load_assets();

		$admin_menus           = self::get_admin_menus();
		$contribution_instance = Contribution::get_instance();
		$config_data           = Config::get_global_options();

		// Ge Currencey Symbol.
		$symbol = Config::get_currency_symbol();

		// Contributions sorting orders.
		$sort_order               = filter_input( INPUT_GET, 'sort_by', FILTER_SANITIZE_STRING );
		$contribution_filter_args = array( 'order' => 'DESC' );
		$allowed_sort_order       = array( 'ASC', 'DESC' );

		// Contributions sorting orders.
		$sort_order = filter_input( INPUT_GET, 'sort_by', FILTER_SANITIZE_STRING );
		// If no sort param is set default to DESC.
		if ( empty( $sort_order ) ) {
			$new_sort_order = 'DESC';
			$sort_order     = 'desc';
		} else {
			$new_sort_order = in_array( strtoupper( $sort_order ), $allowed_sort_order, true ) ? strtoupper( $sort_order ) : 'DESC';
		}

		$contribution_filter_args['order'] = $new_sort_order;

		// Paywall Search Term.
		$search_term = filter_input( INPUT_GET, 'search_term', FILTER_SANITIZE_STRING );

		if ( ! empty( $search_term ) ) {
			$contribution_filter_args['rg_contribution_title'] = $search_term;
		}

		// Adds filter posts by title.
		add_filter( 'posts_where', [ $contribution_instance, 'rg_contribution_title_filter' ], 10, 2 );

		$dashboard_contributions = $contribution_instance->get_all_contributions( $contribution_filter_args );

		// Removes post filter by title.
		remove_filter( 'posts_where', [ $contribution_instance, 'rg_contribution_title_filter' ], 10 );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output is escaped in template file.
		$dashboard_page_data = [
			'new_contribution_url' => add_query_arg( [ 'page' => $admin_menus['contribution']['url'] ], admin_url( 'admin.php' ) ),
			'current_sort_order'   => $sort_order,
			'search_term'          => $search_term,
			'contributions'        => $dashboard_contributions,
			'currency_symbol'      => $symbol,
			'action_icons'         => [
				'lp_icon' => Config::$plugin_defaults['img_dir'] . 'lp-logo-icon.svg',
			],
		];

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output is escaped in template file.
		echo View::render_template( 'backend/contribution/dashboard', $dashboard_page_data );

		return '';
	}

	/**
	 * Create Contribution.
	 */
	public function load_contribution() {
		self::load_assets();

		$config_data = Config::get_global_options();
		$admin_menus = self::get_admin_menus();

		// Ge Currencey Symbol.
		$symbol = '';
		if ( ! empty( $config_data['merchant_currency'] ) ) {
			$symbol = 'USD' === $config_data['merchant_currency'] ? '$' : '€';
		}

		// Set merchant verification status.
		$is_merchant_verified = false;
		if ( 1 === absint( $config_data['is_merchant_verified'] ) ) {
			$is_merchant_verified = true;
		}

		$contributions_dashboard_url = add_query_arg(
			[
				'page' => $admin_menus['contributions']['url'],
			],
			admin_url( 'admin.php' )
		);

		$contribution_instance = Contribution::get_instance();
		$id                    = ( isset( $_GET['id'] ) ) ? intval( $_GET['id'] ) : 0;
		$contribution_data     = $contribution_instance->get( $id );

		if ( is_wp_error( $contribution_data ) ) {
			?>

			<?php esc_html_e( 'Contribution does not exist.', 'revenue-generator' ); ?> <a href="<?php echo esc_url( $contributions_dashboard_url ); ?>"><?php esc_html_e( 'Go back to dashboard', 'revenue-generator' ); ?></a>

			<?php
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output is escaped in template file.
		$dashboard_page_data = [
			'contribution_data'           => $contribution_data,
			'contributions_dashboard_url' => $contributions_dashboard_url,
			'is_merchant_verified'        => $is_merchant_verified,
			'currency_symbol'             => $symbol,
			'action_icons'                => [
				'lp_icon'     => Config::$plugin_defaults['img_dir'] . 'lp-logo-icon.svg',
				'option_info' => Config::$plugin_defaults['img_dir'] . 'option-info.svg',
				'icon_close'  => Config::$plugin_defaults['img_dir'] . 'icon-close.svg',
			],
		];

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output is escaped in template file.
		echo View::render_template( 'backend/contribution/create', $dashboard_page_data );

		return '';
	}

	/**
	 * Load admin welcome screen.
	 *
	 * @return string
	 *
	 * @codeCoverageIgnore -- Test will be covered in e2e tests.
	 */
	public function load_welcome_screen() {
		self::load_assets();
		$welcome_page_data = [
			'welcome_contribution_icon' => REVENUE_GENERATOR_BUILD_URL . 'img/welcome-contribution.svg',
			'welcome_paywall_icon'      => REVENUE_GENERATOR_BUILD_URL . 'img/welcome-paywall.svg',
		];
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output is escaped in template file.
		echo View::render_template( 'backend/welcome/welcome', $welcome_page_data );

		return '';
	}

	/**
	 * If the tutorial is incomplete, redirect user to paywall page before dashboard.
	 *
	 * @param \WP_Screen $current_screen Current \WP_Screen object.
	 */
	public function redirect_merchant( $current_screen ) {
		$current_global_options = Config::get_global_options();
		$admin_menus            = self::get_admin_menus();

		$dashboard_pages = [ 'toplevel_page_revenue-generator', 'revenue-generator_page_revenue-generator-dashboard' ];

		if ( in_array( $current_screen->id, $dashboard_pages, true ) && ! empty( $admin_menus ) ) {
			// Check if tutorial is completed, and load page accordingly.
			$is_welcome_setup_done         = empty( $current_global_options['average_post_publish_count'] ) ? false : true;
			$paywall_tutorial_done = (bool) $current_global_options['paywall_tutorial_done'];

			$paywall_page = add_query_arg( [ 'page' => $admin_menus['paywall']['url'] ], admin_url( 'admin.php' ) );

			if ( true === $is_welcome_setup_done && false === $paywall_tutorial_done ) {
				wp_safe_redirect( $paywall_page );
				exit;
			}
		}
	}

	/**
	 * Load admin dashboard.
	 *
	 * @return string
	 *
	 * @codeCoverageIgnore -- Test will be covered in e2e tests.
	 */
	public function load_dashboard() {
		self::load_assets();

		$paywall_instance = Paywall::get_instance();
		$admin_menus      = self::get_admin_menus();

		// Paywall sorting orders.
		$allowed_sort_order  = [ 'ASC', 'DESC' ];
		$sort_order          = filter_input( INPUT_GET, 'sort_by', FILTER_SANITIZE_STRING );
		$paywall_filter_args = [
			'order' => 'DESC',
		];

		// Paywall Search Term.
		$search_term = filter_input( INPUT_GET, 'search_term', FILTER_SANITIZE_STRING );

		// If no sort param is set default to DESC.
		if ( empty( $sort_order ) ) {
			$new_sort_order = 'DESC';
			$sort_order     = 'priority';
		} else {
			$new_sort_order = in_array( strtoupper( $sort_order ), $allowed_sort_order ) ? strtoupper( $sort_order ) : 'DESC';
		}

		// Add search parameter.
		if ( $search_term ) {
			$paywall_filter_args['rg_paywall_title'] = $search_term;
		}

		$paywall_filter_args['order'] = $new_sort_order;

		// Adds filter posts by title.
		add_filter( 'posts_where', [ $paywall_instance, 'rg_paywall_title_filter' ], 10, 2 );

		$dashboard_paywalls = $paywall_instance->get_all_paywalls( $paywall_filter_args );

		// Removes post filter by title.
		remove_filter( 'posts_where', [ $paywall_instance, 'rg_paywall_title_filter' ], 10 );

		// Sort paywall by priority, more details in class `Paywall` function `sort_paywall_by_priority()`.
		if ( 'priority' === $sort_order ) {
			$dashboard_paywalls = $paywall_instance->sort_paywall_by_priority( $dashboard_paywalls );
		}

		$dashboard_page_data = [
			'new_paywall_url'    => add_query_arg( [ 'page' => $admin_menus['paywall']['url'] ], admin_url( 'admin.php' ) ),
			'current_sort_order' => $sort_order,
			'paywalls'           => $dashboard_paywalls,
			'search_term'        => $search_term,
			'action_icons'       => [
				'lp_icon' => Config::$plugin_defaults['img_dir'] . 'lp-logo-icon.svg',
			],
		];

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output is escaped in template file.
		echo View::render_template( 'backend/dashboard/dashboard', $dashboard_page_data );

		return '';
	}

	/**
	 * Load admin paywall.
	 *
	 * @return string
	 *
	 * @codeCoverageIgnore -- Test will be covered in e2e tests.
	 */
	public function load_paywall() {
		self::load_assets();
		$config_data = Config::get_global_options();

		$post_types = Post_Types::get_instance();

		$current_paywall    = filter_input( INPUT_GET, 'current_paywall', FILTER_SANITIZE_NUMBER_INT );
		$prepare_paywall    = filter_input( INPUT_GET, 'prepare_paywall', FILTER_SANITIZE_NUMBER_INT );
		$rg_categories_data = array();
		$rg_specific_posts  = array();

		// Load paywall related content if found else load requested content for new paywall.
		if ( ! empty( $current_paywall ) ) {
			// Get selected post info from paywall.
			$formatted_post_data = $post_types->get_post_post_content_by_paywall_id( $current_paywall );

			// Get paywall options data.
			$purchase_options_data = $post_types->get_post_purchase_options_by_paywall_id( $current_paywall );
			$purchase_options      = $post_types->convert_to_purchase_options( $purchase_options_data );

			$paywall_data = $purchase_options['paywall'];

			if ( ! empty( $paywall_data ) ) {
				if ( 'category' === $paywall_data['access_to'] || 'exclude_category' === $paywall_data['access_to'] ) {

					$rg_categories_id = $paywall_data['access_entity'];
					if ( ! empty( $rg_categories_id ) && is_array( $rg_categories_id ) ) {
						foreach ( $rg_categories_id as $rg_category_id ) {
							$rg_categories_data[] = get_term( $rg_category_id, 'category' );
						}
					} else {
						$rg_categories_data[] = get_term( $rg_categories_id, 'category' );
					}
				}

				// Get Specific posts if user has selected type specific posts.
				if ( 'specific_post' === $paywall_data['access_to'] ) {
					$rg_specific_posts_ids = $paywall_data['specific_posts'];
					if ( ! empty( $rg_specific_posts_ids ) ) {
						foreach ( $rg_specific_posts_ids as $rg_specific_post_id ) {
							$rg_specific_posts[ $rg_specific_post_id ] = get_the_title( $rg_specific_post_id );
						}
					}
				}
			}
		} else {

			$post_preview_id     = filter_input( INPUT_GET, 'preview_post_id', FILTER_SANITIZE_NUMBER_INT );
			$target_post_id      = 0;
			$formatted_post_data = [];

			if ( ! empty( $post_preview_id ) ) {
				$target_post_id = $post_preview_id;
			}

			if ( ! empty( $target_post_id ) ) {
				$formatted_post_data = $post_types->get_formatted_post_data( $target_post_id );
			}

			// Get formatted data again for latest post id.
			if ( empty( $formatted_post_data ) ) {
				// Get latest post info for preview.
				$target_post_id = $post_types->get_latest_post_for_preview();
			}

			$formatted_post_data = $post_types->get_formatted_post_data( $target_post_id );

			// Get paywall options data.
			$purchase_options_data = $post_types->get_post_purchase_options_by_post_id( $target_post_id, $formatted_post_data );
			$purchase_options      = $post_types->convert_to_purchase_options( $purchase_options_data );
		}

		// Previews Paywall from Metabox and preselect posts.
		if ( $prepare_paywall ) {
			$purchase_options['paywall']['access_to'] = 'supported';
		}

		// Get individual article pricing based on post content word count, i.e "tier".
		$post_tier                 = empty( $formatted_post_data['post_content'] ) ? 'tier_1' : $post_types->get_post_tier( $formatted_post_data['post_content'] );
		$purchase_options_all      = Config::get_pricing_defaults( $config_data['average_post_publish_count'] );
		$post_dynamic_pricing_data = $purchase_options_all['single_article'][ $post_tier ];

		// Set currency symbol.
		$default_option_data = $post_types->get_default_purchase_option();
		$symbol              = '';
		if ( ! empty( $config_data['merchant_currency'] ) ) {
			$symbol = $config_data['merchant_currency'];
		}

		// Set merchant verification status.
		$is_merchant_verified = false;
		if ( 1 === absint( $config_data['is_merchant_verified'] ) ) {
			$is_merchant_verified = true;
		}

		$default_paywall_title = '';
		// Generate default incremental paywall title.
		if ( empty( $current_paywall ) ) {
			$default_paywall_title = $this->generate_default_paywall_title();
		}

		$admin_menus = self::get_admin_menus();

		// Paywall purchase options data.
		$post_preview_data = [
			'default_paywall_title' => $default_paywall_title,
			'rg_preview_post'       => $formatted_post_data,
			'purchase_options_data' => $purchase_options,
			'default_option_data'   => $default_option_data,
			'dynamic_pricing_data'  => $post_dynamic_pricing_data,
			'merchant_symbol'       => $symbol,
			'rg_categories_data'    => $rg_categories_data,
			'rg_specific_posts'     => $rg_specific_posts,
			'is_merchant_verified'  => $is_merchant_verified,
			'new_paywall_url'       => add_query_arg( [ 'page' => $admin_menus['paywall']['url'] ], admin_url( 'admin.php' ) ),
			'dashboard_url'         => add_query_arg( [ 'page' => $admin_menus['dashboard']['url'] ], admin_url( 'admin.php' ) ),
			'action_icons'          => [
				'high_count_icon'    => Config::$plugin_defaults['img_dir'] . 'high-publish.svg',
				'lp_icon'            => Config::$plugin_defaults['img_dir'] . 'lp-logo-icon.svg',
				'option_add'         => Config::$plugin_defaults['img_dir'] . 'add-option.svg',
				'option_edit'        => Config::$plugin_defaults['img_dir'] . 'edit-option.svg',
				'option_remove'      => Config::$plugin_defaults['img_dir'] . 'remove-option.svg',
				'option_move_up'     => Config::$plugin_defaults['img_dir'] . 'move-up.svg',
				'option_move_down'   => Config::$plugin_defaults['img_dir'] . 'move-down.svg',
				'option_move_around' => Config::$plugin_defaults['img_dir'] . 'move-option.svg',
				'option_warning'     => Config::$plugin_defaults['img_dir'] . 'option-warning.svg',
				'option_info'        => Config::$plugin_defaults['img_dir'] . 'option-info.svg',
				'option_dynamic'     => Config::$plugin_defaults['img_dir'] . 'option-dynamic.svg',
			],
		];

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output is escaped in template file.
		echo View::render_template( 'backend/paywall/post-preview', $post_preview_data );

		return '';
	}

	/**
	 * Load plugin settings.
	 *
	 * @return string
	 *
	 * @codeCoverageIgnore -- Test will be covered in e2e tests.
	 */
	public function load_settings() {
		global $wp_roles;
		self::load_assets();

		$args = array(
			'hide_empty' => false,
			'taxonomy'   => 'category',
		);

		$default_roles = array( 'administrator' );
		$custom_roles  = array();
		$categories    = array();

		$rg_merchant_credentials = Client_Account::get_merchant_credentials();
		$rg_global_options       = Config::get_global_options();
		$rg_settings_options     = Settings::get_settings_options();

		// get categories and add them to the array.
		$wp_categories = get_categories( $args );
		foreach ( $wp_categories as $category ) {
			$categories[ $category->term_id ] = $category->name;
		}

		// get all roles.
		foreach ( $wp_roles->roles as $key_role => $role_data ) {

			if ( ! in_array( $key_role, $default_roles, true ) ) {
				$custom_roles[ $key_role ] = $role_data['name'];
			}
		}

		$settings_page_data      = [
			'merchant_credentials' => $rg_merchant_credentials,
			'global_options'       => $rg_global_options,
			'settings_options'     => $rg_settings_options,
			'user_roles'           => $custom_roles,
			'categories'           => $categories,
			'action_icons'         => [
				'lp_icon'     => Config::$plugin_defaults['img_dir'] . 'lp-logo-icon.svg',
				'option_info' => Config::$plugin_defaults['img_dir'] . 'option-info.svg',
			],
		];

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output is escaped in template file.
		echo View::render_template( 'backend/settings/settings', $settings_page_data );

		return '';
	}

	/**
	 * Generates default paywall title.
	 */
	public function generate_default_paywall_title() {
		$paywall_instance      = Paywall::get_instance();
		$paywall_post_count    = $paywall_instance->get_paywalls_count();
		$default_paywall_count = ( ! empty( $paywall_post_count ) ) ? (int) $paywall_post_count + 1 : 1;
		$default_paywall_title = esc_html__( 'Paywall 1', 'revenue-generator' );

		if ( $default_paywall_count ) {

			/* translators: Default paywall title with incrementing count. */
			$default_paywall_title = sprintf( esc_html__( 'Paywall %s', 'revenue-generator' ), $default_paywall_count );

		}

		return $default_paywall_title;
	}


	/**
	 * Update the global config with provided data.
	 *
	 * @codeCoverageIgnore -- @todo add AJAX test base class to cover this.
	 */
	public function update_global_config() {

		// Verify authenticity.
		check_ajax_referer( 'rg_global_config_nonce', 'security' );

		// Get all data and sanitize it.
		$config_key           = sanitize_text_field( filter_input( INPUT_POST, 'config_key', FILTER_SANITIZE_STRING ) );
		$config_value         = sanitize_text_field( filter_input( INPUT_POST, 'config_value', FILTER_SANITIZE_STRING ) );
		$rg_ga_enabled_status = filter_input( INPUT_POST, 'rg_ga_enabled_status', FILTER_SANITIZE_NUMBER_INT );

		$rg_global_options  = Config::get_global_options();
		$rg_global_settings = Settings::get_settings_options();

		// Update Tracking Settings.
		if ( isset( $rg_global_settings['rg_ga_enabled_status'] ) && isset( $rg_ga_enabled_status ) ) {
			Settings::update_settings_options( 'rg_ga_enabled_status', $rg_ga_enabled_status );
		}

		// Check if the option exists already.
		if ( ! isset( $rg_global_options[ $config_key ] ) ) {
			wp_send_json(
				[
					'success' => false,
					'msg'     => __( 'Invalid data passed!', 'revenue-generator' ),
				]
			);
		}

		// Check and verify updated option.
		if ( ! empty( $config_value ) ) {
			$rg_global_options[ $config_key ] = $config_value;
		}

		// Update the option value.
		update_option( 'lp_rg_global_options', $rg_global_options );

		// Send success message.
		wp_send_json(
			[
				'success' => true,
				'msg'     => __( 'Selection stored successfully!', 'revenue-generator' ),
			]
		);

	}

	/**
	 * Define admin menus used in the plugin.
	 *
	 * @return array
	 */
	public static function get_admin_menus() {
		$menus                  = [];
		$current_global_options = Config::get_global_options();

		// Check if tutorial is completed, and load page accordingly.
		$is_welcome_setup_done = ( ! empty( $current_global_options['is_welcome_done'] ) ) ? $current_global_options['is_welcome_done'] : false;

		if ( ! empty( $is_welcome_setup_done ) ) {
			$menus['dashboard'] = [
				'url'    => 'revenue-generator-dashboard',
				'title'  => __( 'Paywall', 'revenue-generator' ),
				'cap'    => 'manage_options',
				'method' => 'dashboard',
			];

			$menus['contributions'] = [
				'url'    => Contribution::ADMIN_DASHBOARD_SLUG,
				'title'  => __( 'Contributions', 'revenue-generator' ),
				'cap'    => 'manage_options',
				'method' => 'contributions',
			];

			$menus['contribution'] = [
				'url'    => Contribution::ADMIN_EDIT_SLUG,
				'title'  => __( 'Contribution', 'revenue-generator' ),
				'cap'    => 'manage_options',
				'method' => 'contribution',
			];

			$menus['paywall'] = [
				'url'    => 'revenue-generator-paywall',
				'title'  => __( 'New Paywall', 'revenue-generator' ),
				'cap'    => 'manage_options',
				'method' => 'paywall',
			];

			$menus['settings'] = [
				'url'    => 'revenue-generator-settings',
				'title'  => __( 'Settings', 'revenue-generator' ),
				'cap'    => 'manage_options',
				'method' => 'settings',
			];
		}

		return $menus;
	}

	/**
	 * Update Paywall.
	 */
	public function update_paywall() {
		// Verify authenticity.
		check_ajax_referer( 'rg_paywall_nonce', 'security' );

		// Sanitize the data.
		$rg_post_id      = sanitize_text_field( filter_input( INPUT_POST, 'post_id', FILTER_DEFAULT ) );
		$paywall_data    = filter_input( INPUT_POST, 'paywall', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$individual_data = filter_input( INPUT_POST, 'individual', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$time_passes     = filter_input( INPUT_POST, 'time_passes', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$subscriptions   = filter_input( INPUT_POST, 'subscriptions', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		$paywall_instance       = Paywall::get_instance();
		$paywall['name']        = sanitize_text_field( wp_unslash( $paywall_data['name'] ) );
		$paywall['description'] = sanitize_text_field( wp_unslash( $paywall_data['desc'] ) );
		$paywall['title']       = sanitize_text_field( wp_unslash( $paywall_data['title'] ) );
		$paywall['id']          = sanitize_text_field( $paywall_data['id'] );
		$paywall['access_to']   = sanitize_text_field( wp_unslash( $paywall_data['applies'] ) );
		$paywall['preview_id']  = sanitize_text_field( wp_unslash( $paywall_data['preview_id'] ) );

		if ( ! empty( $paywall['access_to'] ) && ( 'category' === $paywall['access_to'] || 'exclude_category' === $paywall['access_to'] ) ) {
			$rg_post_id = json_decode( $rg_post_id );
		}

		$paywall['access_entity']  = $rg_post_id;
		$specific_posts            = sanitize_text_field( wp_unslash( $paywall_data['specific_posts'] ) );
		$paywall['specific_posts'] = json_decode( $specific_posts );

		$order_items = [];

		// Create Paywall.
		$paywall_id = $paywall_instance->update_paywall( $paywall );

		if ( ! empty( $individual_data ) ) {
			$individual['title']        = sanitize_text_field( wp_unslash( $individual_data['title'] ) );
			$individual['description']  = sanitize_text_field( wp_unslash( $individual_data['desc'] ) );
			$individual['price']        = floatval( $individual_data['price'] );
			$individual['revenue']      = sanitize_text_field( $individual_data['revenue'] );
			$individual['type']         = sanitize_text_field( $individual_data['type'] );
			$individual['custom_title'] = sanitize_text_field( $individual_data['custom_title'] );
			$individual['custom_desc']  = sanitize_text_field( $individual_data['custom_desc'] );
			$order_items['individual']  = absint( $individual_data['order'] );

			// Add Individual option to paywall.
			$paywall_instance->update_paywall_individual_option( $paywall_id, $individual );
		}

		// Save Paywall ID in selected specific posts meta.
		if ( ! empty( $paywall['specific_posts'] ) ) {
			foreach ( $paywall['specific_posts'] as $post_id ) {
				update_post_meta( $post_id, '_rg_specific_paywall', $paywall_id );
			}
		}

		$time_pass_instance = Time_Pass::get_instance();
		$time_pass_response = [];

		if ( ! empty( $time_passes ) ) {
			// Store time pass.
			foreach ( $time_passes as $time_pass ) {
				if ( isset( $time_pass['tlp_id'] ) ) {
					$timepass['id'] = sanitize_text_field( $time_pass['tlp_id'] );
				}
				$timepass['title']        = sanitize_text_field( wp_unslash( $time_pass['title'] ) );
				$timepass['description']  = sanitize_text_field( wp_unslash( $time_pass['desc'] ) );
				$timepass['price']        = floatval( $time_pass['price'] );
				$timepass['revenue']      = sanitize_text_field( $time_pass['revenue'] );
				$timepass['duration']     = sanitize_text_field( $time_pass['duration'] );
				$timepass['period']       = sanitize_text_field( $time_pass['period'] );
				$timepass['access_to']    = 'all'; // @todo make it dynamic
				$timepass['custom_title'] = sanitize_text_field( $time_pass['custom_title'] );
				$timepass['custom_desc']  = sanitize_text_field( $time_pass['custom_desc'] );

				$tp_id                                   = $time_pass_instance->update_time_pass( $timepass );
				$time_pass_response[ $time_pass['uid'] ] = $tp_id;
				$order_items[ 'tlp_' . $tp_id ]          = absint( $time_pass['order'] );
			}
		}

		$subscription_instance = Subscription::get_instance();
		$subscription_response = [];

		if ( ! empty( $subscriptions ) ) {
			// Store time pass.
			foreach ( $subscriptions as $subscription_data ) {
				if ( isset( $subscription_data['sub_id'] ) ) {
					$subscription['id'] = sanitize_text_field( $subscription_data['sub_id'] );
				}
				$subscription['title']        = sanitize_text_field( wp_unslash( $subscription_data['title'] ) );
				$subscription['description']  = sanitize_text_field( wp_unslash( $subscription_data['desc'] ) );
				$subscription['price']        = floatval( $subscription_data['price'] );
				$subscription['revenue']      = sanitize_text_field( $subscription_data['revenue'] );
				$subscription['duration']     = sanitize_text_field( $subscription_data['duration'] );
				$subscription['period']       = sanitize_text_field( $subscription_data['period'] );
				$subscription['access_to']    = 'all'; // @todo make it dynamic
				$subscription['custom_title'] = sanitize_text_field( $subscription_data['custom_title'] );
				$subscription['custom_desc']  = sanitize_text_field( $subscription_data['custom_desc'] );

				$sub_id                                             = $subscription_instance->update_subscription( $subscription );
				$subscription_response[ $subscription_data['uid'] ] = $sub_id;
				$order_items[ 'sub_' . $sub_id ]                    = absint( $subscription_data['order'] );
			}
		}

		$paywall_instance->update_paywall_option_order( $paywall_id, $order_items );

		// Set redirect for saved paywall.
		$admin_menus   = self::get_admin_menus();
		$dashboard_url = add_query_arg( [ 'page' => $admin_menus['dashboard']['url'] ], admin_url( 'admin.php' ) );

		// Send success message.
		wp_send_json(
			[
				'success'       => true,
				'redirect_to'   => $dashboard_url,
				'msg'           => empty( $paywall_data['id'] ) ? __( 'Paywall updated successfully!', 'revenue-generator' ) : __( 'Paywall saved successfully!', 'revenue-generator' ),
				'paywall_id'    => $paywall_id,
				'time_passes'   => $time_pass_response,
				'subscriptions' => $subscription_response,
			]
		);

	}

	/**
	 * Update the global currency config.
	 */
	public function update_currency_selection() {
		// Verify authenticity.
		check_ajax_referer( 'rg_paywall_nonce', 'security' );

		// Get all data and sanitize it.
		$config_key   = sanitize_text_field( filter_input( INPUT_POST, 'config_key', FILTER_SANITIZE_STRING ) );
		$config_value = sanitize_text_field( filter_input( INPUT_POST, 'config_value', FILTER_SANITIZE_STRING ) );

		$rg_global_options = Config::get_global_options();

		// Check if the option exists already.
		if ( ! isset( $rg_global_options[ $config_key ] ) ) {
			wp_send_json(
				[
					'success' => false,
					'msg'     => __( 'Invalid data passed!', 'revenue-generator' ),
				]
			);
		}

		// Check and verify updated option.
		if ( ! empty( $config_value ) ) {
			$rg_global_options[ $config_key ]     = $config_value;
			$region                               = 'USD' === $config_value ? 'US' : 'EU';
			$rg_global_options['merchant_region'] = $region;
		}

		// Update the option value.
		update_option( 'lp_rg_global_options', $rg_global_options );

		// Send success message.
		wp_send_json(
			[
				'success' => true,
				'msg'     => __( 'Currency stored successfully!', 'revenue-generator' ),
			]
		);

	}

	/**
	 * Update the global currency config.
	 */
	public function remove_purchase_option() {

		// Verify authenticity.
		check_ajax_referer( 'rg_paywall_nonce', 'security' );

		// Get all data and sanitize it.
		$purchase_option_type = sanitize_text_field( filter_input( INPUT_POST, 'type', FILTER_SANITIZE_STRING ) );
		$purchase_option_id   = sanitize_text_field( filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT ) );
		$paywall_id           = sanitize_text_field( filter_input( INPUT_POST, 'paywall_id', FILTER_SANITIZE_NUMBER_INT ) );

		$paywall_instance      = Paywall::get_instance();
		$time_pass_instance    = Time_Pass::get_instance();
		$subscription_instance = Subscription::get_instance();

		$msg = '';
		if ( 'individual' === $purchase_option_type ) {
			$paywall_instance->remove_individual_purchase_option( $purchase_option_id );
			$msg = __( 'Purchase option removed!', 'revenue-generator' );
		} elseif ( 'subscription' === $purchase_option_type ) {
			if ( $subscription_instance->remove_subscription_purchase_option( $purchase_option_id, $paywall_id ) ) {
				$msg = __( 'Subscription removed!', 'revenue-generator' );
			} else {
				$msg = __( 'Unable to remove Subscription, something went wrong!', 'revenue-generator' );
			}
		} elseif ( 'timepass' === $purchase_option_type ) {
			if ( $time_pass_instance->remove_time_pass_purchase_option( $purchase_option_id, $paywall_id ) ) {
				$msg = __( 'Time Pass removed!', 'revenue-generator' );
			} else {
				$msg = __( 'Unable to remove Time Pass, something went wrong!', 'revenue-generator' );
			}
		}

		// Send success message.
		wp_send_json(
			[
				'success' => true,
				'msg'     => $msg,
			]
		);

	}

	/**
	 * Update the global currency config.
	 */
	public function remove_paywall() {

		// Verify authenticity.
		check_ajax_referer( 'rg_paywall_nonce', 'security' );

		// Get all data and sanitize it.
		$paywall_id = sanitize_text_field( filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT ) );

		$paywall_instance = Paywall::get_instance();
		$removal_data     = $paywall_instance->remove_paywall( $paywall_id );
		$removal_status   = $removal_data['success'];
		$preview_id       = $removal_data['preview_post_id'];

		if ( true === $removal_status ) {
			// Send success message.
			wp_send_json(
				[
					'success'    => true,
					'preview_id' => $preview_id,
					'msg'        => __( 'Paywall removed!', 'revenue-generator' ),
				]
			);
		} else {
			// Send success message.
			wp_send_json(
				[
					'success' => false,
					'msg'     => __( 'Unable to remove Paywall, something went wrong!', 'revenue-generator' ),
				]
			);
		}

	}

	/**
	 * Search content for post preview selection.
	 */
	public function search_preview_content() {
		// Verify authenticity.
		check_ajax_referer( 'rg_paywall_nonce', 'security' );

		// Get all data and sanitize it.
		$search_term = sanitize_text_field( filter_input( INPUT_POST, 'search_term', FILTER_SANITIZE_STRING ) );

		$post_types_instance = Post_Types::get_instance();
		$preview_posts       = $post_types_instance->get_preview_content_selection( $search_term );

		if ( ! empty( $preview_posts ) ) {
			wp_send_json(
				[
					'success'       => true,
					'preview_posts' => $preview_posts,
				]
			);
		} else {
			wp_send_json(
				[
					'success'       => false,
					'msg'           => __( 'No matching content found!', 'revenue-generator' ),
					'preview_posts' => [],
				]
			);
		}
	}

	/**
	 * Select content for post preview.
	 */
	public function select_preview_content() {

		// Verify authenticity.
		check_ajax_referer( 'rg_paywall_nonce', 'security' );

		// Get all data and sanitize it.
		$post_preview_id = sanitize_text_field( filter_input( INPUT_POST, 'post_preview_id', FILTER_SANITIZE_NUMBER_INT ) );

		if ( ! empty( $post_preview_id ) ) {

			$admin_menus = self::get_admin_menus();

			$paywall_instance = Paywall::get_instance();
			$paywall_id       = $paywall_instance->get_connected_paywall_by_post( $post_preview_id );

			// Preview page URL.
			$preview_query_args = [
				'page' => $admin_menus['paywall']['url'],
			];

			// If a paywall exists for the selected content redirect merchant accordingly.
			if ( ! empty( $paywall_id ) ) {
				$preview_query_args['current_paywall'] = $paywall_id;
			} else {
				$preview_query_args['preview_post_id'] = $post_preview_id;
			}

			// Create redirect URL.
			$post_preview_page = add_query_arg(
				$preview_query_args,
				admin_url( 'admin.php' )
			);

			// Send success message.
			wp_send_json(
				[
					'success'     => true,
					'redirect_to' => $post_preview_page,
				]
			);

		}
	}

	/**
	 * Get categories to be added in a paywall.
	 */
	public function select_search_term() {
		// Verify authenticity.
		check_ajax_referer( 'rg_paywall_nonce', 'security' );

		// Get all data and sanitize it.
		$post_term = sanitize_text_field( filter_input( INPUT_POST, 'term', FILTER_SANITIZE_STRING ) );

		$args = [];
		if ( ! empty( $post_term ) ) {
			$args['name__like'] = $post_term;
		}

		$category_instance = Categories::get_instance();

		wp_send_json(
			[
				'success'    => true,
				'categories' => $category_instance->get_applicable_categories( $args ),
			]
		);
	}

	/**
	 * Search Post/page to be added in paywall
	 */
	public function select_search_post() {
		// Verify authenticity.
		check_ajax_referer( 'rg_paywall_nonce', 'security' );

		// Get all data and sanitize it.
		$post_term = sanitize_text_field( filter_input( INPUT_POST, 'term', FILTER_SANITIZE_STRING ) );

		$args = [
			'post_type'      => [
				'post',
				'page',
			],
			'posts_per_page' => 5,
			'orderby'        => 'relevance',
		];

		if ( ! empty( $post_term ) ) {
			$args['s'] = $post_term;
		}

		add_filter( 'posts_where', [ $this, 'rg_post_title_filter' ], 10, 2 );
		$posts = new \WP_Query( $args );
		remove_filter( 'posts_where', [ $this, 'rg_post_title_filter' ], 10, 2 );

		wp_send_json(
			[
				'success' => true,
				'posts'   => $posts->posts,
			]
		);
	}

	/**
	 * Filter to modify the search of post and search by title.
	 *
	 * @param string    $sql   SQL string.
	 * @param \WP_Query $query Query object.
	 *
	 * @return string
	 */
	public function rg_post_title_filter( $sql, $query ) {
		global $wpdb;

		// If our custom query var is set modify the query.
		if ( ! empty( $query->query['s'] ) ) {
			$term = $wpdb->esc_like( $query->query['s'] );
			$sql .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . $term . '%\'';
		}

		return $sql;
	}

	/**
	 * Clear category meta data on new selection.
	 */
	public function clear_category_meta() {
		// Verify authenticity.
		check_ajax_referer( 'rg_paywall_nonce', 'security' );

		// Get all data and sanitize it.
		$rg_category_ids   = sanitize_text_field( filter_input( INPUT_POST, 'rg_category_id', FILTER_DEFAULT ) );
		$category_instance = Categories::get_instance();

		$rg_cat_ids = json_decode( $rg_category_ids );
		$error      = array();

		foreach ( $rg_cat_ids as $rg_cat_id ) {
			$error[] = $category_instance->clear_category_paywall_meta( $rg_cat_id );
		}

		if ( ! empty( $error ) && ! in_array( false, $error, true ) ) {
			wp_send_json_success();
		} else {
			wp_send_json_error();
		}
	}

	/**
	 * Complete the welcome tour.
	 */
	public function complete_tour() {
		// Verify authenticity.
		check_ajax_referer( 'rg_paywall_nonce', 'security' );

		// Get all data and sanitize it.
		$config_key   = sanitize_text_field( filter_input( INPUT_POST, 'config_key', FILTER_SANITIZE_STRING ) );
		$config_value = sanitize_text_field( filter_input( INPUT_POST, 'config_value', FILTER_SANITIZE_STRING ) );

		$rg_global_options = Config::get_global_options();

		// Check if the option exists already.
		if ( ! isset( $rg_global_options[ $config_key ] ) ) {
			wp_send_json(
				[
					'success' => false,
					'msg'     => __( 'Invalid data passed!', 'revenue-generator' ),
				]
			);
		}

		// Check and verify updated option.
		if ( ! empty( $config_value ) ) {
			$rg_global_options[ $config_key ] = $config_value;
		}

		// Update the option value.
		update_option( 'lp_rg_global_options', $rg_global_options );

		// Send success message.
		wp_send_json(
			[
				'success' => true,
				'msg'     => __( 'Selection stored successfully!', 'revenue-generator' ),
			]
		);
	}

	/**
	 * Verify account credentials and do a test purchase.
	 */
	public function verify_account_credentials() {
		// Verify authenticity.
		check_ajax_referer( 'rg_paywall_nonce', 'security' );

		// Get all data and sanitize it.
		$merchant_id  = sanitize_text_field( filter_input( INPUT_POST, 'merchant_id', FILTER_SANITIZE_STRING ) );
		$merchant_key = sanitize_text_field( filter_input( INPUT_POST, 'merchant_key', FILTER_SANITIZE_STRING ) );

		$client_account_instance = Client_Account::get_instance();
		$rg_merchant_credentials = Client_Account::get_merchant_credentials();

		// Check and verify merchant id data.
		if ( ! empty( $merchant_id ) ) {
			$rg_merchant_credentials['merchant_id'] = $merchant_id;
		}

		// Check and verify merchant id data.
		if ( ! empty( $merchant_key ) ) {
			$rg_merchant_credentials['merchant_key'] = $merchant_key;
		}

		// Update the option value.
		update_option( 'lp_rg_merchant_credentials', $rg_merchant_credentials );

		$is_valid = $client_account_instance->validate_merchant_account( true );

		// Set merchant status to verified.
		if ( true === $is_valid ) {
			$rg_global_options                         = Config::get_global_options();
			$rg_global_options['is_merchant_verified'] = '1';
			update_option( 'lp_rg_global_options', $rg_global_options );

			$response = array(
				'success' => true,
				'msg'     => esc_html__( 'Saved valid crendetials!', 'revenue-generator' ),
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
	 * Get post permalink for requested post.
	 */
	public function get_post_permalink() {
		// Verify authenticity.
		check_ajax_referer( 'rg_paywall_nonce', 'security' );

		// Get all data and sanitize it.
		$preview_post_id    = sanitize_text_field( filter_input( INPUT_POST, 'preview_post_id', FILTER_SANITIZE_NUMBER_INT ) );
		$categories_id      = filter_input( INPUT_POST, 'category_id', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$applies_to         = sanitize_text_field( filter_input( INPUT_POST, 'applies_to', FILTER_SANITIZE_STRING ) );
		$specific_posts_ids = filter_input( INPUT_POST, 'specific_posts_ids', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		// Check if there is category id and current post has that category.
		if ( ! empty( $categories_id ) && is_array( $categories_id ) ) {

			// flag to check if post has category.
			$has_category = false;

			foreach ( $categories_id as $category_id ) {
				if ( has_category( $category_id, $preview_post_id ) ) {
					$has_category = true;
				}
			}

			// If Preview post has not category assigned.
			if ( ! $has_category ) {

				$category_post_args = array(
					'post_type'        => 'post',
					'numberposts'      => 1,
					'suppress_filters' => false,
				);

				// If it applies to category include them else exclude them.
				if ( 'category' === $applies_to ) {
					$category_post_args['category__in'] = $categories_id;
				} elseif ( 'exclude_category' === $applies_to ) {
					$category_post_args['category__not_in'] = $categories_id;
				}

				// If Preview post is post assigned to category, fetch post that has category.
				$category_post = get_posts( $category_post_args );

				// If category post exists assign for preview.
				if ( ! empty( $category_post ) ) {
					// Set preview post id.
					$preview_post_id = $category_post[0]->ID;
				}
			}
		}

		// Check if preview id exists in selected specific posts id.
		if ( ! empty( $specific_posts_ids ) && ! in_array( $preview_post_id, $specific_posts_ids, true ) ) {
			// If not in list use first post id from list for preview post.
			$preview_post_id = $specific_posts_ids[0];
		}

		// Check Preview post has post type post and it applies to only posts.
		if (
			! empty( $applies_to )
			&& 'posts' === $applies_to
			&& 'post' !== get_post_type( $preview_post_id )
		) {
			// if preview post is not post type "post" then get latest post.
			$latest_post = get_posts(
				array(
					'numberposts'      => 1,
					'suppress_filters' => false,
					'post_type'        => 'post',
					'orderby'          => 'ID',
					'order'            => 'DESC',
				)
			);

			// If we found a post then assing it to preview.
			if ( ! empty( $latest_post ) ) {
				$preview_post_id = $latest_post[0]->ID;
			}
		}

		// Check and verify data exits.
		if ( ! empty( $preview_post_id ) ) {
			wp_send_json(
				[
					'success'     => true,
					'redirect_to' => get_permalink( $preview_post_id ),
				]
			);
		}
	}

	/**
	 * Activate paywall.
	 */
	public function activate_paywall() {
		// Verify authenticity.
		check_ajax_referer( 'rg_paywall_nonce', 'security' );

		// Get all data and sanitize it.
		$paywall_id = sanitize_text_field( filter_input( INPUT_POST, 'paywall_id', FILTER_SANITIZE_NUMBER_INT ) );

		// Check and verify data exits.
		if ( ! empty( $paywall_id ) ) {
			$paywall_instance = Paywall::get_instance();
			$paywall_instance->activate_paywall( $paywall_id );

			wp_send_json(
				[
					'success'      => true,
					'has_paywalls' => $paywall_instance->get_paywalls_count() > 1,
				]
			);
		}
	}

	/**
	 * Disable paywall.
	 */
	public function disable_paywall() {
		// Verify authenticity.
		check_ajax_referer( 'rg_paywall_nonce', 'security' );

		// Get all data and sanitize it.
		$paywall_id = sanitize_text_field( filter_input( INPUT_POST, 'paywall_id', FILTER_SANITIZE_NUMBER_INT ) );

		// Check and verify data exits.
		if ( ! empty( $paywall_id ) ) {
			$paywall_instance = Paywall::get_instance();
			$result           = (bool) $paywall_instance->disable_paywall( $paywall_id );
			wp_send_json(
				[
					'success' => $result,
					'msg'     => $result ? __( 'Paywall Disabled!', 'revenue-generator' ) : __( 'Something went wrong!', 'revenue-generator' ),
				]
			);
		}
	}

	/**
	 * Restart the tour.
	 */
	public function restart_tour() {
		// Verify authenticity.
		check_ajax_referer( 'rg_paywall_nonce', 'security' );

		// Get all data and sanitize it.
		$should_restart = filter_input( INPUT_POST, 'restart_tour', FILTER_SANITIZE_NUMBER_INT );
		$tour_type      = filter_input( INPUT_POST, 'tour_type', FILTER_SANITIZE_STRING );
		$config_key     = ( ! empty( $tour_type ) ) ? $tour_type : 'paywall_tutorial_done';

		// Check and verify data exits.
		if ( 1 === absint( $should_restart ) ) {
			// Reset tutorial.
			$rg_global_options                = Config::get_global_options();
			$rg_global_options[ $config_key ] = 0;
			update_option( 'lp_rg_global_options', $rg_global_options );
			wp_send_json_success();
		}
	}

	/**
	 * Set paywall sort order.
	 */
	public function set_paywall_sort_order() {

		// Verify authenticity.
		check_ajax_referer( 'rg_paywall_nonce', 'security' );

		// Get all data and sanitize it.
		$rg_sort_order    = sanitize_text_field( filter_input( INPUT_POST, 'rg_sort_order', FILTER_SANITIZE_STRING ) );
		$rg_dashboard_url = sanitize_text_field( filter_input( INPUT_POST, 'rg_current_url', FILTER_SANITIZE_URL ) );

		if ( ! empty( $rg_sort_order ) ) {

			// Set sort by order.
			$preview_query_args['sort_by'] = $rg_sort_order;

			// Create redirect URL.
			$dashboard_sort_url = add_query_arg(
				$preview_query_args,
				$rg_dashboard_url
			);

			// Send success message.
			wp_send_json(
				[
					'success'     => true,
					'redirect_to' => $dashboard_sort_url,
				]
			);
		}
	}

	/**
	 * Search content for post preview selection.
	 */
	public function search_paywall() {
		// Verify authenticity.
		check_ajax_referer( 'rg_paywall_nonce', 'security' );

		// Get all data and sanitize it.
		$search_term      = sanitize_text_field( filter_input( INPUT_POST, 'search_term', FILTER_SANITIZE_STRING ) );
		$rg_dashboard_url = sanitize_text_field( filter_input( INPUT_POST, 'rg_current_url', FILTER_SANITIZE_URL ) );

		if ( ! empty( $search_term ) ) {

			// Set search term.
			$preview_query_args['search_term'] = $search_term;

			// Create redirect URL.
			$dashboard_url = add_query_arg(
				$preview_query_args,
				$rg_dashboard_url
			);

			// Send success message.
			wp_send_json(
				[
					'success'     => true,
					'redirect_to' => $dashboard_url,
				]
			);
		}
	}

	/**
	 * Handle Paywall Name change triggered from Paywall Dashboard.
	 */
	public function rg_set_paywall_name() {
		// Verify authenticity.
		check_ajax_referer( 'rg_paywall_nonce', 'security' );
		$new_name       = filter_input( INPUT_POST, 'new_paywall_name', FILTER_SANITIZE_STRING );
		$paywall_id     = filter_input( INPUT_POST, 'paywall_id', FILTER_SANITIZE_NUMBER_INT );
		$return_post_id = 0;

		if ( ! empty( $paywall_id ) ) {
			$return_post_id = wp_update_post(
				[
					'ID'         => $paywall_id,
					'post_title' => $new_name,
				]
			);
		}

		$is_updated = ( ! empty( $return_post_id ) || ! is_wp_error( $return_post_id ) );
		$response   = [
			'success' => $is_updated,
			'msg'     => $is_updated ? esc_html__( 'Paywall title updated.', 'revenue-generator' ) : esc_html__( 'Failed to update paywall title.', 'revenue-generator' ),
		];

		wp_send_json( $response );

	}

	/**
	 * Filter to modify the search of preview content.
	 *
	 * @param string    $sql   SQL string.
	 * @param \WP_Query $query Query object.
	 *
	 * @return string
	 */
	public function rg_paywall_preview_name_filter( $sql, $query ) {
		global $wpdb;

		// If our custom query var is set modify the query.
		if ( ! empty( $query->query['rg_preview_title'] ) ) {
			$term = $wpdb->esc_like( $query->query['rg_preview_title'] );
			$sql  .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . $term . '%\'';
		}

		return $sql;
	}
}

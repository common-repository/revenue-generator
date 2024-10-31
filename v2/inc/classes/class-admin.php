<?php
/**
 * Revenue Generator Plugin Admin Class.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc;

use \LaterPay\Revenue_Generator\Inc\Plugin;
use \LaterPay\Revenue_Generator\Inc\Config;
use \LaterPay\Revenue_Generator\Inc\Post_Types\Contribution;
use \LaterPay\Revenue_Generator\Inc\Settings;
use \LaterPay\Revenue_Generator\Inc\Traits\Singleton;
use \LaterPay\Revenue_Generator\Inc\Api\Auth;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin
 */
class Admin {

	use Singleton;

	/**
	 * Base path for REST API routes related to admin.
	 *
	 * @const string
	 */
	const REST_BASE_PATH = 'app';

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
		add_action( 'admin_menu', [ $this, 'register_pages' ] );
		add_action( 'wp_ajax_rgv2_update_global_config', [ $this, 'update_global_config' ] );
		add_action( 'wp_ajax_rgv2_update_settings', [ $this, 'update_settings' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Handles Ajax Request for settings.
	 */
	public function update_settings() {

		// Verify authenticity.
		check_ajax_referer( 'rg_global_config_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Cheating huh?', 'revenue-generator' ) );
		}

		$client_id                        = filter_input( INPUT_POST, 'client_id', FILTER_SANITIZE_STRING );
		$client_secret                    = filter_input( INPUT_POST, 'client_secret', FILTER_SANITIZE_STRING );
		$personal_ga_ua_id                = filter_input( INPUT_POST, 'personal_ga_ua_id', FILTER_SANITIZE_STRING );
		$ga_personal_enabled_status       = filter_input( INPUT_POST, 'ga_personal_enabled_status', FILTER_SANITIZE_NUMBER_INT );
		$ga_enabled_status                = filter_input( INPUT_POST, 'ga_enabled_status', FILTER_SANITIZE_NUMBER_INT );

		$global_options = Config::get_global_options();

		$response = array();

		if ( ! empty( $personal_ga_ua_id ) ) {
			Settings::update_settings_options( 'personal_ga_ua_id', $personal_ga_ua_id );
		}

		if ( isset( $ga_personal_enabled_status ) ) {
			Settings::update_settings_options( 'ga_personal_enabled_status', $ga_personal_enabled_status );
		}

		if ( isset( $ga_enabled_status ) ) {
			Settings::update_settings_options( 'ga_enabled_status', $ga_enabled_status );
		}

		// Verify Merchant Credentials.
		if ( ! empty( $client_id ) && ! empty( $client_secret ) ) {
			$auth                 = Api\Auth::get_instance();
			$save_credentials     = $auth->save_credentials(
				[
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
				]
			);

			$response['merchant'] = $save_credentials;

			// Set merchant status to verified.
			if ( true === $save_credentials ) {
				$rgv2_global_options                         = Config::get_global_options();
				$rgv2_global_options['is_merchant_verified'] = '1';
				update_option( 'lp_rgv2_global_options', $rgv2_global_options );
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

		$api = Api\Auth::get_instance();

		$lp_config_id         = Settings::get_tracking_id();
		$lp_user_tracking_id  = Settings::get_tracking_id( 'user' );
		$merchant_credentials = $api->get_credentials();

		$admin_menus  = self::get_admin_menus();

		// Script date required for operations.
		$rgv2_script_data = [
			'globalOptions'            => $current_global_options,
			'ajaxUrl'                  => admin_url( 'admin-ajax.php' ),
			'rg_settings_nonce'        => wp_create_nonce( 'rg_settings_nonce' ),
			'rg_contribution_nonce'    => wp_create_nonce( 'rg_contribution_nonce' ),
			'rg_tracking_id'           => ( ! empty( $lp_config_id ) ) ? esc_html( $lp_config_id ) : '',
			'rg_user_tracking_id'      => ( ! empty( $lp_user_tracking_id ) ) ? esc_html( $lp_user_tracking_id ) : '',
		];

		$rgv2_script_data['rg_global_config_nonce'] = wp_create_nonce( 'rg_global_config_nonce' );

		// Add Merchant ID for backend.
		if ( ! empty( $merchant_credentials['client_id'] ) && is_admin() ) {
			$rgv2_script_data['client_id'] = $merchant_credentials['client_id'];
		}

		// Localize script to store `$rgv2_script_data` values in `rgOptions` global JS var.
		wp_localize_script(
			'revenue-generator-v2',
			'rgOptions',
			$rgv2_script_data
		);

		wp_enqueue_script( 'revenue-generator-v2' );
		wp_enqueue_style( 'revenue-generator' );
		wp_enqueue_style( 'revenue-generator-select2' );
	}

	/**
	 * Load scripts and styles for React app.
	 */
	public static function load_react_assets() {
		if ( 'development' === wp_get_environment_type() ) {
			return self::load_react_assets_dev();
		}

		$react_app_build_url = REVENUE_GENERATOR_PLUGIN_URL . '/v2/builder-react-app/build';
		$manifest_url        = $react_app_build_url . '/asset-manifest.json';

		$response = wp_remote_get( $manifest_url );

		if ( is_wp_error( $response ) ) {
			return;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( empty( $data ) ) {
			return;
		}

		if ( ! property_exists( $data, 'entrypoints' ) ) {
			return;
		}

		$assets = $data->entrypoints;

		$files  = [
			'js'  => [],
			'css' => [],
		];

		$files['js'] = array_filter(
			$assets,
			function( $file ) {
				return 'js' === pathinfo( $file, PATHINFO_EXTENSION );
			}
		);

		$files['css'] = array_filter(
			$assets,
			function( $file ) {
				return 'css' === pathinfo( $file, PATHINFO_EXTENSION );
			}
		);

		foreach ( $files['js'] as $key => $file ) {
			wp_enqueue_script(
				'revenue-generator-builder-react-js-' . $key,
				$react_app_build_url . '/' . $file,
				[],
				REVENUE_GENERATOR_VERSION,
				true
			);
		}

		foreach ( $files['css'] as $key => $file ) {
			wp_enqueue_style(
				'revenue-generator-builder-react-css-' . $key,
				$react_app_build_url . '/' . $file,
				[],
				REVENUE_GENERATOR_VERSION
			);
		}
	}

	/**
	 * Load React app assets in development environment.
	 */
	public static function load_react_assets_dev() {
		$app_build_url     = 'http://localhost:3000';
		$app_relative_path = 'wp-content/plugins/revenue-generator/v2/builder-react-app';

		$files  = [
			'js'  => [
				'main.js'    => "{$app_relative_path}/build/static/js/main.chunk.js",
				'bundle.js'  => "{$app_relative_path}/build/static/js/bundle.js",
				'vendors.js' => "{$app_relative_path}/build/static/js/vendors~main.chunk.js",
			],
			'css' => [
				'main.css' => "{$app_relative_path}/build/static/css/main.css",
			],
		];

		foreach ( $files['js'] as $key => $file ) {
			wp_enqueue_script(
				'revenue-generator-builder-react-js-' . $key,
				$app_build_url . '/' . $file,
				[ 'wp-i18n' ],
				REVENUE_GENERATOR_VERSION,
				true
			);

			wp_set_script_translations( 'revenue-generator-builder-react-js-' . $key, 'revenue-generator' );
		}

		foreach ( $files['css'] as $key => $file ) {
			wp_enqueue_style(
				'revenue-generator-builder-react-css-' . $key,
				$app_build_url . '/' . $file,
				[],
				REVENUE_GENERATOR_VERSION
			);
		}
	}

	/**
	 * Register a new menu page for the Dashboard.
	 */
	public function register_pages() {
		$current_global_options = Config::get_global_options();

		// Check if setup is done, and load page accordingly.
		$is_welcome_setup_done = ( ! empty( $current_global_options['is_welcome_done'] ) ) ? $current_global_options['is_welcome_done'] : false;

		$main = \LaterPay\Revenue_Generator\Main::get_instance();
		$onboarding_instance = \LaterPay\Revenue_Generator\Onboarding\Main::get_instance();

		$dashboard_callback = [ $this, 'load_react_app' ];

		if ( $main->is_onboarding_required() && ! $is_welcome_setup_done ) {
			$dashboard_callback = [ $onboarding_instance, 'welcome_screen_callback' ];
		}

		// Add main menu page.
		add_menu_page(
			'Revenue Generator',
			'Revenue Generator',
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
				$page_callback = 'load_' . $page_data['method'];

				add_submenu_page(
					'revenue-generator',
					$page_data['title'] . ' | ' . __( 'Revenue Generator Settings', 'revenue-generator' ),
					$page_data['title'],
					$page_data['cap'],
					$slug,
					[ $this, $page_callback ]
				);
			}
		}
	}

	/**
	 * Create Contribution.
	 */
	public function load_react_app() {
		self::load_react_assets();

		$config_data = Config::get_global_options();
		$admin_menus = self::get_admin_menus();
		$contributions_dashboard_url = '';

		if ( isset( $admin_menus['contributions'] ) ) {
			$contributions_dashboard_url = add_query_arg(
				[
					'page' => $admin_menus['contributions']['url'],
				],
				admin_url( 'admin.php' )
			);
		}

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
		echo View::render_template( 'backend/common/react-app' );

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

		$auth = Api\Auth::get_instance();

		$merchant_credentials = $auth->get_credentials();
		$global_options       = Config::get_global_options();
		$settings_options     = Settings::get_settings_options();

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
			'merchant_credentials' => $merchant_credentials,
			'global_options'       => $global_options,
			'settings_options'     => $settings_options,
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
	 * Update the global config with provided data.
	 *
	 * @codeCoverageIgnore -- @todo add AJAX test base class to cover this.
	 */
	public function update_global_config() {

		// Verify authenticity.
		check_ajax_referer( 'rg_global_config_nonce', 'security' );

		// Get all data and sanitize it.
		$config_key              = sanitize_text_field( filter_input( INPUT_POST, 'config_key', FILTER_SANITIZE_STRING ) );
		$config_value            = sanitize_text_field( filter_input( INPUT_POST, 'config_value', FILTER_SANITIZE_STRING ) );
		$ga_enabled_status       = filter_input( INPUT_POST, 'ga_enabled_status', FILTER_SANITIZE_NUMBER_INT );

		$rgv2_global_options  = Config::get_global_options();
		$rgv2_global_settings = Settings::get_settings_options();

		// Update Tracking Settings.
		if ( isset( $rgv2_global_settings['ga_enabled_status'] ) && isset( $ga_enabled_status ) ) {
			Settings::update_settings_options( 'ga_enabled_status', $ga_enabled_status );
		}

		// Check if the option exists already.
		if ( ! isset( $rgv2_global_options[ $config_key ] ) ) {
			wp_send_json(
				[
					'success' => false,
					'msg'     => __( 'Invalid data passed!', 'revenue-generator' ),
				]
			);
		}

		// Check and verify updated option.
		if ( ! empty( $config_value ) ) {
			$rgv2_global_options[ $config_key ] = $config_value;
		}

		// Update the option value.
		update_option( 'lp_rgv2_global_options', $rgv2_global_options );

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
			$menus['contributions'] = [
				'url'    => 'revenue-generator',
				'title'  => __( 'Contributions', 'revenue-generator' ),
				'cap'    => 'manage_options',
				'method' => 'react_app',
			];

			$menus['contribution'] = [
				'url'    => Contribution::ADMIN_EDIT_SLUG,
				'title'  => __( 'Contribution', 'revenue-generator' ),
				'cap'    => 'manage_options',
				'method' => 'react_app',
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
	 * Register REST API routes.
	 *
	 * @hooked action `rest_api_init`
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			REVENUE_GENERATOR_REST_NAMESPACE,
			static::REST_BASE_PATH,
			[
				[
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => [ $this, 'rest_get_app_state' ],
					'permission_callback' => [ $this, 'rest_check_permission' ],
				],
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'rest_set_app_state' ],
					'permission_callback' => [ $this, 'rest_check_permission' ],
				],
			]
		);
	}

	/**
	 * REST API endpoint for admin React app to get initial state.
	 *
	 * @return WP_REST_Response
	 */
	public function rest_get_app_state() {
		$contribution_instance = Contribution::get_instance();
		$config_instance       = Config::get_instance();
		$footer_contribution   = $contribution_instance::get_footer_contribution_id();
		$auth_instance         = Auth::get_instance();

		$data = [
			'nonce'                 => wp_create_nonce( 'wp_rest' ),
			'siteUrl'               => site_url(),
			'isWelcomeDone'         => ! empty( $config_instance->get_option( 'is_welcome_done' ) ),
			'hasContributions'      => $contribution_instance->has_contributions(),
			'footerContribution'    => $footer_contribution,
			'trackingId'            => [
				'user'   => Settings::get_tracking_id( 'user' ),
				'global' => Settings::get_tracking_id(),
			],
			'clientId'              => $auth_instance->get_client_id(),
		];

		return rest_ensure_response( $data );
	}

	/**
	 * REST API endpoint handler for storing app state.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_set_app_state( $request ) {
		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
			return;
		}

		$body = json_decode( $request->get_body(), true );

		if ( empty( $body ) ) {
			return new \WP_Error(
				'rg_rest_missing_body',
				__( 'The request is missing a body which is required.', 'revenue-generator' ),
				[
					'status' => 400,
				]
			);
		}

		$available_options = [
			'is_welcome_done'   => 'config',
			'ga_enabled_status' => 'settings',
		];

		foreach ( $body as $option_key => $option_value ) {
			if ( ! in_array( $option_key, array_keys( $available_options ) ) ) {
				continue;
			}

			$target = $available_options[ $option_key ];

			switch ( $target ) {
				case 'config':
					Config::update_global_options( $option_key, $option_value );
					break;

				case 'settings':
					Settings::update_settings_options( $option_key, $option_value );
					break;

				default:
					break;
			}
		}

		$data = [
			'success' => true,
		];

		return rest_ensure_response( $data );
	}

	/**
	 * Checks if request is valid and user has permission in REST context.
	 *
	 * @param WP_REST_Request $request REST request instance.
	 *
	 * @return boolean|WP_Error
	 */
	public function rest_check_permission( $request ) {
		$referer = $request->get_header( 'referer' );

		if ( empty( $referer ) || false === strpos( $referer, admin_url( '' ) ) ) {
			return new \WP_Error(
				'rg_rest_forbidden_context',
				__( 'Sorry, you are not authorized for this endpoint.', 'revenue-generator' ),
				[
					'status' => rest_authorization_required_code(),
				]
			);
		}

		return true;
	}

}

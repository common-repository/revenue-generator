<?php
/**
 * Revenue Generator Plugin Fronted Post Class.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc;

use \LaterPay\Revenue_Generator\Inc\Post_Types\Subscription;
use \LaterPay\Revenue_Generator\Inc\Post_Types\Time_Pass;
use \LaterPay\Revenue_Generator\Inc\Traits\Singleton;
use \LaterPay\Revenue_Generator\Inc\Post_Types\Contribution;
use \LaterPay\Revenue_Generator\Inc\Shortcodes;
use \LaterPay\Revenue_Generator\Inc\API;

defined( 'ABSPATH' ) || exit;

/**
 * Class Frontend_Post
 */
class Frontend_Post {

	use Singleton;

	/**
	 * Name of the action to handle contribution through ajax.
	 *
	 * @const string
	 */
	const CONTRIBUTION_AJAX_ACTION = 'rgv2_contribution_contribute';

	/**
	 * URL of tab widget script.
	 *
	 * @const string
	 */
	const TAB_WIDGET_SCRIPT_URL = 'https://assets.laterpay.net/pcpro-jsx-components-frontend/main.js';

	/**
	 * URL of tab widget script (SBX).
	 *
	 * @const string
	 */
	const TAB_WIDGET_SCRIPT_SBX_URL = 'https://assets.sbx.laterpay.net/pcpro-jsx-components-frontend/main.js';

	/**
	 * URL of payment component script.
	 *
	 * @const string
	 */
	const PAYMENT_COMPONENT_SCRIPT_URL = 'https://payment-component.laterpay.net/tab-widget';

	/**
	 * URL of payment component script (SBX).
	 *
	 * @const string
	 */
	const PAYMENT_COMPONENT_SCRIPT_SBX_URL = 'https://payment-component.sbx.laterpay.net/tab-widget';

	/**
	 * MyTab URL.
	 *
	 * @const string
	 */
	const MYTAB_URL = 'https://mytab.laterpay.net';

	/**
	 * MyTab URL (SBX).
	 *
	 * @const string
	 */
	const MYTAB_SBX_URL = 'https://mytab.sbx.laterpay.net';

	/**
	 * Current content post ID.
	 *
	 * @var int
	 */
	protected $current_post_id;

	/**
	 * Individual article purchase option tile.
	 *
	 * @var string
	 */
	protected $individual_article_title;

	/**
	 * Individual article purchase option description.
	 *
	 * @var string
	 */
	protected $individual_article_description;

	/**
	 * Merchant region.
	 *
	 * @var string
	 */
	protected $merchant_region;

	/**
	 * Merchant currency.
	 *
	 * @var string
	 */
	protected $merchant_currency;

	/**
	 * Class Frontend_Post construct method.
	 */
	protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Setup options.
	 */
	protected function setup_hooks() {
		add_action( 'wp_ajax_' . static::CONTRIBUTION_AJAX_ACTION, [ $this, 'ajax_contribute_contribution' ] );
		add_action( 'wp_ajax_nopriv_' . static::CONTRIBUTION_AJAX_ACTION, [ $this, 'ajax_contribute_contribution' ] );
		add_action( 'parse_request', [ $this, 'handle_payment_iframe_redirect' ] );
		add_action( 'parse_request', [ $this, 'handle_purchase_iframe_display' ] );
		add_action( 'parse_request', [ $this, 'handle_json_amp_url' ] );
		add_action( 'wp_print_footer_scripts', [ $this, 'print_amp_script' ] );
		add_action( 'wp_head', [ $this, 'add_amp_script_hash' ] );
		add_action( 'wp_footer', [ $this, 'maybe_add_footer_contribution' ] );
	}

	/**
	 * Handle custom contribution form submit.
	 *
	 * @hooked action `wp_ajax_rgv2_contribution_contribute`
	 * @hooked action `wp_ajax_nopriv_rgv2_contribution_contribute`
	 *
	 * @return void
	 */
	public function ajax_contribute_contribution() {
		check_ajax_referer( static::CONTRIBUTION_AJAX_ACTION, 'nonce' );

		$amount        = ( isset( $_REQUEST['amount'] ) ) ? (float) $_REQUEST['amount'] : 0;
		$custom_amount = ( isset( $_REQUEST['custom_amount'] ) ) ? (float) $_REQUEST['custom_amount'] : 0;
		$item_id       = ( isset( $_REQUEST['item_id'] ) ) ? (int) $_REQUEST['item_id'] : 0;
		$is_amp        = ( isset( $_REQUEST['is_amp'] ) && 1 === (int) $_REQUEST['is_amp'] );
		$layout_type   = ( isset( $_REQUEST['layout_type'] ) ) ? sanitize_text_field( $_REQUEST['layout_type'] ) : '';
		$key           = ( isset( $_REQUEST['rg_key'] ) ) ? sanitize_text_field( $_REQUEST['rg_key'] ) : '';
		$redirect_uri  = ( isset( $_REQUEST['redirect_uri'] ) ) ? esc_url( $_REQUEST['redirect_uri'] ) : ''; // phpcs:ignore -- WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// If amount is empty, there's nothing to contribute so return early.
		if ( empty( $amount ) && empty( $custom_amount ) ) {
			wp_send_json_error(
				__( 'Contribution amount cannot be empty.', 'revenue-generator' )
			);
		}

		// Return early if no redirect uri or item ID is present.
		if ( empty( $item_id ) || empty( $redirect_uri ) ) {
			wp_send_json_error(
				__( 'Contribution details cannot be empty.', 'revenue-generator' )
			);
		}

		// If custom amount was provided, use that for further processing.
		if ( ! empty( $custom_amount ) ) {
			$amount = $custom_amount;
		}

		$api = API::get_instance();

		// If session key is present, set it to API instance.
		if ( ! empty( $key ) ) {
			$api->set_session( $key );
		}

		/**
		 * If user is not authorized, return auth flow URL for them in the response
		 * and set cookie so they can continue where they left off on their return.
		 */
		if ( ! $api->is_user_authorized() ) {
			// Reset session if in AMP context as we want to pair data to AMP client ID instead.
			if ( $is_amp ) {
				$api->set_session( '' );

				$amp_id_key = '';

				if ( $is_amp ) {
					$amp_id_key = $key;
				}
			}

			$auth_url = $api::$auth->get_auth_code_url();
			$state    = $api::$auth->get_state_from_auth_url( $auth_url );

			$api->set_session( $state );

			// Set specific AMP redirect URI to redirect back to AMP context.
			if ( $is_amp ) {
				$request_headers = getallheaders();
				$redirect_uri    = ( ! empty( $request_headers['referer'] ) ) ? $request_headers['referer'] : $redirect_uri;
			}

			$client_data = [
				'redirect_uri' => $redirect_uri,
			];

			// Add reference to AMP client ID to client meta for future linking.
			if ( $is_amp ) {
				$client_data['amp_id_key'] = $amp_id_key;
			}

			$api->set_client_meta( $client_data );

			if ( $is_amp ) {
				header( 'AMP-Redirect-To: ' . $auth_url );
				header( 'Access-Control-Expose-Headers: AMP-Redirect-To' );
			}

			// Send JSON response with auth code flow URL and 401 code.
			wp_send_json(
				[
					'data' => [
						'auth_url'    => $auth_url,
						'session_key' => $api->session_key,
						'handover'    => [
							'contribution' => [
								'amount'  => number_format( $amount, 2 ),
								'item_id' => $item_id,
							],
						],
					],
				],
				401
			);
		}

		// Get contribution post type instance and contribution details.
		$contributions   = Contribution::get_instance();
		$contribution    = $contributions->get( $item_id );

		// Multiply amount by 100 to get amount in cents.
		$amount_in_cents = $amount * 100;

		// Attempt to try item to user's tab.
		$add_to_tab = $api->add_to_tab(
			$item_id,
			$amount_in_cents,
			$contribution['post_title']
		);

		// If response wasn't what we expected, return error.
		if ( empty( $add_to_tab ) || ! isset( $add_to_tab['code'] ) || ! isset( $add_to_tab['body'] ) ) {
			wp_send_json_error();
		}

		$code     = $add_to_tab['code'];
		$body     = $add_to_tab['body'];
		$response = [];

		if ( 201 === $code || 402 === $code ) {
			$amounts = [
				'total' => (int) $body->tab->total / 100,
				'limit' => (int) $body->tab->limit / 100,
			];

			$tab_data = [
				'id'    => $body->tab->id,
				'total' => $amounts['total'],
				'limit' => $amounts['limit'],
			];

			if ( $is_amp ) {
				$api->set_client_meta( $tab_data, 'amp_payment' );
			}

			$response = [
				'data' => [
					'tab' => $tab_data,
				],
			];
		}

		// Decide on the response based on the response code from Tapper.
		switch ( $code ) {
			/**
			 * In case adding to the tab was successful, return tab info
			 * that we'll then pass to component library to display it in UI.
			 */
			case 201:
				$response['data']['payment_required'] = false;
				$response['data']['html']             = sprintf(
					'<div class="lp__root"><div class="lp__tab-widget-data" data-tab-amount="%s" data-tab-currency="USD" data-tab-limit="%s" data-view-tab-url="%s" data-sign-in-url="%s"></div></div>',
					$amounts['total'],
					$amounts['limit'],
					static::get_mytab_url(),
					''
				);

				wp_send_json(
					$response,
					200
				);

				break;

			/**
			 * If tab needs to be settled, return different response along with
			 * the client app and client secret so component library can initialize
			 * payment flow.
			 */
			case 402:
				$iframe_url = static::get_payment_iframe_url();
				$iframe_url = add_query_arg(
					[
						'key' => $key,
						'v'   => time(),
					],
					$iframe_url
				);

				$meta = [
					'tab_id'      => $tab_data['id'],
					'layout_type' => $layout_type,
				];

				// Store full tab ID and layout type in payment meta.
				$api->set_client_meta( $meta, 'payment' );

				$response['data']['payment_required'] = true;
				$response['data']['html']             = sprintf(
					'<iframe src="%s" data-contribution-id="%s" onload="laterpayIframeLoaded( this )" height="%s" scrolling="no" sandbox="allow-scripts allow-forms allow-same-origin"></iframe>',
					$iframe_url,
					$item_id,
					'100%'
				);

				wp_send_json(
					$response,
					402
				);

				break;

			default:
				break;
		}
	}

	/**
	 * Get URL of the tab widget script.
	 *
	 * @return string
	 */
	public static function get_tab_widget_script_url() {
		if ( defined( 'REVENUE_GENERATOR_SANDBOX_MODE' ) && REVENUE_GENERATOR_SANDBOX_MODE ) {
			return static::TAB_WIDGET_SCRIPT_SBX_URL;
		}

		return static::TAB_WIDGET_SCRIPT_URL;
	}

	/**
	 * Get URL of the payment component.
	 *
	 * @return string
	 */
	public static function get_payment_component_url() {
		if ( defined( 'REVENUE_GENERATOR_SANDBOX_MODE' ) && REVENUE_GENERATOR_SANDBOX_MODE ) {
			return static::PAYMENT_COMPONENT_SCRIPT_SBX_URL;
		}

		return static::PAYMENT_COMPONENT_SCRIPT_URL;
	}

	/**
	 * Get MyTab dashboard URL.
	 *
	 * @return string
	 */
	public static function get_mytab_url() {
		if ( defined( 'REVENUE_GENERATOR_SANDBOX_MODE' ) && REVENUE_GENERATOR_SANDBOX_MODE ) {
			return static::MYTAB_SBX_URL;
		}

		return static::MYTAB_URL;
	}

	/**
	 * Get iframe URL for payment component display.
	 *
	 * @return string
	 */
	public static function get_payment_iframe_url() {
		$url = site_url();
		$url = add_query_arg( 'laterpay', 'payment', $url );

		return $url;
	}

	/**
	 * Intercept request. When `?laterpay` is present and set to `payment`,
	 * make a call to payment component and render it before WP theme initializes.
	 *
	 * @return void
	 */
	public function handle_payment_iframe_redirect() {
		if ( ! isset( $_GET['laterpay'] ) ) {
			return;
		}

		if ( 'payment' !== sanitize_text_field( $_GET['laterpay'] ) ) {
			return;
		}

		if ( ! isset( $_GET['key'] ) ) {
			return;
		}

		$key = sanitize_text_field( $_GET['key'] );

		if ( empty( $key ) ) {
			return;
		}

		$api = API::get_instance();
		$api->set_session( $key );

		// Get full tab ID and layout type previously set to client meta.
		$payment_data = $api->get_client_meta( 'payment' );

		$req = wp_remote_get(
			static::get_payment_component_url(),
			[
				'headers' => [
					'x-lp-access-token'  => $api::$auth->get_access_token(),
					'x-lp-tab-id'        => $payment_data['tab_id'],
					'x-lp-amp'           => ( isset( $_GET['amp'] ) ),
					'x-lp-theme'         => $payment_data['layout_type'],
				],
			]
		);

		$res = wp_remote_retrieve_body( $req );

		header( 'Access-Control-Allow-Origin: ' . site_url() );
		header( 'Content-Type: text/html; charset=UTF-8' );

		echo $res; // phpcs:ignore -- WordPress.Security.EscapeOutput.OutputNotEscaped

		// Clean up. Delete client meta and payment meta.
		$api->delete_client_meta();
		$api->delete_client_meta( 'payment' );

		exit();
	}

	/**
	 * Renders purchase iframe template for AMP.
	 *
	 * @return void
	 */
	public function handle_purchase_iframe_display() {
		if ( ! isset( $_GET['laterpay'] ) ) {
			return;
		}

		if ( 'purchase' !== sanitize_text_field( $_GET['laterpay'] ) ) {
			return;
		}

		if ( ! isset( $_GET['key'] ) ) {
			return;
		}

		$key = sanitize_text_field( $_GET['key'] );

		$api = API::get_instance();
		$api->set_session( $key );

		$amp_meta = $api->get_client_meta( 'amp_payment' );

		$api      = API::get_instance();
		$html     = '';
		$tab_data = [
			'id'    => '',
			'total' => '',
			'limit' => '',
		];

		$tab_data = wp_parse_args( $amp_meta, $tab_data );

		// Data to use in the template.
		$data = [
			'tab'       => $tab_data,
			'mytab_url' => static::get_mytab_url(),
		];

		ob_start();
			include REVENUE_GENERATOR_PLUGIN_DIR . '/v2/templates/frontend/contribution/amp-purchase.php';
		$html = ob_get_clean();

		echo $html; // phpcs:ignore -- WordPress.Security.EscapeOutput.OutputNotEscaped (output escaped in the template)

		exit();
	}

	/**
	 * Outputs simple URL where we'll listen to and intercept the request
	 * to render a JSON.
	 *
	 * @param string $type Type of the JSON to echo.
	 *
	 * @return string
	 */
	public static function get_json_amp_url( $type = '' ) {
		if ( empty( $type ) ) {
			return;
		}

		$url = add_query_arg(
			[
				'laterpay' => 'json',
				'type'     => $type,
			],
			site_url()
		);

		return $url;
	}

	/**
	 * Intercept the request and output JSON with the URL to either purchase
	 * or payment component for AMP purposes.
	 *
	 * AMP implementation uses `<amp-list>` to grab values of this JSON and
	 * then dynamically renders `<iframe>` with `src` coming from this JSON.
	 * This allows us to render iframe with the URL queries we need and
	 * overcome the limitation of `<amp-iframe>` alone which does not allow
	 * for dynamic attributes by default.
	 *
	 * @return string
	 */
	public function handle_json_amp_url() {
		if ( ! isset( $_GET['laterpay'] ) ) {
			return;
		}

		if ( 'json' !== sanitize_text_field( $_GET['laterpay'] ) ) {
			return;
		}

		if ( ! isset( $_GET['key'] ) ) {
			return;
		}

		header( 'Content-type: application/json' );

		$key = sanitize_text_field( $_GET['key'] );
		$api = API::get_instance();
		$api->set_session( $key );

		$payload = $api->encrypt_amp_payment_payload();

		$url = static::get_payment_component_url();
		$url = add_query_arg(
			[
				'tabData' => urlencode( $payload ),
				'amp'     => 1,
			],
			$url
		);

		$json = json_encode(
			[
				'items' => [
					[
						'url' => $url,
					],
				],
			]
		);

		echo $json; // phpcs:ignore -- WordPress.Security.EscapeOutput.OutputNotEscaped -- output is formed in this method so no need to further escape

		exit();
	}

	/**
	 * Print AMP purchase component script if in AMP context.
	 *
	 * @hooked action `wp_print_footer_scripts`
	 */
	public function print_amp_script() {
		if ( ! function_exists( 'is_amp_endpoint' ) || ! is_amp_endpoint() ) {
			return;
		}

		include REVENUE_GENERATOR_PLUGIN_DIR . '/v2/templates/frontend/contribution/amp-purchase-component.php';
	}

	/**
	 * Print AMP purchase component script hash to wp_head if
	 * in AMP context.
	 *
	 * @hooked action `wp_head`
	 */
	public function add_amp_script_hash() {
		if ( ! function_exists( 'is_amp_endpoint' ) || ! is_amp_endpoint() ) {
			return;
		}
		?>
		<meta name="amp-script-src" content="sha384-Xcsn0uFdGgT5Rk2GgmeML-rKQU6kfiRtPfrouuTo0sjizpodOzkcwzV8uBn2zyo9">
		<?php
	}

	/**
	 * Get floating footer contribution and print it in footer markup in case
	 * it's found.
	 *
	 * @hooked action `wp_footer`
	 *
	 * @return void
	 */
	public function maybe_add_footer_contribution() {
		$floating_footer_contribution_id = Contribution::get_footer_contribution_id();

		if ( empty( $floating_footer_contribution_id ) ) {
			return;
		}

		$shortcode = sprintf(
			'[%s id="%d"]',
			Shortcodes::SHORTCODE_NAMESPACE,
			$floating_footer_contribution_id
		);

		if ( ! apply_filters( 'rg_footer_contribution_visible', true ) ) {
			return;
		}

		echo do_shortcode( $shortcode );
	}

}

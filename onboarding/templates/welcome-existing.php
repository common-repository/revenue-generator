<?php
/**
 * Revenue Generator admin welcome screen for Revenue Generator merchants.
 *
 * @package revenue-generator
 */

use \LaterPay\Revenue_Generator\Inc\View;
use \LaterPay\Revenue_Generator\Onboarding\Main as Onboarding_Main;

if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file.
	exit;
}
?>

<div class="rev-gen-layout-wrapper">
	<section class="rev-gen-welcome rgv2-welcome">
		<section class="rev-gen-welcome__left">
			<h1 class="rev-gen-welcome__title"><?php esc_html_e( 'We have an exciting announcement!', 'revenue-generator' ); ?></h1>

			<p><?php esc_html_e( 'Laterpay has built an all new API-first platform and we are excited to share it with you!', 'revenue-generator' ); ?> <?php esc_html_e( 'Not only does this allow us to provide you with additional flexibility and configuration options over the coming months, but your users will now have an amazing, 100% on-page purchase experience.', 'revenue-generator' ); ?> <a href="https://theleek.demo.laterpay.net/contribute/" target="_blank"><?php esc_html_e( 'See it for yourself here!', 'revenue-generator' ); ?></a></p>

			<p><b><?php esc_html_e( 'Your existing integration will continue to work, uninterrupted. If you’d like to take advantage of these new features, we DO need you to re-register with Laterpay. But don’t worry!', 'revenue-generator' ); ?></b></p>

			<ul class="rev-gen-welcome__list">
				<li><?php esc_html_e( 'Your customers will not experience any outage of service', 'revenue-generator' ); ?></li>
				<li><?php esc_html_e( 'All of your settings have already been migrated over', 'revenue-generator' ); ?></li>
				<li><?php esc_html_e( 'Just use the links to the left to create a new Laterpay account', 'revenue-generator' ); ?></li>
				<li><?php esc_html_e( 'Then you\'ll be asked to register with Stripe, our new Payment Solutions Provider', 'revenue-generator' ); ?></li>
			</ul>

			<p>
				<?php
				printf(
					/* translators: %1$s stands for the link to Laterpay WordPress support email address. */
					esc_html__( 'We\'ve deliberately kept the transition as simple as possible, but if you have any questions or concerns, please feel free to reach out to us at %s.', 'revenue-generator' ),
					'<a href="mailto:wordpress@laterpay.net">wordpress@laterpay.net</a>'
				);
				?>
			</p>
		</section>

		<section class="rev-gen-welcome__buttons">
			<h1 class="rev-gen-welcome__title"><?php esc_html_e( 'Get Started Now!', 'revenue-generator' ); ?></h1>

			<form class="rev-gen-welcome__connect" id="rgv2_js_connect_form">
				<input name="action" type="hidden" value="rgv2_verify_credentials">
				<input name="security" type="hidden" value="<?php echo esc_attr( wp_create_nonce( 'rgv2_settings_nonce' ) ); ?>">
				<input id="rgv2-client-id" name="client_id" type="text" placeholder="<?php esc_attr_e( 'Client ID', 'revenue-generator' ); ?>" maxlength="43" />
				<input id="rgv2-client-secret" name="client_secret" type="text" placeholder="<?php esc_attr_e( 'Client Secret', 'revenue-generator' ); ?>" maxlength="26" />

				<button disabled="disabled" class="rev-gen__button" data-default-text="<?php esc_attr_e( 'Connect Account', 'revenue-generator' ); ?>" data-loading-text="<?php esc_attr_e( 'Please wait…', 'revenue-generator' ); ?>"><?php esc_html_e( 'Connect Account', 'revenue-generator' ); ?></button>

				<p>
					<?php esc_html_e( 'Don’t have an account?', 'revenue-generator' ); ?>
					<a href="<?php echo esc_url( Onboarding_Main::get_signup_url() ); ?>" target="_blank"><?php esc_html_e( 'Signup', 'revenue-generator' ); ?></a>
				</p>
			</form>

			<div class="rev-gen-welcome__buttons-wrap rev-gen-welcome__buttons-wrap--column rev-gen-welcome__connect-buttons" id="rgv2_js_connect_welcome_buttons">
				<button class="rev-gen__button" id="rgv2_js_connect_account"><?php esc_html_e( 'Connect Account', 'revenue-generator' ); ?></button>
				<a class="rev-gen__button rev-gen__button--secondary" href="<?php echo esc_url( Onboarding_Main::get_signup_url() ); ?>" target="_blank"><?php esc_html_e( 'Signup', 'revenue-generator' ); ?></a>
			</div>
		</section>
	</section>
	<div id="rgv2_js_snackBar" class="rev-gen-snackbar"></div>
</div>

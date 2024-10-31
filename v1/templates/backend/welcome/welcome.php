<?php
/**
 * Revenue Generator admin welcome screen.
 *
 * @package revenue-generator
 */

use LaterPay\Revenue_Generator\Inc\View;

if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file.
	exit;
}
?>

<div class="rev-gen-layout-wrapper">
	<section class="rev-gen-welcome">
		<section class="rev-gen-welcome__left">
			<h1 class="rev-gen-welcome__title"><?php esc_html_e( 'Welcome to the', 'revenue-generator' ); ?><br>Revenue Generator</h1>

			<p><?php esc_html_e( 'Requesting contributions, selling single articles, time passes, and subscriptions have never been easier.', 'revenue-generator' ); ?></p>

			<p><?php esc_html_e( 'Your readers and viewers already love your content - the Revenue Generator makes it easy for them to support you. Sell individual pieces of content, timed access to your site, recurring subscriptions, or request contributions - at any price point. Instead of requiring upfront registration and payment, we defer this process until customer purchases combined reach a $5 threshold. Earn money from all of your users, not just subscribers.', 'revenue-generator' ); ?></p>

			<label>
				<input type="checkbox" checked="checked" class="welcome-screen-tracking" name="rg_ga_enabled_status" id="welcome-screen-tracking" value="1">
				<?php esc_html_e( 'Enable usage tracking and help improve Revenue Generator', 'revenue-generator' ); ?>
			</label>
		</section>

		<section class="rev-gen-welcome__buttons">
			<h1 class="rev-gen-welcome__title"><?php esc_html_e( 'Where would you like to start?', 'revenue-generator' ); ?></h1>

			<div class="rev-gen-welcome__buttons-wrap">
				<div id="rg_Contribution" class="rev-gen-card">
					<img class="rev-gen-card__icon" alt="<?php esc_attr_e( 'Create contribution icon', 'revenue-generator' ); ?>" src="<?php echo esc_url( $welcome_contribution_icon ); ?>">
					<h5 class="rev-gen-card__title"><?php esc_html_e( 'Create Contribution', 'revenue-generator' ); ?></h5>
				</div>

				<div id="rg_Paywall" class="rev-gen-card">
					<img class="rev-gen-card__icon" alt="<?php esc_attr_e( 'Create Paywall icon', 'revenue-generator' ); ?>" src="<?php echo esc_url( $welcome_paywall_icon ); ?>">
					<h5 class="rev-gen-card__title"><?php esc_html_e( 'Create Paywall', 'revenue-generator' ); ?></h5>
				</div>
			</div>
		</section>
	</section>
	<div id="rg_js_SnackBar" class="rev-gen-snackbar"></div>
</div>

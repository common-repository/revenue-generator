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
	<div class="welcome-screen-wrapper">
		<div class="welcome-screen-wrapper--main">
			<div class="welcome-screen">
				<h1 class="welcome-screen--heading"><?php esc_html_e( 'Welcome to Revenue Generator', 'revenue-generator' ); ?></h1>
			</div>
			<div class="welcome-screen-question welcome-screen-question-paywall">
				<p class="welcome-screen-publish-questionnaire--heading welcome-screen-publish-questionnaire--description"><?php esc_html_e( 'Want to see this on your own site?', 'revenue-generator' ); ?></p>
				<p class="welcome-screen-question--description"><?php esc_html_e( 'Below, you can take a tour of the features we offer and see a step-by-step demonstration of how to set up your own flexible paywall.', 'revenue-generator' ); ?></p>
			</div>
			<div class="welcome-screen-publish-questionnaire welcome-screen-publish-questionnaire-paywall">
				<p class="welcome-screen-publish-questionnaire--heading"><?php esc_html_e( 'How often do you publish premium content?', 'revenue-generator' ); ?></p>
			</div>
			<div class="welcome-screen-wrapper--card">
				<div id="rg_js_lowPostCard" class="rg-card">
					<img class="rg-card--icon" alt="<?php esc_attr_e( 'Fewer posts icon', 'revenue-generator' ); ?>" src="<?php echo esc_url( $low_count_icon ); ?>">
					<h5 class="rg-card--title">
						<?php
							// translators: %1$s Fewer %2$s Bold First Letters.
							printf( esc_html__( '%1$sFewer%2$s than 10 posts per month', 'revenue-generator' ), '<b>', '</b>' );
						?>
					</h5>
				</div>
				<div id="rg_js_highPostCard" class="rg-card">
					<img class="rg-card--icon" alt="<?php esc_attr_e( 'More posts icon', 'revenue-generator' ); ?>" src="<?php echo esc_url( $high_count_icon ); ?>">
					<h5 class="rg-card--title">
						<?php
							// translators: %1$s More %2$s Bold First Letters.
							printf( esc_html__( '%1$sMore%2$s than 10 posts per month', 'revenue-generator' ), '<b>', '</b>' );
						?>
					</h5>
				</div>
			</div>
		</div>
		<div id="rg_js_SnackBar" class="rev-gen-snackbar"></div>
	</div>
</div>

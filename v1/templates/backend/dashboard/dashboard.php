<?php
/**
 * Revenue Generator admin dashboard screen.
 *
 * @package revenue-generator
 */

use LaterPay\Revenue_Generator\Inc\Post_Types\Paywall;
use LaterPay\Revenue_Generator\Inc\View;

if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file.
	exit;
}
?>

<div class="rev-gen-layout-wrapper">
	<div class="laterpay-loader-wrapper">
		<img alt="<?php esc_attr_e( 'Contribute.to Logo', 'revenue-generator' ); ?>" src="<?php echo esc_url( $action_icons['lp_icon'] ); ?>" />
	</div>
	<div class="rev-gen-dashboard-main" data-current="Paywall">
		<div class="rev-gen-dashboard-bar">
			<div class="rev-gen-dashboard-bar--item rev-gen-dashboard-bar--filter">
				<label for="rg_js_filterPaywalls"><?php esc_html_e( 'Sort By', 'revenue-generator' ); ?></label>
				<select id="rg_js_filterPaywalls" class="rev-gen__select2">
					<option <?php selected( strtolower( $current_sort_order ), 'desc', true ); ?> value="desc"><?php esc_html_e( 'Newest First', 'revenue-generator' ); ?></option>
					<option <?php selected( strtolower( $current_sort_order ), 'asc', true ); ?> value="asc"><?php esc_html_e( 'Oldest First', 'revenue-generator' ); ?></option>
					<option <?php selected( strtolower( $current_sort_order ), 'priority', true ); ?> value="priority"><?php esc_html_e( 'Priority', 'revenue-generator' ); ?></option>
				</select>
			</div>
			<div class="rev-gen-dashboard-bar--item rev-gen-dashboard-bar--search">
				<input placeholder="<?php esc_attr_e( 'Search Paywalls', 'revenue-generator' ); ?>" type="text" id="rg_js_searchPaywall" value="<?php echo esc_attr( $search_term ); ?>">
				<i class="rev-gen-dashboard-bar--search-icon"></i>
			</div>
			<div class="rev-gen-dashboard-bar--item rev-gen-dashboard-bar--actions">
				<a href="<?php echo esc_url( $new_paywall_url ); ?>" id="rg_js_newPaywall" class="rev-gen__button"><?php esc_html_e( 'New Paywall', 'revenue-generator' ); ?></a>
			</div>
		</div>
		<div class="rev-gen-dashboard-content">
			<?php
			if ( ! empty( $paywalls ) ) :
				foreach ( $paywalls as $paywall ) {
					$paywall_id        = $paywall['id'];
					$paywall_title     = $paywall['name'];
					$paywall_updated   = $paywall['updated'];
					$paywall_published = $paywall['published_on'];
					?>
					<div class="rev-gen-dashboard-content-paywall" data-paywall-id="<?php echo esc_attr( $paywall_id ); ?>">
						<?php Paywall::generate_paywall_mini_preview( $paywall_id ); ?>
						<div class="rev-gen-dashboard-content-paywall-info">
							<span contenteditable="true" class="rev-gen-dashboard-paywall-name"><?php echo esc_html( $paywall_title ); ?></span>
							<p><?php echo wp_kses_post( $paywall_published ); ?></p>
							<p class="rev-gen-dashboard-content-paywall-info-updated"><?php echo esc_html( $paywall_updated ); ?></p>
							<div class="rev-gen-dashboard-content-paywall-info-links">
								<a href="#" class="rev-gen-dashboard-remove-paywall" data-paywall-id="<?php echo esc_attr( $paywall_id ); ?>"><?php esc_html_e( 'Delete Paywall', 'revenue-generator' ); ?></a>
							</div>
						</div>
					</div>
				<?php } else : ?>
				<div class="rev-gen-dashboard-content-nopaywall">
					<div class="rev-gen-dashboard-content-nopaywall--title">
						<?php

						$empty_paywall_button_text = ( ! empty( $search_term ) ) ? __( 'Create a new Paywall', 'revenue-generator' ) : __( 'Create your first Paywall', 'revenue-generator' );
						$empty_paywall_message     = ( ! empty( $search_term ) ) ? __( 'No paywalls matched your search, <br /> try again or', 'revenue-generator' ) : __( 'It’s pretty empty here, <br /> let’s create your first Paywall.', 'revenue-generator' );

						printf(
							wp_kses(
								$empty_paywall_message,
								[
									'br' => [],
								]
							)
						);
						?>
					</div>
					<div class="rev-gen-dashboard-content-nopaywall--create-paywall">
						<a href="<?php echo esc_url( $new_paywall_url ); ?>" class="rev-gen-dashboard-content-nopaywall--create-paywall--button"><?php echo esc_html( $empty_paywall_button_text ); ?></a>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<div id="rg_js_SnackBar" class="rev-gen-snackbar"></div>
	<div class="rev-gen__button rev-gen__button--secondary rev-gen__button--help rev-gen-start-tutorial" id="rg_js_RestartTutorial"><?php esc_html_e( 'Tutorial', 'revenue-generator' ); ?></div>
</div>

<?php include( REVENUE_GENERATOR_PLUGIN_DIR . '/v1/templates/backend/modal-remove-paywall.php' ); ?>

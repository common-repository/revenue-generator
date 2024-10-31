<?php
/**
 * Revenue Generator admin settings screen.
 *
 * @package revenue-generator
 */

use \LaterPay\Revenue_Generator\Inc\View;
use \LaterPay\Revenue_Generator\Inc\Post_Types\Contribution;

if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file.
	exit;
}

$contribution_builder_data = apply_filters( 'rg_contribution_builder_data', $contribution_data );
?>

<script>
	var RevGenContributionData = <?php echo json_encode( $contribution_builder_data ); ?>;
</script>

<div class="wrap">
	<div class="rev-gen-layout-wrapper">
		<div class="laterpay-loader-wrapper">
			<img alt="<?php esc_attr_e( 'Contribute.to Logo', 'revenue-generator' ); ?>" src="<?php echo esc_url( $action_icons['lp_icon'] ); ?>" />
		</div>
		<div class="rg-contribution-builder" id="rg-contribution-builder-app">
			<section class="rg-contribution-builder__left">
				<form class="rg-contribution-builder__form" id="rg_js_form">
					<h1 class="rg-contribution-builder__title"><?php esc_html_e( 'Create Your Contribution', 'revenue-generator' ); ?></h1>

					<section class="rg-contribution-builder__layout-select rg-contribution-layout-select" id="rg-contribution-layout-select">
						<h3 class="rg-contribution-layout-select__title"><?php esc_html_e( 'Select contribution type', 'revenue-generator' ); ?></h3>

						<div class="rg-contribution-layout-select__options">
							<label class="rg-contribution-layout-type">
								<input type="radio" class="rg-visuallyhidden" name="layout_type" value="box" data-bind="layout_type" <?php checked( 'box', $contribution_data['layout_type'] ); ?>>
								<div class="rg-contribution-layout-type__preview">
									<div class="rg-contribution-layout-type__box"></div>
									<?php esc_html_e( 'Box', 'revenue-generator' ); ?>
								</div>
							</label>
							<label class="rg-contribution-layout-type">
								<input type="radio"  class="rg-visuallyhidden" name="layout_type" value="button" data-bind="layout_type" <?php checked( 'button', $contribution_data['layout_type'] ); ?>>
								<div class="rg-contribution-layout-type__preview">
									<div class="rg-contribution-layout-type__box rg-contribution-layout-type__box--pill"></div>
									<span class="rg-contribution-layout-type__label"><?php esc_html_e( 'Button', 'revenue-generator' ); ?></span>
								</div>
							</label>
							<label class="rg-contribution-layout-type">
								<input type="radio"  class="rg-visuallyhidden" name="layout_type" value="bar" data-bind="layout_type" <?php checked( 'bar', $contribution_data['layout_type'] ); ?>>
								<div class="rg-contribution-layout-type__preview">
									<div class="rg-contribution-layout-type__box rg-contribution-layout-type__box--complex">
										<div class="rg-contribution-layout-type__inner"></div>
									</div>
									<?php esc_html_e( 'Bar', 'revenue-generator' ); ?>
								</div>
							</label>
						</div>
					</section>

					<section class="rg-contribution-builder-inputs">
						<div class="rg-contribution-builder__input-wrap">
							<input type="text" placeholder="<?php esc_attr_e( 'Campaign Name', 'revenue-generator' ); ?>" value="<?php echo esc_attr( $contribution_data['post_title'] ); ?>" data-bind="name" id="rg-contribution-campaign-name" required data-validate>
							<p class="input-error-text"><?php esc_html_e( 'This field is required' ); ?>.</p>
						</div>
						<div class="rg-contribution-builder__input-wrap">
							<input type="text" placeholder="<?php esc_attr_e( 'Link to Thank You Page', 'revenue-generator' ); ?>" value="<?php echo esc_attr( $contribution_data['thank_you'] ); ?>" data-bind="thank_you" data-validate data-validation="url">
							<p class="input-error-text"><?php esc_html_e( 'Please enter valid URL', 'revenue-generator' ); ?>.</p>
						</div>
						<input type="submit" class="rev-gen__button" value="<?php esc_attr_e( 'Save and Copy Code', 'revenue-generator' ); ?>" disabled="disabled" id="rg-contribution-submit" data-default-text="<?php esc_attr_e( 'Save and Copy Code', 'revenue-generator' ); ?>">
					</section>

					<input type="hidden" name="security" value="<?php echo esc_attr( wp_create_nonce( 'rg_contribution_nonce' ) ); ?>">
					<input type="hidden" name="action" value="rg_contribution_save">
					<input type="hidden" name="ID" value="<?php echo esc_attr( $contribution_data['ID'] ); ?>">

					<p class="rg-contribution-builder__helper-message"><?php esc_html_e( 'To include the Contribution Box on your site, paste the code where you would like it to appear.', 'revenue-generator' ); ?></p>

					<div id="rg_js_SnackBar" class="rev-gen-snackbar"></div>
				</form>
			</section>
			<section class="rg-contribution-builder__preview">
				<a href="#" id="rg_js_toggle_preview" class="rg-contribution-builder__preview-toggle" data-expand-text="<?php esc_attr_e( 'Expand preview', 'revenue-generator' ); ?>" data-collapse-text="<?php esc_attr_e( 'Collapse preview', 'revenue-generator' ); ?>"><?php esc_html_e( 'Expand preview', 'revenue-generator' ); ?></a>

				<iframe src="<?php echo esc_url( Contribution::get_preview_post_url() ); ?>&id=<?php echo esc_attr( $contribution_data['ID'] ); ?>" width="100%" height="100%" id="rg-contribution-builder-preview" onload="javascript:handleIframeLoad(this)" class="loading"></iframe>

				<ul class="rg-contribution-builder__tour-nav rg-tour-nav" id="rg-tour-progress">
					<?php for ( $i = 1; $i <= 4; $i++ ) : ?>
						<li class="rg-tour-nav__item"><?php echo esc_html( $i ); ?></li>
					<?php endfor; ?>
				</ul>
			</section>
		</div>
	</div>
</div>
<!-- Template for ShortCode modal -->
<script type="text/template" id="tmpl-revgen-info-shortcode">
	<div class="rev-gen-contribution-main-info-modal rev-gen-preview-main-info-modal campaign-name-info-modal">
	<span class="rev-gen-contribution-main-info-modal-cross"><img alt="<?php esc_attr_e( 'close', 'revenue-generator' ); ?>" src="<?php echo esc_url( $action_icons['icon_close'] ); ?>" /></span>
		<h4 class="rev-gen-preview-main-info-modal-title rev-gen-settings-main-info-modal-title"><?php esc_html_e( 'Contribution Shortcode', 'revenue-generator' ); ?></h4>
		<p class="rev-gen-preview-main-info-modal-message">
			<?php
			echo wp_kses(
				__(
					'Once you have completed the information above, simply click "Generate and copy code." This will use the information you have provided to create a customized <a href="https://wordpress.com/support/shortcodes/" target="_blank">shortcode</a>. It will also copy this code to your clipboard so all that you need to do is navigate to where you would like this to appear on your site & paste it in pace.',
					'revenue-generator'
				),
				[
					'a' => [
						'href'   => [],
						'target' => [],
					],
				]
			);
			?>
		</p>
	</div>
</script>
<!-- Template for Campaign Name modal -->
<script type="text/template" id="tmpl-revgen-info-campaignName">
	<div class="rev-gen-contribution-main-info-modal rev-gen-preview-main-info-modal campaign-name-info-modal">
	<span class="rev-gen-contribution-main-info-modal-cross"><img alt="<?php esc_attr_e( 'close', 'revenue-generator' ); ?>" src="<?php echo esc_url( $action_icons['icon_close'] ); ?>" /></span>
		<h4 class="rev-gen-preview-main-info-modal-title rev-gen-settings-main-info-modal-title"><?php esc_html_e( 'Campaign Name', 'revenue-generator' ); ?></h4>
		<p class="rev-gen-preview-main-info-modal-message">
			<?php esc_html_e( "Enter the name you would like to appear on your customers' invoice. We recommend including your organization's name as well as something to remind them of this specific contribution.", 'revenue-generator' ); ?>
		</p>
	</div>
</script>
<!-- Template for Thank you modal -->
<script type="text/template" id="tmpl-revgen-info-thankYouPage">
	<div class="rev-gen-contribution-main-info-modal rev-gen-preview-main-info-modal thankyoupage-info-modal">
	<span class="rev-gen-contribution-main-info-modal-cross"><img alt="<?php esc_attr_e( 'close', 'revenue-generator' ); ?>" src="<?php echo esc_url( $action_icons['icon_close'] ); ?>" /></span>
		<h4 class="rev-gen-preview-main-info-modal-title rev-gen-settings-main-info-modal-title"><?php esc_html_e( 'Thank You Page', 'revenue-generator' ); ?></h4>
		<p class="rev-gen-preview-main-info-modal-message">
			<?php esc_html_e( 'After your customer has contributed, we can redirect them to a page of your choice (for example, a dedicated "thank you" page on your website). If no thank you page is provided, they will be redirected to the page which initiated their contribution.', 'revenue-generator' ); ?>
		</p>
	</div>
</script>


<?php include( REVENUE_GENERATOR_PLUGIN_DIR . '/v1/templates/backend/modal-account-activation.php' ); ?>
<?php include( REVENUE_GENERATOR_PLUGIN_DIR . '/v1/templates/backend/modal-account-connect.php' ); ?>

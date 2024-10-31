<?php
/**
 * Revenue Generator admin settings screen.
 *
 * @package revenue-generator
 */

use LaterPay\Revenue_Generator\Inc\View;

if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file.
	exit;
}
?>
<div class="wrap">
	<div class="rev-gen-layout-wrapper">
		<div class="laterpay-loader-wrapper">
			<img alt="<?php esc_attr_e( 'Contribute.to Logo', 'revenue-generator' ); ?>" src="<?php echo esc_url( $action_icons['lp_icon'] ); ?>" />
		</div>
		<div class="rev-gen-settings-main">
			<h3 class="rev-gen-settings-main--header"><?php esc_html_e( 'Settings', 'revenue-generator' ); ?></h3>
			<div class="rev-gen-settings-main--publish-settings">
				<table class="form-table rev-gen-settings-main-table">
					<tr>
						<th>
							<?php esc_html_e( 'Posts per month', 'revenue-generator' ); ?>
						</th>
						<td>
							<label class="rev-gen-settings-main-radio-label">
								<input type="radio" class="rev-gen-settings-main-post-per-month" <?php checked( $global_options['average_post_publish_count'], 'low', true ); ?> name="rg_global_options[average_post_publish_count]" value="low" />
								<?php esc_html_e( 'Fewer than 10', 'revenue-generator' ); ?>
							</label>
							<label class="rev-gen-settings-main-radio-label">
								<input type="radio" class="rev-gen-settings-main-post-per-month" <?php checked( $global_options['average_post_publish_count'], 'high', true ); ?> name="rg_global_options[average_post_publish_count]" value="high" />
								<?php esc_html_e( 'More than 10', 'revenue-generator' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e( 'Merchant ID', 'revenue-generator' ); ?>
						</th>
						<td>
							<input type="text" autocomplete="off" name="lp_rg_merchant_credentials[merchant_id]" class="rev-gen-settings-main-merchant-id" value="<?php echo esc_attr( $merchant_credentials['merchant_id'] ); ?>" size="48" />
						</td>
					</tr>
					<tr>
						<th>
							<?php esc_html_e( 'API Key', 'revenue-generator' ); ?>
						</th>
						<td>
							<input type="text" autocomplete="off" name="lp_rg_merchant_credentials['merchant_key']" class="rev-gen-settings-main-merchant-key" value="<?php echo esc_attr( $merchant_credentials['merchant_key'] ); ?>" size="48" />
						</td>
					</tr>
				</table>
				<table class="form-table rev-gen-settings-main-table rev-gen-settings-main-ga-table">
					<tr>
						<td>
							<?php esc_html_e( 'Analytics', 'revenue-generator' ); ?>
						</td>
						<td>
						</td>
						<td>
							<?php esc_html_e( 'Google Analytics “UA-ID”', 'revenue-generator' ); ?>
						</td>
					</tr>
					<tr class="rg-user-row">
						<th>
							<?php esc_html_e( 'Your Google Analytics', 'revenue-generator' ); ?>
						</th>
						<td>
							<label for="rgGAUserStatus">
								<input id="rgGAUserStatus" type="checkbox" value="1" <?php checked( $settings_options['rg_ga_personal_enabled_status'], '1', true ); ?> class="rev-gen-settings-main-ga-user-status rg-settings-ga-status" value="1" />
							<?php esc_html_e( 'Enabled', 'revenue-generator' ); ?>
							</label>
						</td>
						<td>
							<div class="rev-gen-settings-main-field-right">
								<input type="text" class="rev-gen-settings-main-ga-code-user rev-gen-settings-main-ga-input" autocomplete="off" value="<?php echo esc_attr( $settings_options['rg_personal_ga_ua_id'] ); ?>" size="24" />
								<button data-info-for="user" id="rev-gen-settings-user-info-modal" class="rev-gen-settings-main-option-info">
									<img src="<?php echo esc_url( $action_icons['option_info'] ); ?>">
								</button>
							</div>
						</td>
					</tr>
					<tr class="rg-laterpay-row">
						<th>
							<?php esc_html_e( 'Our Google Analytics', 'revenue-generator' ); ?>
						</th>
						<td>
							<label for="rgGALaterPayStatus">
								<input id="rgGALaterPayStatus" type="checkbox" value="1" <?php checked( $settings_options['rg_ga_enabled_status'], '1', true ); ?> class="rev-gen-settings-main-ga-laterpay-status rg-settings-ga-status" value="1" />
							<?php esc_html_e( 'Enabled', 'revenue-generator' ); ?>
							</label>
						</td>
						<td>
							<div class="rev-gen-settings-main-field-right">
								<input type="text" readonly="readonly" class="rev-gen-settings-main-ga-code-laterpay rev-gen-settings-main-ga-input" autocomplete="off" value="<?php echo esc_attr( $settings_options['rg_laterpay_ga_ua_id'] ); ?>" size="24" />
								<button data-info-for="laterpay" id="rev-gen-settings-laterpay-info-modal" class="rev-gen-settings-main-option-info">
									<img src="<?php echo esc_url( $action_icons['option_info'] ); ?>">
								</button>
							</div>
						</td>
					</tr>
					<tr>
						<td>
							<button type="button" class="button button-primary rev-gen-settings-main-save-settings">
								<?php esc_html_e( 'Save Settings', 'revenue-generator' ); ?>
							</button>
						</td>
						<td></td>
						<td></td>
					</tr>
				</table>

			</div>
		</div>
		<div id="rg_js_SnackBar" class="rev-gen-snackbar"></div>
	</div>
</div>

<!-- Template for User info modal -->
<script type="text/template" id="tmpl-revgen-info-user">
	<div class="rev-gen-settings-main-info-modal rev-gen-preview-main-info-modal user-info-modal">
	<span class="rev-gen-settings-main-info-modal-cross">X</span>
		<h4 class="rev-gen-preview-main-info-modal-title rev-gen-settings-main-info-modal-title"><?php esc_html_e( 'Your Google Analytics', 'revenue-generator' ); ?></h4>
		<p class="rev-gen-preview-main-info-modal-message">
			<?php
				printf(
					wp_kses(
						__( 'Provide us with your <a href="https://support.google.com/analytics/answer/7372977?hl=en" target="_blank">Google Analytics UA-ID</a> and check to enable this feature if you would like to receive events in your own Google Analytics instance.', 'revenue-generator' ),
						[
							'a' => [
								'href'   => [],
								'target' => [],
							],
						]
					)
				);
				?>
		</p>
		<p class="rev-gen-preview-main-info-modal-message">
			<?php esc_html_e( 'This will include things like the number of times your customers come across our paywall as well as the number of successful purchases, so that you can easily track your conversion rates.', 'revenue-generator' ); ?>
		</p>
		<p class="rev-gen-preview-main-info-modal-message">
			<?php
			printf(
				wp_kses(
					__(
						'For more analytics, log in to your <a href="https://www.laterpay.net/" target="_blank">Laterpay Merchant Portal</a> and check out your Analytics Dashboard.',
						'revenue-generator'
					),
					[
						'a' => [
							'href'   => [],
							'target' => [],
						],
					]
				)
			);
			?>
		</p>
	</div>
</script>
<script type="text/template" id="tmpl-revgen-info-laterpay">
	<div class="rev-gen-settings-main-info-modal rev-gen-preview-main-info-modal laterpay-info-modal">
	<span class="rev-gen-settings-main-info-modal-cross">X</span>
		<h4 class="rev-gen-preview-main-info-modal-title rev-gen-settings-main-info-modal-title"><?php esc_html_e( 'Google Analytics', 'revenue-generator' ); ?></h4>
		<p class="rev-gen-preview-main-info-modal-message">
			<?php
			esc_html_e( 'Revenue Generator collects information on how you are using our plugin in order to improve our products and services. We are not in the business of selling data but use this data only to benefit you, our customer.', 'revenue-generator' );
			?>
		</p>
		<p class="rev-gen-preview-main-info-modal-message">
			<?php esc_html_e( 'If you would like more information, contact' ); ?>
			<a href="mailto:wordpress@laterpay.net">wordpress@laterpay.net</a>
		</p>
	</div>
</script>

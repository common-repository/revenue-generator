<?php
/**
 * Revenue Generator Contribution dashboard screen.
 *
 * @package revenue-generator
 */

use LaterPay\Revenue_Generator\Inc\View;
use LaterPay\Revenue_Generator\Inc\Post_Types\Contribution as Contribution;

if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file.
	exit;
}
?>

<div class="rev-gen-layout-wrapper">
	<div class="laterpay-loader-wrapper">
		<img alt="<?php esc_attr_e( 'Contribute.to Logo', 'revenue-generator' ); ?>" src="<?php echo esc_url( $action_icons['lp_icon'] ); ?>" />
	</div>
	<div class="rev-gen-dashboard-main" data-current="Contribution">
		<div class="rev-gen-dashboard-bar">
			<div class="rev-gen-dashboard-bar--item rev-gen-dashboard-bar--filter">
				<label for="rg_js_filterPaywalls"><?php esc_html_e( 'Sort By', 'revenue-generator' ); ?></label>
				<select id="rg_js_filterPaywalls" class="rev-gen__select2 rev-gen__select2--no-search">
					<option <?php selected( strtolower( $current_sort_order ), 'desc', true ); ?> value="desc"><?php esc_html_e( 'Newest First', 'revenue-generator' ); ?></option>
					<option <?php selected( strtolower( $current_sort_order ), 'asc', true ); ?> value="asc"><?php esc_html_e( 'Oldest First', 'revenue-generator' ); ?></option>
				</select>
			</div>
			<div class="rev-gen-dashboard-bar--item rev-gen-dashboard-bar--search">
				<input placeholder="<?php esc_attr_e( 'Search Contributions', 'revenue-generator' ); ?>" type="text" id="rg_js_searchPaywall" value="<?php echo esc_attr( $search_term ); ?>">
				<i class="rev-gen-dashboard-bar--search-icon"></i>
			</div>
			<div class="rev-gen-dashboard-bar--item rev-gen-dashboard-bar--actions">
				<a href="<?php echo esc_url( $new_contribution_url ); ?>" id="rg_js_newContribution" class="rev-gen__button"><?php esc_html_e( 'New Contribution', 'revenue-generator' ); ?></a>
			</div>
		</div>
		<div class="rev-gen-dashboard-content rev-gen-dashboard-content-contribution-wrapper">
			<?php
			$contribution_instance = Contribution::get_instance();

			if ( ! empty( $contributions ) ) :
				foreach ( $contributions as $contribution ) {
					$contribution_id             = $contribution['ID'];
					$contribution_title          = $contribution['post_title'];
					$contribution_shortcode      = $contribution_instance->get_shortcode( $contribution );
					$contribution_updated_string = $contribution_instance->get_date_time_string( $contribution );
					$contribution_edit_link      = $contribution_instance->get_edit_link( $contribution['ID'] );
					$delete_button_text          = __( 'Delete', 'revenue-generator' );

					if ( ! empty( $contribution['code'] ) ) {
						$delete_button_text = __( 'Hide', 'revenue-generator' );
					}
					?>
					<div class="rev-gen-dashboard-content-contribution" data-contribution-id="<?php echo esc_attr( $contribution_id ); ?>">
						<div class="rev-gen-dashboard-content-contribution--box">
							<a href="<?php echo esc_url( $contribution_edit_link ); ?>" class="rev-gen-dashboard-content-contribution--box__link">
								<span class="screen-reader-text"><?php esc_html_e( 'Edit Contribution offer', 'revenue-generator' ); ?>
							</a>
							<h4 class="rev-gen-dashboard-content-contribution--box-header">
								<?php echo esc_html( $contribution['dialog_header'] ); ?>
							</h4>
							<p class="rev-gen-dashboard-content-contribution--box-description">
								<?php echo esc_html( $contribution['dialog_description'] ); ?>
							</p>
							<div class="rev-gen-dashboard-content-contribution--box-donation-wrapper">
								<?php
								if ( ! empty( $contribution['all_amounts'] ) ) {
									foreach ( $contribution['all_amounts'] as $key => $amount ) {
										?>
									<div class="rev-gen-dashboard-content-contribution--box-donation">
										<span class="rev-gen-dashboard-content-contribution--box-donation-currency">
											<?php echo esc_html( $currency_symbol ); ?>
										</span>
										<span class="rev-gen-dashboard-content-contribution--box-donation-amount">
											<?php echo esc_html( View::format_number( floatval( (int) $amount / 100 ), 2 ) ); ?>
										</span>
									</div>
										<?php
									}
								}
								?>
								<div class="rev-gen-dashboard-content-contribution--box-donation">
									<span class="rev-gen-dashboard-content-contribution--box-donation-currency">
										<?php echo esc_html( $currency_symbol ); ?>
									</span>
									<span class="rev-gen-dashboard-content-contribution--box-donation-amount">
										<?php esc_html_e( 'custom', 'revenue-generator' ); ?>
									</span>
								</div>
							</div>
						</div>
						<div class="rev-gen-dashboard-content-contribution--info">
							<h3 class="rev-gen-dashboard-content-contribution--info-title">
								<?php echo esc_html( $contribution_title ); ?>
							</h3>
							<div class="rev-gen-dashboard-content-contribution--info-url">
								<?php echo esc_html( $contribution['thank_you'] ); ?>
							</div>
							<div class="rev-gen-dashboard-content-contribution--info-updated">
								<?php echo esc_html( $contribution_updated_string ); ?>
							</div>
							<div class="rev-gen-dashboard-content-contribution--links">
								<a href="#" class="rev-gen-dashboard__link--copy-shortcode" data-shortcode="<?php echo esc_attr( $contribution_shortcode ); ?>"><?php esc_html_e( 'Copy shortcode', 'revenue-generator' ); ?></a> |
								<a href="<?php echo esc_url( $contribution_edit_link ); ?>"><?php esc_html_e( 'Edit', 'revenue-generator' ); ?></a> |
								<a href="#" data-id="<?php echo esc_attr( $contribution['ID'] ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'rg_contribution_delete_nonce' ) ); ?>" data-editable="<?php echo esc_attr( empty( $contribution['code'] ) ? 1 : 0 ); ?>" class="rev-gen-dashboard__contribution-delete"><?php echo esc_html( $delete_button_text ); ?></a>
							</div>
						</div>
					</div>
			<?php } else : ?>
			<div class="rev-gen-dashboard-content-nopaywall">
				<div class="rev-gen-dashboard-content-nopaywall--title">
					<?php
					$empty_contribution_button_text = ( ! empty( $search_term ) ) ? __( 'Create a new Contribution', 'revenue-generator' ) : __( 'Create your first Contribution Dialog', 'revenue-generator' );
					$empty_contribution_message     = ( ! empty( $search_term ) ) ? __( 'No Contribution matched your search, <br /> try again or', 'revenue-generator' ) : __( 'Welcome back! <br /> Are you ready to get started?', 'revenue-generator' );

					printf(
						wp_kses(
							$empty_contribution_message,
							[
								'br' => [],
							]
						)
					);
					?>
				</div>
				<div class="rev-gen-dashboard-content-nopaywall--create-paywall">
					<a href="<?php echo esc_url( $new_contribution_url ); ?>" class="rev-gen-dashboard-content-nopaywall--create-paywall--button"><?php echo esc_html( $empty_contribution_button_text ); ?></a>
				</div>
			</div>
			<?php endif; ?>
		</div>
		<div id="rg_js_SnackBar" class="rev-gen-snackbar"></div>
	</div>
	<div class="rev-gen__button rev-gen__button--secondary rev-gen__button--help rev-gen-start-tutorial" id="rg_js_RestartTutorial_Contribution"><?php esc_html_e( 'Tutorial', 'revenue-generator' ); ?></div>
</div>

<script type="text/template" id="tmpl-rg-modal-remove-contribution">
	<div class="rev-gen-modal" id="rg-modal-remove-contribution">
		<div class="rev-gen-modal__inner">
			<h4 class="rev-gen-modal__title">
				<?php esc_html_e( 'Are you sure you want to remove the contribution request?', 'revenue-generator' ); ?>
			</h4>

			<# if ( 1 === data.isEditable ) { #>
				<p class="rev-gen-modal__message">
					<?php esc_html_e( 'This will hide the contribution from your published site but the shortcode will need to be manually removed from the editor.', 'revenue-generator' ); ?>
				</p>
			<# } else { #>
				<p class="rev-gen-modal__message">
					<?php esc_html_e( 'This will NOT remove the Contribution request from your site.', 'revenue-generator' ); ?>
				</p>
				<p class="rev-gen-modal__message">
					<?php esc_html_e( 'The shortcode will need to be manually removed from the editor in order to remove it from your published site. Are you sure that you would like to hide this contribution offer from your dashboard?', 'revenue-generator' ); ?>
				</p>
			<# } #>
			</p>
			<div class="rev-gen-modal__buttons">
				<button id="rg_js_modal_confirm" class="rev-gen__button">
					<# if ( 1 === data.isEditable ) { #>
						<?php esc_html_e( 'Yes, remove Contribution request', 'revenue-generator' ); ?>
					<# } else { #>
						<?php esc_html_e( 'Yes, hide Contribution request', 'revenue-generator' ); ?>
					<# } #>
				</button>
				<button id="rg_js_modal_cancel" class="rev-gen__button rev-gen__button--secondary">
					<?php esc_html_e( 'No, keep Contribution request', 'revenue-generator' ); ?>
				</button>
			</div>
		</div>
	</div>
	<div class="rev-gen-modal-overlay"></div>
</script>

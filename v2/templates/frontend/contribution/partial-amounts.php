<?php
/**
 * Donation box contribution partial template.
 *
 * @package revenue-generator
 */

use \LaterPay\Revenue_Generator\Inc\View;
use \LaterPay\Revenue_Generator\Inc\Frontend_Post;

defined( 'ABSPATH' ) || exit;

global $wp;
?>

<?php
/**
 * The following bit is added so that AMP compiled CSS include
 * the purchase component styles which is not yet available
 * in DOM at the time of render.
 */
?>
<?php if ( $is_amp ) : ?>
	<div class="tab_widget__container tab_widget__arc-container tab_widget__arc--background tab_widget__arc--filled tab_widget__donated-amount-text tab_widget__thank-you-message tab_widget__links-container tab_widget__link tab_widget__links-separator tab_widget__container-hidden tab_widget__container-visible tab_widget__regular-text tab_widget tab_widget__arc-animation lp__root lp__tab-widget-data tab__widget__info-button__container tab__widget__info-button tab__widget__info-button-question-mark tab__widget__close-button-x-mark tab_widget__container__info-screen infoContainer title steps step stepText stepNumber description tab__widget__icon" style="display: none;"></div>
<?php endif; ?>

<div class="rev-gen-contribution__donate" id="<?php echo esc_attr( $html_id ); ?>_donate">
	<form class="rev-gen-contribution__form" action="<?php echo esc_url( admin_url() ); ?>admin-ajax.php" method="POST" id="<?php echo esc_attr( $html_id ); ?>_donate-form" action-xhr="<?php echo esc_url( admin_url() ); ?>admin-ajax.php" data-tab-widget-url="<?php echo esc_url( Frontend_Post::get_tab_widget_script_url() ); ?>" on="submit: AMP.setState( { submitted: <?php echo esc_attr( $contribution_id ); ?> } )">
		<?php if ( $is_amp ) : ?>
		<div submit-success>
			<span on="tap:<?php echo esc_attr( $html_id ); ?>_info-modal.show" class="rev-gen-contribution__amp-show-modal"></span>
			<template type="amp-mustache">
				<amp-script layout="fixed" height="105" width="330" script="revenue-generator-amp-purchase-component">
					<div class="lp__root">
						<div class="lp__tab-widget-data"
						data-tab-amount="{{ data.tab.total }}"
						data-tab-currency="USD"
						data-tab-limit="{{ data.tab.limit }}"
						data-view-tab-url="https://mytab.laterpay.net"></div>
					</div>
				</amp-script>
			</template>
		</div>
		<div submit-error>
			<span on="tap:<?php echo esc_attr( $html_id ); ?>_info-modal.show" class="rev-gen-contribution__amp-show-modal--purchase"></span>
			<amp-list width="auto" height="250" src="<?php echo esc_url( Frontend_Post::get_json_amp_url( 'payment' ) ); ?>&key=CLIENT_ID(uid)" max-items="1" binding="no">
				<template type="amp-mustache">
					<iframe src="{{url}}" sandbox="allow-scripts allow-forms allow-same-origin" width="100%" height="250" allowpaymentrequest></iframe>
				</template>
			</amp-list>
		</div>
		<?php endif; ?>
		<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( Frontend_Post::CONTRIBUTION_AJAX_ACTION ) ); ?>">
		<input type="hidden" name="item_id" value="<?php echo esc_attr( $contribution_id ); ?>">
		<input type="hidden" name="redirect_uri" value="<?php echo esc_url_raw( home_url( $wp->request ) ); ?>">
		<input type="hidden" name="layout_type" value="<?php echo esc_attr( $layout_type ); ?>">
		<input type="hidden" name="action" value="<?php echo esc_attr( Frontend_Post::CONTRIBUTION_AJAX_ACTION ); ?>">
		<?php if ( $is_amp ) : ?>
		<input type="hidden" name="rg_key" value="CLIENT_ID(uid)" data-amp-replace="CLIENT_ID">
		<input type="hidden" name="is_amp" value="1">
		<?php endif; ?>

		<div class="rev-gen-contribution__amounts" id="<?php echo esc_attr( $html_id ); ?>_amounts">
			<?php
			foreach ( $payment_config['amounts'] as $amount_info ) {
				$lp_amount = View::format_number( floatval( $amount_info['amount'] / 100 ), 2 );
				?>
				<label class="rev-gen-contribution__amount" on="tap:<?php echo esc_attr( $html_id ); ?>_submit.show">
					<input type="radio" name="amount" value="<?php echo esc_attr( $lp_amount ); ?>">
					<span class="rev-gen-contribution-control__label">
						<?php echo esc_html( '$' ); ?><?php echo esc_html( $lp_amount ); ?>
					</span>
				</label>
				<?php
			}
			?>
			<button type="button" class="rev-gen-contribution__amount rev-gen-contribution__amount--custom"<?php echo ( $is_amp ) ? 'on="tap:' . esc_attr( $html_id ) . '_amounts.toggleVisibility,' . esc_attr( $html_id ) . '_custom.toggleVisibility,' . esc_attr( $html_id ) . '_submit.show"' : ''; ?>>
				<?php esc_html_e( 'Custom', 'revenue-generator' ); ?>
			</button>
		</div>

		<div class="rev-gen-contribution__custom rev-gen-contribution-custom" id="<?php echo esc_attr( $html_id ); ?>_custom"<?php echo ( $is_amp ) ? ' hidden' : ''; ?>>
			<div class="rev-gen-contribution-custom__back" on="tap:<?php echo esc_attr( $html_id ); ?>_amounts.toggleVisibility,<?php echo esc_attr( $html_id ); ?>_custom.toggleVisibility">
				<span class="rev-gen-contribution-custom__back-arrow"></span>
			</div>
			<div class="rev-gen-contribution-custom__group">
				<input type="number" step="0.01" min="0" id="<?php echo esc_attr( $html_id ); ?>_custom" name="custom_amount" placeholder="<?php esc_attr_e( 'Enter custom amount', 'revenue-generator' ); ?>">
			</div>
		</div>

		<button type="submit" data-mytab-button id="<?php echo esc_attr( $html_id ); ?>_submit"<?php echo ( $is_amp ) ? ' hidden' : ''; ?>><?php esc_html_e( 'Add to MyTab', 'revenue-generator' ); ?></button>
	</form>

</div>

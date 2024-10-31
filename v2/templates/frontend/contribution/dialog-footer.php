<?php
/**
 * Revenue Generator Contribution floating footer template.
 *
 * @package revenue-generator
 */

use \LaterPay\Revenue_Generator\Inc\View;

if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file.
	exit;
}
?>
<div class="rev-gen-footer-contribution">
	<div class="rev-gen-contribution rev-gen-contribution--footer<?php echo ( $is_amp ) ? ' is-amp' : ''; ?>" id="<?php echo esc_attr( $html_id ); ?>" data-step="default" data-type="box" data-dismiss-for="<?php echo esc_attr( apply_filters( 'rg_contribution_footer_dismiss_for', 1 * DAY_IN_SECONDS ) ); ?>" data-contribution-id="<?php echo esc_attr( $contribution_id ); ?>"<?php echo ( $is_amp ) ? ' [class]="submitted == ' . esc_attr( $contribution_id ) . ' ? \'rev-gen-contribution rev-gen-contribution--box is-style-wide is-amp amp-submitted\' : \'rev-gen-contribution rev-gen-contribution--box is-style-wide is-amp\'"' : ''; ?>>
		<div class="rev-gen-contribution__inner">
			<button class="rev-gen-contribution__toggle" type="button">
				<span class="screen-reader-text"><?php esc_html_e( 'Toggle visibility of this contribution', 'revenue-generator' ); ?></span>
			</button>
			<?php include REVENUE_GENERATOR_PLUGIN_DIR . '/v2/templates/frontend/contribution/info-modal.php'; ?>
			<div class="rev-gen-contribution__choose" data-step="default">
				<h2 class="rev-gen-contribution__title"><?php echo esc_html( $dialog_header ); ?></h2>
				<?php if ( ! empty( $dialog_content ) ) : ?>
				<p class="rev-gen-contribution__content"><?php echo esc_html( $dialog_content ); ?></p>
				<?php endif; ?>
				<?php include REVENUE_GENERATOR_PLUGIN_DIR . '/v2/templates/frontend/contribution/partial-amounts.php'; ?>
			</div>
			<?php include REVENUE_GENERATOR_PLUGIN_DIR . '/v2/templates/frontend/contribution/tab-status.php'; ?>
			<?php include REVENUE_GENERATOR_PLUGIN_DIR . '/v2/templates/frontend/contribution/partial-footer.php'; ?>
		</div>
	</div>
</div>

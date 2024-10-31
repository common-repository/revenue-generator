<?php
/**
 * Revenue Generator Contribution Short code button preview.
 *
 * @package revenue-generator
 */

use LaterPay\Revenue_Generator\Inc\View;

if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file.
	exit;
}
?>
<div class="rev-gen-preview-main">
	<div class="rev-gen-preview__section">
		<h3 class="rev-gen-preview__section-title"><?php esc_html_e( 'Button preview', 'revenue-generator' ); ?></h3>

		<div class="rev-gen-contribution rev-gen-contribution--button is-style-wide<?php echo ( $is_amp ) ? ' is-amp' : ''; ?>" id="<?php echo esc_attr( $html_id ); ?>" data-type="button">
			<div class="rev-gen-contribution__inner">
				<button class="rev-gen-contribution__button"><span<?php echo ( $is_preview ) ? ' contenteditable="true" data-bind="button_label"' : ''; ?>><?php echo esc_html( $button_label ); ?></span></button>
			</div>
		</div>
	</div>

	<div class="rev-gen-preview__section">
		<h3 class="rev-gen-preview__section-title"><?php esc_html_e( 'Pop-up preview', 'revenue-generator' ); ?></h3>

		<?php include REVENUE_GENERATOR_PLUGIN_DIR . '/v1/templates/frontend/contribution/dialog-box.php'; ?>
	</div>
</div>

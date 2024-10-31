<?php
/**
 * Revenue Generator paywall removal modal template.
 *
 * @package revenue-generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file.
	exit;
}
?>
<!-- Template for paywall removal confirmation. -->
<script type="text/template" id="tmpl-rg-modal-remove-paywall">
	<div class="rev-gen-modal" id="rg-modal-remove-paywall">
		<div class="rev-gen-modal__inner">
			<h4 class="rev-gen-modal__title">
				<?php esc_html_e( 'Are you sure you want to remove the paywall?', 'revenue-generator' ); ?>
			</h4>
			<p class="rev-gen-modal__message">
				<?php esc_html_e( 'This content will be visible to all users.', 'revenue-generator' ); ?>
			</p>
			<div class="rev-gen-modal__buttons">
				<button id="rg_js_modal_confirm" class="rev-gen__button">
					<?php esc_html_e( 'Yes, remove Paywall', 'revenue-generator' ); ?>
				</button>
				<button id="rg_js_modal_cancel" class="rev-gen__button rev-gen__button--secondary">
					<?php esc_html_e( 'No, keep Paywall', 'revenue-generator' ); ?>
				</button>
			</div>
		</div>
	</div>
	<div class="rev-gen-modal-overlay"></div>
</script>

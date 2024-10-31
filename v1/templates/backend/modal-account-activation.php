<?php
/**
 * Revenue Generator account activation modal template.
 *
 * @package revenue-generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file.
	exit;
}
?>
<!-- Template for account activation modal. -->
<script type="text/template" id="tmpl-rg-modal-account-activation">
	<div class="rev-gen-modal" id="rg-modal-account-activation">
		<div class="rev-gen-modal__inner">
			<a href="#" class="rev-gen-modal__close" id="rg_js_modal_close">x</a>

			<?php if ( false === $is_merchant_verified ) : ?>
				<h4 class="rev-gen-modal__title">
					<?php esc_html_e( 'You’re almost done!', 'revenue-generator' ); ?>
				</h4>
				<p class="rev-gen-modal__message">
					<?php esc_html_e( 'To make sure you get your revenues, we need you to connect your account.', 'revenue-generator' ); ?>
				</p>
				<div class="rev-gen-modal__buttons">
					<button id="rg_js_modal_confirm" class="rev-gen__button">
						<?php esc_html_e( 'Connect Account', 'revenue-generator' ); ?>
					</button>
					<button id="rg_js_modal_cancel" class="rev-gen__button rev-gen__button--secondary">
						<?php esc_html_e( 'Signup', 'revenue-generator' ); ?>
					</button>
				</div>
				<p>
					<a href="https://support.laterpay.net/what-is-laterpay/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn more', 'revenue-generator' ); ?></a>
				</p>
			<?php endif; ?>
		</div>
	</div>
	<div class="rev-gen-modal-overlay"></div>
</script>

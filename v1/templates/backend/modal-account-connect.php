<?php
/**
 * Connect account modal template.
 *
 * @package revenue-generator
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- Template for account activation modal. -->
<script type="text/template" id="tmpl-rg-modal-connect-account">
	<div class="rev-gen-modal rev-gen-modal--connect" id="rg-modal-connect-account">
		<div class="rev-gen-modal__inner">
			<a href="#" class="rev-gen-modal__close" id="rg_js_modal_close">x</a>

			<h4 class="rev-gen-modal__title hide-on-loading">
				<?php esc_html_e( 'Connect your account to activate paywall', 'revenue-generator' ); ?>
			</h4>
			<h4 class="rev-gen-modal__title show-on-loading">
				<?php esc_html_e( 'Just a second…', 'revenue-generator' ); ?>
			</h4>
			<p class="rev-gen-modal__message hide-on-loading">
				<?php esc_html_e( 'Unsure where to find this information?', 'revenue-generator' ); ?>
				<a target="_blank" rel="noopener noreferrer" href="https://support.laterpay.net/what-is-my-laterpay-merchant-id-api-key-and-where-can-i-find-them">
					<?php esc_html_e( 'Click here.', 'revenue-generator' ); ?>
				</a>
			</p>

			<div class="rev-gen-modal__fields hide-on-loading">
				<input id="rev-gen-merchant-id" type="text" placeholder="<?php esc_attr_e( 'Merchant ID', 'revenue-generator' ); ?>" maxlength="22" />
				<input id="rev-gen-api-key" type="text" placeholder="<?php esc_attr_e( 'API Key', 'revenue-generator' ); ?>" maxlength="32" />
			</div>

			<div class="rev-gen-modal__buttons hide-on-loading">
				<button disabled="disabled" id="rg_js_modal_confirm" class="rev-gen__button">
					<?php esc_html_e( 'Connect Account', 'revenue-generator' ); ?>
				</button>
				<p>
					<?php esc_html_e( 'Don’t have an account?', 'revenue-generator' ); ?>
					<a id="rg_js_activateSignup" href="#"><?php esc_html_e( 'Signup', 'revenue-generator' ); ?></a>
				</p>
			</div>
			<div class="rev-gen-modal__error">
				<h4 class="rev-gen-modal__title"><?php esc_html_e( 'Sorry, something went wrong.', 'revenue-generator' ); ?></h4>
				<span class="rev-gen-modal__icon rev-gen-modal__icon--warning">!</span>
				<p class="rev-gen-modal__message">
					<?php
					echo wp_kses(
						sprintf(
							/* translators: %1$s static anchor id to handle signup link %2$s statuc anchor id to handle re verification. */
							__(
								'It looks like you need to create an account. Please <a id="%1$s" href="#">sign up here</a>, <a id="%2$s" href="#">try again</a>, or contact <a href="mailto:integration@laterpay.net">integration@laterpay.net</a> if you’re still experiencing difficulties.',
								'revenue-generator'
							),
							'rg_js_warningSignup',
							'rg_js_restartVerification'
						),
						[
							'a' => [
								'id'   => [],
								'href' => [],
							],
						]
					);
					?>
				</p>
			</div>
			<div class="rev-gen-modal__loader"></div>
		</div>
	</div>
	<div class="rev-gen-modal-overlay"></div>
</script>

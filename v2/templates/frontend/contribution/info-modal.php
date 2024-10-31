<?php
/**
 * Contribution info modal template.
 *
 * @package revenue-generator
 */

 defined( 'ABSPATH' ) || exit;
?>

<div class="rev-gen-contribution__question-mark"<?php echo ( $is_amp ) ? ' on="tap:' . esc_attr( $html_id ) . '_info-modal.show"' : ''; ?>>
	<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="question" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="#ffffff" d="M202.021 0C122.202 0 70.503 32.703 29.914 91.026c-7.363 10.58-5.093 25.086 5.178 32.874l43.138 32.709c10.373 7.865 25.132 6.026 33.253-4.148 25.049-31.381 43.63-49.449 82.757-49.449 30.764 0 68.816 19.799 68.816 49.631 0 22.552-18.617 34.134-48.993 51.164-35.423 19.86-82.299 44.576-82.299 106.405V320c0 13.255 10.745 24 24 24h72.471c13.255 0 24-10.745 24-24v-5.773c0-42.86 125.268-44.645 125.268-160.627C377.504 66.256 286.902 0 202.021 0zM192 373.459c-38.196 0-69.271 31.075-69.271 69.271 0 38.195 31.075 69.27 69.271 69.27s69.271-31.075 69.271-69.271-31.075-69.27-69.271-69.27z" class=""></path></svg>
</div>
<div class="rev-gen-contribution__info-modal rev-gen-contribution-info-modal<?php echo ( ! $is_amp ) ? ' rev-gen-hidden' : ''; ?>" id="<?php echo esc_attr( $html_id ); ?>_info-modal"<?php echo ( $is_amp ) ? ' hidden' : ''; ?>>
	<div class="rev-gen-contribution-info-modal-inner">
		<div class="rev-gen-contribution-info-modal__x-mark"<?php echo ( $is_amp ) ? ' on="tap:' . esc_attr( $html_id ) . '_info-modal.hide"' : ''; ?>>
			<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="times" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 352 512"><path fill="#ffffff" d="M242.72 256l100.07-100.07c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 75.93 89.21c-12.28-12.28-32.19-12.28-44.48 0L9.21 111.45c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.21 356.07c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.2 12.28 44.48 0L176 322.72l100.07 100.07c12.28 12.28 32.2 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z" class=""></path></svg>
		</div>
		<p class="rev-gen-contribution-info-modal__title"><?php esc_html_e( 'Contribute with My Tab', 'revenue-generator' ); ?></p>

		<div class="rev-gen-contribution-info-modal__steps">
			<p class="rev-gen-contribution-info-modal__step"><span class="rev-gen-contribution-info-modal__step-number">1</span> <span class="rev-gen-contribution-info-modal__step-text"><?php esc_html_e( 'Contribute', 'revenue-generator' ); ?></span></p>
			<p class="rev-gen-contribution-info-modal__step"><span class="rev-gen-contribution-info-modal__step-number">2</span> <span class="rev-gen-contribution-info-modal__step-text"><?php esc_html_e( 'Add to Tab', 'revenue-generator' ); ?></span></p>
			<p class="rev-gen-contribution-info-modal__step"><span class="rev-gen-contribution-info-modal__step-number">3</span> <span class="rev-gen-contribution-info-modal__step-text"><?php esc_html_e( 'Pay at 5$', 'revenue-generator' ); ?></span></p>
		</div>

		<div class="rev-gen-contribution-info-modal__description">
			<p><?php _e( 'Laterpay makes it possible for you to <b>contribute</b> without having to pay upfront.', 'revenue-generator' ); // phpcs:ignore -- WordPress.Security.EscapeOutput.UnsafePrintingFunction ?></p>

			<p><?php _e( 'Use your <b>Tab</b> to support your favorite artists.','revenue-generator' ); // phpcs:ignore -- WordPress.Security.EscapeOutput.UnsafePrintingFunction ?></p>

			<p><?php _e( 'As soon as you have <b>accrued a total of $5</b>, we will ask you to settle your Tab.', 'revenue-generator' ); // phpcs:ignore -- WordPress.Security.EscapeOutput.UnsafePrintingFunction ?></p>

			<p><?php _e( 'A <b>new Tab</b> will start automatically, so you can continue supporting and pay for it later.', 'revenue-generator' ); // phpcs:ignore -- WordPress.Security.EscapeOutput.UnsafePrintingFunction ?></p>
		</div>
	</div>
</div>

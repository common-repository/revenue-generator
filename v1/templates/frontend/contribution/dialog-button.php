<?php
/**
 * Revenue Generator Contribution Short code Screen.
 *
 * @package revenue-generator
 */

use LaterPay\Revenue_Generator\Inc\View;

if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file.
	exit;
}
?>

<?php if ( ! $is_preview ) : ?>
<div class="rev-gen-contribution rev-gen-contribution--button is-style-wide<?php echo ( $is_amp ) ? ' is-amp' : ''; ?>" id="<?php echo esc_attr( $html_id ); ?>" data-type="button">
	<div class="rev-gen-contribution__inner">
		<?php if ( ! $is_amp ) : ?>
			<button class="rev-gen-contribution__button"<?php echo ( $is_preview ) ? ' contenteditable="true" data-bind="dialog_header"' : ''; ?>><?php echo esc_html( $button_label ); ?></button>
		<?php else : ?>
			<button class="rev-gen-contribution__button" on="tap:<?php echo esc_attr( $html_id ); ?>_modal"><?php echo esc_html( $button_label ); ?></button>
		<?php endif; ?>
	</div>
	<?php if ( ! $is_amp ) : ?>
	<div class="rev-gen-contribution-modal" id="<?php echo esc_attr( $html_id ); ?>_modal">
		<div class="rev-gen-contribution rev-gen-contribution--box">
			<div class="rev-gen-contribution__inner">
				<button class="rev-gen-contribution-modal__close">
					<span class="screen-reader-text"><?php esc_html_e( 'Close modal', 'revenue-generator' ); ?></span>
				</button>
				<h2 class="rev-gen-contribution__title"<?php echo ( $is_preview ) ? ' contenteditable="true" data-bind="dialog_header"' : ''; ?>><?php echo esc_html( $dialog_header ); ?></h2>
				<div class="rev-gen-contribution__description rev-gen-contribution-tooltip-right"<?php echo ( $is_preview ) ? ' contenteditable="true" data-bind="dialog_description"' : ''; ?>><?php echo esc_html( $dialog_description ); ?></div>
				<?php include REVENUE_GENERATOR_PLUGIN_DIR . '/v1/templates/frontend/contribution/partial-donate.php'; ?>
				<?php include REVENUE_GENERATOR_PLUGIN_DIR . '/v1/templates/frontend/contribution/partial-custom.php'; ?>
				<div class="rev-gen-contribution__tip rev-gen-hidden">
					<?php esc_html_e( 'Contribute Now, Pay Later with your Tab', 'revenue-generator' ); ?>
				</div>
			</div>
		</div>
	</div>
	<?php else : ?>
	<amp-lightbox class="rev-gen-contribution-modal" id="<?php echo esc_attr( $html_id ); ?>_modal" layout="nodisplay">
		<div class="rev-gen-contribution-amp-wrap">
			<div class="rev-gen-contribution rev-gen-contribution--box">
				<div class="rev-gen-contribution__inner">
					<button class="rev-gen-contribution-modal__close" on="tap:<?php echo esc_attr( $html_id ); ?>_modal.close">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal', 'revenue-generator' ); ?></span>
					</button>
					<h2 class="rev-gen-contribution__title"<?php echo ( $is_preview ) ? ' contenteditable="true" data-bind="dialog_header"' : ''; ?>><?php echo esc_html( $dialog_header ); ?></h2>
					<div class="rev-gen-contribution__description rev-gen-contribution-tooltip-right"<?php echo ( $is_preview ) ? ' contenteditable="true" data-bind="dialog_description"' : ''; ?>><?php echo esc_html( $dialog_description ); ?></div>
					<?php include REVENUE_GENERATOR_PLUGIN_DIR . '/v1/templates/frontend/contribution/partial-donate.php'; ?>
					<?php include REVENUE_GENERATOR_PLUGIN_DIR . '/v1/templates/frontend/contribution/partial-custom.php'; ?>
				</div>
			</div>
		</div>
	</amp-lightbox>
	<?php endif; ?>
</div>
<?php else : ?>
	<?php include REVENUE_GENERATOR_PLUGIN_DIR . '/v1/templates/frontend/contribution/dialog-button-preview.php'; ?>
<?php endif; ?>

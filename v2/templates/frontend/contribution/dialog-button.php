<?php
/**
 * Revenue Generator Contribution Short code Screen.
 *
 * @package revenue-generator
 */

use \LaterPay\Revenue_Generator\Inc\View;

if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file.
	exit;
}
?>

<div class="rev-gen-contribution rev-gen-contribution--button is-style-wide<?php echo ( $is_amp ) ? ' is-amp' : ''; ?>" id="<?php echo esc_attr( $html_id ); ?>" data-step="default" data-type="button">
	<div class="rev-gen-contribution__inner">
		<?php if ( ! $is_amp ) : ?>
			<button class="rev-gen-contribution__button"><?php echo esc_html( $dialog_header ); ?></button>
		<?php else : ?>
			<button class="rev-gen-contribution__button" on="tap:<?php echo esc_attr( $html_id ); ?>_modal"><?php echo esc_html( $dialog_header ); ?></button>
		<?php endif; ?>
	</div>
	<?php if ( ! $is_amp ) : ?>
	<div class="rev-gen-contribution-modal" id="<?php echo esc_attr( $html_id ); ?>_modal">
		<div class="rev-gen-contribution rev-gen-contribution--box" data-contribution-id="<?php echo esc_attr( $contribution_id ); ?>">
			<?php include REVENUE_GENERATOR_PLUGIN_DIR . '/v2/templates/frontend/contribution/info-modal.php'; ?>
			<div class="rev-gen-contribution__inner">
				<button class="rev-gen-contribution-modal__close">
					<span class="screen-reader-text"><?php esc_html_e( 'Close modal', 'revenue-generator' ); ?></span>
				</button>
				<div class="rev-gen-contribution__choose">
					<h2 class="rev-gen-contribution__title"><?php echo esc_html( $dialog_header ); ?></h2>
					<?php include REVENUE_GENERATOR_PLUGIN_DIR . '/v2/templates/frontend/contribution/partial-amounts.php'; ?>
				</div>
				<?php include REVENUE_GENERATOR_PLUGIN_DIR . '/v2/templates/frontend/contribution/tab-status.php'; ?>
			</div>
		</div>
	</div>
	<?php else : ?>
	<amp-lightbox class="rev-gen-contribution-modal" id="<?php echo esc_attr( $html_id ); ?>_modal" layout="nodisplay">
		<div class="rev-gen-contribution-amp-wrap">
			<div class="rev-gen-contribution rev-gen-contribution--box"<?php echo ( $is_amp ) ? ' [class]="submitted == ' . esc_attr( $contribution_id ) . ' ? \'rev-gen-contribution rev-gen-contribution--box is-amp amp-submitted\' : \'rev-gen-contribution rev-gen-contribution--box is-amp\'"' : ''; ?>>
				<?php include REVENUE_GENERATOR_PLUGIN_DIR . '/v2/templates/frontend/contribution/info-modal.php'; ?>
				<div class="rev-gen-contribution__inner">
					<button class="rev-gen-contribution-modal__close" on="tap:<?php echo esc_attr( $html_id ); ?>_modal.close">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal', 'revenue-generator' ); ?></span>
					</button>
					<div class="rev-gen-contribution__choose">
						<h2 class="rev-gen-contribution__title"><?php echo esc_html( $dialog_header ); ?></h2>
						<?php include REVENUE_GENERATOR_PLUGIN_DIR . '/v2/templates/frontend/contribution/partial-amounts.php'; ?>
					</div>
					<?php include REVENUE_GENERATOR_PLUGIN_DIR . '/v2/templates/frontend/contribution/tab-status.php'; ?>
				</div>
			</div>
		</div>
	</amp-lightbox>
	<?php endif; ?>
</div>

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

$breakpoints = [
	'large' => 1080,
];
?>
<div class="rev-gen-contribution rev-gen-contribution--bar is-style-wide<?php echo ( $is_amp ) ? ' is-amp' : ''; ?>" id="<?php echo esc_attr( $html_id ); ?>" data-step="default" data-type="bar" data-breakpoints="<?php echo esc_attr( json_encode( $breakpoints ) ); ?>" data-contribution-id="<?php echo esc_attr( $contribution_id ); ?>"<?php echo ( $is_amp ) ? ' [class]="submitted == ' . esc_attr( $contribution_id ) . ' ? \'rev-gen-contribution rev-gen-contribution--bar is-style-wide is-amp amp-submitted\' : \'rev-gen-contribution rev-gen-contribution--bar is-style-wide is-amp\'"' : ''; ?>>
	<?php include REVENUE_GENERATOR_PLUGIN_DIR . '/v2/templates/frontend/contribution/info-modal.php'; ?>
	<div class="rev-gen-contribution__inner">
		<div class="rev-gen-contribution__choose">
			<h2 class="rev-gen-contribution__title"><?php echo esc_html( $dialog_header ); ?></h2>
			<?php include REVENUE_GENERATOR_PLUGIN_DIR . '/v2/templates/frontend/contribution/partial-amounts.php'; ?>
			<?php include REVENUE_GENERATOR_PLUGIN_DIR . '/v2/templates/frontend/contribution/partial-footer.php'; ?>
		</div>
		<?php include REVENUE_GENERATOR_PLUGIN_DIR . '/v2/templates/frontend/contribution/tab-status.php'; ?>
	</div>
</div>

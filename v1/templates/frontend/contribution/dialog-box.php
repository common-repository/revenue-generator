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
<div class="rev-gen-contribution rev-gen-contribution--box is-style-wide<?php echo ( $is_amp ) ? ' is-amp' : ''; ?>" id="<?php echo esc_attr( $html_id ); ?>" data-type="box">
	<div class="rev-gen-contribution__inner">
		<h2 class="rev-gen-contribution__title"<?php echo ( $is_preview ) ? ' contenteditable="true" data-bind="dialog_header"' : ''; ?>><?php echo esc_html( $dialog_header ); ?></h2>
		<div class="rev-gen-contribution__description rev-gen-contribution-tooltip-right"<?php echo ( $is_preview ) ? ' contenteditable="true" data-bind="dialog_description"' : ''; ?>><?php echo esc_html( $dialog_description ); ?></div>
		<?php include REVENUE_GENERATOR_PLUGIN_DIR . '/v1/templates/frontend/contribution/partial-donate.php'; ?>
		<?php include REVENUE_GENERATOR_PLUGIN_DIR . '/v1/templates/frontend/contribution/partial-custom.php'; ?>
		<div class="rev-gen-contribution__tip rev-gen-hidden">
			<?php esc_html_e( 'Contribute Now, Pay Later with your Tab', 'revenue-generator' ); ?>
		</div>
	</div>
</div>

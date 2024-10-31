<?php
/**
 * Donation box contribution partial template.
 *
 * @package revenue-generator
 */

use LaterPay\Revenue_Generator\Inc\View;

defined( 'ABSPATH' ) || exit;
?>
<div class="rev-gen-contribution__donate" id="<?php echo esc_attr( $html_id ); ?>_donate">
	<?php
	foreach ( $payment_config['amounts'] as $amount_info ) {
		if ( true === $amount_info['selected'] ) {
			$selected_button = true;
		} else {
			$selected_button = false;
		}
		$lp_amount = View::format_number( floatval( $amount_info['amount'] / 100 ), 2 );

		if ( ! $is_preview ) {
			$lp_amount = $currency_symbol . $lp_amount;
		}
		?>
		<div data-href="<?php echo esc_url( $amount_info['url'] ); ?>" data-revenue="<?php echo esc_attr( $amount_info['revenue'] ); ?>" data-campid="<?php echo esc_attr( $campaign_id ); ?>" class="rev-gen-contribution__donation">
			<?php if ( $is_preview ) : ?>
				<?php echo esc_html( $currency_symbol ); ?><span contenteditable="true" data-bind="amounts"><?php echo esc_html( $lp_amount ); ?></span>
			<?php else : ?>
				<a href="<?php echo esc_url( $amount_info['url'] ); ?>" target="_blank"><?php echo esc_html( $lp_amount ); ?></a>
			<?php endif; ?>
		</div>
		<?php
	}
	?>
	<div class="rev-gen-contribution__donation rev-gen-contribution__donation--custom" on="tap:<?php echo esc_attr( $html_id ); ?>_donate.toggleVisibility,<?php echo esc_attr( $html_id ); ?>_custom.toggleVisibility">
		<button><?php esc_html_e( 'Custom', 'revenue-generator' ); ?></button>
	</div>
</div>

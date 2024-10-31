<?php
/**
 * Revenue Generator admin dashboard screen.
 *
 * @package revenue-generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file.
	exit;
}

$paywall_data          = isset( $purchase_options_data['paywall'] ) ? $purchase_options_data['paywall'] : [];
$purchase_option_items = empty( $purchase_options_data['options'] ) ? [] : $purchase_options_data['options'];
$rg_is_paywall_active  = 1 === absint( $paywall_data['is_active'] ) ? true : false;
?>

<div class="rev-gen-dashboard-content-paywall-preview <?php echo ! $rg_is_paywall_active ? 'is-disabled' : ''; ?>">
	<div class="rev-gen-dashboard-content-paywall-preview-title"><?php echo esc_html( $paywall_data['title'] ); ?></div>
	<div class="rev-gen-dashboard-content-paywall-preview-description hide"><?php echo esc_html( $paywall_data['description'] ); ?></div>
	<div class="rev-gen-dashboard-content-paywall-preview-purchase-options">
		<?php
		if ( ! empty( $purchase_option_items ) ) :
			foreach ( $purchase_option_items as $purchase_option ) {
				$purchase_option_price = number_format( $purchase_option['price'], 2 );
				?>
				<div class="rev-gen-dashboard-content-paywall-preview-purchase-options-item">
					<div class="rev-gen-dashboard-content-paywall-preview-purchase-options-item-info">
						<div class="rev-gen-dashboard-content-paywall-preview-purchase-options-item-info-title">
							<?php echo esc_html( $purchase_option['title'] ); ?>
						</div>
						<div class="rev-gen-dashboard-content-paywall-preview-purchase-options-item-info-description">
							<?php echo esc_html( $purchase_option['description'] ); ?>
						</div>
					</div>
					<div class="rev-gen-dashboard-content-paywall-preview-purchase-options-item-price">
						<span class="rev-gen-dashboard-content-paywall-preview-purchase-options-item-price-symbol"><?php echo esc_html( $merchant_symbol ); ?></span>
						<span class="rev-gen-dashboard-content-paywall-preview-purchase-options-item-price-span">
							<?php echo esc_html( $purchase_option_price ); ?>
						</span>
					</div>
				</div>
				<?php
			}
		endif;
		?>
	</div>
</div>

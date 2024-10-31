<?php
/**
 * Template for Paywall Preview.
 *
 * @package revenue-generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file.
	exit;
}

if ( ! empty( $paywall_data ) && ! empty( $payload ) ) :
	?>
	<div class="rg-post-preview--container">
		<h4 class="rg-post-preview--title">
			<?php echo esc_html( $paywall_data['title'] ); ?>
		</h4>
		<p class="rg-post-preview--updated"><?php echo esc_html( $paywall_data['updated'] ); ?></p>
		<?php if ( ! empty( $payload ) ) : ?>
		<div class="rg-post-preview--purchase-container">
			<?php
			foreach ( $payload as $payload_data ) :
				?>
				<div class="rg-post-preview--purchase-option">
					<div class="rg-post-preview--purchase-option-title">
						<?php echo esc_html( $payload_data['title'] ); ?>
					</div>
					<div class="rg-post-preview--purchase-option-amount">
						<?php echo esc_html( $payload_data['amount'] ); ?>
					</div>
				</div>
				<?php
			endforeach;
			?>
			<div class="rg-post-preview--edit">
				<a target="_blank" class="rg-post-preview--button-black" href="<?php echo esc_url( $edit_paywall_url ); ?>"><?php esc_html_e( 'Edit', 'revenue-generator' ); ?></a>
			</div>
		</div>
		<?php endif; ?>
	</div>
	<?php
else :
	?>
	<div class="rg-post-preview--container">
		<div class="rg-post-preview--edit">
			<a target="_blank" href="<?php echo esc_url( $new_paywall_url ); ?>" class="rg-post-preview--button-black"><?php esc_html_e( 'Add New Paywall', 'revenue-generator' ); ?></a>
		</div>
	</div>
	<?php
endif;

<?php
/**
 * Revenue Generator post preivew screen with Paywall.
 *
 * @package revenue-generator
 */

use LaterPay\Revenue_Generator\Inc\View;
use LaterPay\Revenue_Generator\Inc\Post_Types;
use LaterPay\Revenue_Generator\Inc\Config;

if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file.
	exit;
}

$rg_teaser = '';

// Create data for view.
if ( ! empty( $rg_preview_post['excerpt'] ) ) {
	$rg_teaser = $rg_preview_post['excerpt'];
} elseif ( ! empty( $rg_preview_post['teaser'] ) ) {
	$rg_teaser = $rg_preview_post['teaser'];
}

$paywall_data       = isset( $purchase_options_data['paywall'] ) ? $purchase_options_data['paywall'] : [];
$paywall_id         = empty( $paywall_data['id'] ) ? '' : $paywall_data['id'];
$paywall_preview_id = empty( $paywall_data['preview_id'] ) ? '' : $paywall_data['preview_id'];
if ( empty( $paywall_data ) && ! empty( $rg_preview_post['ID'] ) ) {
	$paywall_preview_id = $rg_preview_post['ID'];
}
$paywall_access_to       = isset( $paywall_data['access_to'] ) ? $paywall_data['access_to'] : 'all';
$purchase_option_items   = empty( $purchase_options_data['options'] ) ? [] : $purchase_options_data['options'];
$rg_preview_post_title   = empty( $rg_preview_post['title'] ) ? '' : $rg_preview_post['title'];
$dynamic_pricing_price   = $dynamic_pricing_data['price'];
$dynamic_pricing_revenue = $dynamic_pricing_data['revenue'];
$paywall_hide_class      = ( 'publish' === get_post_status( $paywall_id ) ) ? 'hide' : '';
?>

<div class="rev-gen-layout-wrapper">
	<div class="laterpay-loader-wrapper">
		<img alt="<?php esc_attr_e( 'Contribute.to Logo', 'revenue-generator' ); ?>" src="<?php echo esc_url( $action_icons['lp_icon'] ); ?>" />
	</div>
	<div class="rev-gen-preview-main">
		<div class="rev-gen-preview-main--search" data-tippy-content="<?php esc_attr_e( 'Search for the page or post you\'d like to preview with Revenue Generator here.', 'revenue-generator' ); ?>">
			<?php if ( ! empty( $rg_preview_post_title ) ) : ?>
				<label for="rg_js_searchContent"><?php esc_html_e( 'Previewing', 'revenue-generator' ); ?>:</label>
			<?php endif; ?>
			<input type="text" id="rg_js_searchContent" placeholder="<?php esc_attr_e( 'search for the page or post you\'d like to preview here', 'revenue-generator' ); ?>" value="<?php echo esc_attr( $rg_preview_post_title ); ?>" />
			<input type="hidden" id="rg_currentPaywall" value="<?php echo esc_attr( $paywall_id ); ?>" />
			<i class="rev-gen-preview-main--search-icon"></i>
			<div class="rev-gen-preview-main--search-results"></div>
		</div>
		<?php if ( ! empty( $rg_preview_post ) ) : ?>
			<div id="rg_js_postPreviewWrapper" data-preview-id="<?php echo esc_attr( $paywall_preview_id ); ?>" data-access-id="<?php echo esc_attr( $rg_preview_post['ID'] ); ?>" class="rev-gen-preview-main--post">
				<h4 class="rev-gen-preview-main--post--title"><?php echo esc_html( $rg_preview_post['title'] ); ?></h4>
				<?php if ( ! empty( $rg_teaser ) ) : ?>
					<p id="rg_js_postPreviewExcerpt" class="rev-gen-preview-main--post--excerpt"><?php echo wp_kses_post( $rg_teaser ); ?></p>
				<?php endif; ?>
				<div id="rg_js_postPreviewContent" class="rev-gen-preview-main--post--content">
					<?php echo wp_kses_post( $rg_preview_post['post_content'] ); ?>
				</div>
				<div class="rg-purchase-overlay" id="rg_js_purchaseOverlay">
					<div class="rg-purchase-overlay-highlight"></div>
					<button class="rg-purchase-overlay-remove">
						<img alt="<?php echo esc_attr( 'Paywall Remove', 'revenue-generator' ); ?>" src="<?php echo esc_url( $action_icons['option_remove'] ); ?>" />
					</button>
				</div>
			</div>
			<div class="rev-gen-preview-main--paywall-actions">
				<div class="rev-gen-preview-main--paywall-actions-wrap">
					<div class="rev-gen-preview-main--paywall-actions-apply">
							<?php
							$paywall_name = ! empty( $paywall_data['name'] ) ? $paywall_data['name'] : $default_paywall_title;
							echo wp_kses(
								sprintf(
									/* translators: %s Paywall name */
									__( 'Apply <span contenteditable="true" class="rev-gen-preview-main-paywall-name">%s</span> to', 'revenue-generator' ),
									$paywall_name
								),
								[
									'span' => [
										'class'           => [],
										'contenteditable' => true,
									],
								]
							);
							?>
						<?php
							/* translators: %1s post type. */
							$supported_label = ( ! empty( $rg_preview_post['type'] ) ) ? sprintf( __( 'this %1s only', 'revenue-generator' ), esc_html( $rg_preview_post['type'] ) ) : __( 'selected post or page', 'revenue-generator' );
						?>
						<select class="rev-gen-preview-main-paywall-applies-to rev-gen__select2 rev-gen__select2--arrow-up rev-gen__select2--no-search">
							<option <?php selected( $paywall_access_to, 'all', true ); ?> value="all"><?php esc_html_e( 'all posts and pages', 'revenue-generator' ); ?></option>
							<option <?php selected( $paywall_access_to, 'posts', true ); ?> value="posts"><?php esc_html_e( 'all posts', 'revenue-generator' ); ?></option>
							<?php
							$allowed_post_types = Post_Types::get_allowed_post_types();

							foreach ( $allowed_post_types as $post_type_slug ) {
								if ( in_array( $post_type_slug, [ 'post', 'page' ] ) ) {
									continue;
								}

								$allowed_post_type = Post_Types::get_post_type_object( $post_type_slug );

								if ( empty( $allowed_post_type ) ) {
									continue;
								}
								?>
							<option <?php selected( $paywall_access_to, $allowed_post_type->name, true ); ?> value="<?php echo esc_attr( $allowed_post_type->name ); ?>"><?php echo esc_html_e( 'all', 'revenue-generator' ); ?> <?php echo esc_attr( strtolower( $allowed_post_type->label ) ); ?></option>
							<?php } ?>
							<option <?php selected( $paywall_access_to, 'category', true ); ?> value="category"><?php esc_html_e( 'category', 'revenue-generator' ); ?></option>
							<option <?php selected( $paywall_access_to, 'exclude_category', true ); ?> value="exclude_category"><?php esc_html_e( 'except for category', 'revenue-generator' ); ?></option>
							<option <?php selected( $paywall_access_to, 'specific_post', true ); ?> value="specific_post"><?php esc_html_e( 'specific posts or pages', 'revenue-generator' ); ?></option>
							<option <?php selected( $paywall_access_to, 'supported', true ); ?> value="supported"><?php echo esc_html( $supported_label ); ?></option>
						</select>
					</div>
					<div class="rev-gen-preview-main--paywall-actions__search rev-gen-preview-main--paywall-actions-search">
						<select id="rg_js_searchPaywallContent" class="rev-gen__select2 rev-gen__select2--searchable" multiple="multiple">
							<?php if ( ! empty( $rg_categories_data ) && is_array( $rg_categories_data ) ) : ?>
								<?php foreach ( $rg_categories_data as $rg_category_data ) : ?>
									<option selected="selected" value="<?php echo esc_attr( $rg_category_data->term_id ); ?>">
										<?php echo esc_html( $rg_category_data->name ); ?>
									</option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
						<i class="rev-gen-preview-main--paywall-actions__search-icon"></i>
					</div>
					<div class="rev-gen-preview-main--paywall-actions__search rev-gen-preview-main--paywall-actions-search-post">
						<select id="rg_js_searchPost" class="rev-gen__select2 rev-gen__select2--searchable" name="posts[]" multiple="multiple">
							<?php if ( ! empty( $rg_specific_posts ) ) : ?>
								<?php foreach ( $rg_specific_posts as $rg_specific_post_id => $rg_specific_post_title ) : ?>
									<option selected="selected" value="<?php echo esc_attr( $rg_specific_post_id ); ?>">
										<?php echo esc_html( $rg_specific_post_title ); ?>
									</option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
						<i class="rev-gen-preview-main--paywall-actions__search-icon"></i>
					</div>
				</div>
				<div class="rev-gen-preview-main--paywall-actions-update">
					<button id="rg_js_savePaywall" class="rev-gen-preview-main--paywall-actions-update-save <?php echo esc_attr( sanitize_html_class( $paywall_hide_class ) ); ?>">
						<?php esc_html_e( 'Save Draft', 'revenue-generator' ); ?>
					</button>
					<button id="rg_js_activatePaywall" class="rev-gen-preview-main--paywall-actions-update-publish">
						<?php esc_html_e( 'Publish', 'revenue-generator' ); ?>
					</button>
				</div>
			</div>
		<?php else : ?>
			<div class="rev-gen-preview-main-no-result">
				<img class="rg-card--icon" alt="<?php esc_attr_e( 'More posts icon', 'revenue-generator' ); ?>" src="<?php echo esc_url( $action_icons['high_count_icon'] ); ?>">
			</div>
		<?php endif; ?>
	</div>
	<div id="rg_js_SnackBar" class="rev-gen-snackbar"></div>
	<a href="https://wordpress.org/support/plugin/revenue-generator" target="_blank" class="rev-gen__button rev-gen__button--secondary rev-gen-email-support rev-gen__button--help"><?php esc_html_e( 'Email Support', 'revenue-generator' ); ?></a>
	<div class="rev-gen-exit-tour rev-gen__button rev-gen__button--secondary rev-gen__button--help"><?php esc_html_e( 'Exit Tour', 'revenue-generator' ); ?></div>
</div>

<!-- Template for purchase option manager actions -->
<script type="text/template" id="tmpl-revgen-purchase-overlay-actions">
	<div class="rg-purchase-overlay-purchase-options-item-actions">
		<button class="rg-purchase-overlay-option-edit">
			<img alt="<?php echo esc_attr( 'Option edit', 'revenue-generator' ); ?>" src="<?php echo esc_url( $action_icons['option_edit'] ); ?>" />
		</button>
	</div>
</script>

<!-- Template for purchase option manager -->
<script type="text/template" id="tmpl-revgen-purchase-overlay-item-manager">
	<div class="rg-purchase-overlay-option-manager">
		<div class="rg-purchase-overlay-option-manager-entity-selection">
			<select id="rg_js_purchaseOptionType" class="rg-purchase-overlay-option-manager-entity rev-gen__select2">
				<option
				<# data.entityType === 'individual' ? print("selected") : print('') #> value="individual"><?php esc_html_e( 'Individual Article', 'revenue-generator' ); ?></option>
				<option
				<# data.entityType === 'timepass' ? print("selected") : print('') #> value="timepass"><?php esc_html_e( 'Time Pass (for the entire site)', 'revenue-generator' ); ?></option>
				<option
				<# data.entityType === 'subscription' ? print("selected") : print('') #> value="subscription"><?php esc_html_e( 'Subscription (for the entire site)', 'revenue-generator' ); ?></option>
			</select>
		</div>
		<div class="rg-purchase-overlay-option-manager-revenue">
			<span class="pay-now"><?php esc_html_e( 'Pay Now', 'revenue-generator' ); ?></span>
			<label class="switch">
				<input class="rg-purchase-overlay-option-revenue-selection" type="checkbox">
				<span class="slider round"></span>
			</label>
			<span class="pay-later"><?php esc_html_e( 'Pay Later', 'revenue-generator' ); ?></span>
			<button data-info-for="revenue" id="revenue-info-modal" class="rg-purchase-overlay-option-info">
				<img src="<?php echo esc_url( $action_icons['option_info'] ); ?>">
			</button>
		</div>
		<div class="rg-purchase-overlay-option-manager-pricing">
			<span class="static-pricing"><?php esc_html_e( 'Static Pricing', 'revenue-generator' ); ?></span>
			<label class="switch">
				<input class="rg-purchase-overlay-option-pricing-selection" type="checkbox">
				<span class="slider round"></span>
			</label>
			<span class="dynamic-pricing"><?php esc_html_e( 'Dynamic Pricing', 'revenue-generator' ); ?></span>
			<button data-info-for="pricing"  id="pricing-info-modal" class="rg-purchase-overlay-option-info">
				<img src="<?php echo esc_url( $action_icons['option_info'] ); ?>"></button>
		</div>
		<div class="rg-purchase-overlay-option-manager-duration">
			<select class="rg-purchase-overlay-option-manager-duration-count rev-gen__select2">
				<?php
				echo wp_kses(
					Post_Types::get_select_options( 'duration' ),
					[
						'option' => [
							'selected' => [],
							'value'    => [],
						],
					]
				);
				?>
			</select>
			<select class="rg-purchase-overlay-option-manager-duration-period rev-gen__select2">
				<?php
				echo wp_kses(
					Post_Types::get_select_options( 'period' ),
					[
						'option' => [
							'selected' => [],
							'value'    => [],
						],
					]
				);
				?>
			</select>
		</div>
		<div class="rg-purchase-overlay-option-manager-action">
			<button class="rg-purchase-overlay-option-remove"><?php esc_html_e( 'Delete This Purchase Option', 'revenue-generator' ); ?></button>
		</div>
	</div>
</script>

<!-- Template for purchase overlay -->
<script type="text/template" id="tmpl-revgen-purchase-overlay">
	<div class="rg-purchase-overlay-title" contenteditable="true">
		<?php echo empty( $paywall_data['title'] ) ? esc_html__( 'Keep Reading', 'revenue-generator' ) : esc_html( $paywall_data['title'] ); ?>
	</div>
	<div class="rg-purchase-overlay-description hide">
		<?php echo empty( $paywall_data['description'] ) ? esc_html( sprintf( 'Support %s to get access to this content and more.', esc_url( get_home_url() ) ) ) : esc_html( $paywall_data['description'] ); ?>
	</div>
	<div class="rg-purchase-overlay-purchase-options"
		 data-paywall-id="<?php echo esc_attr( $paywall_id ); ?>"
		 data-dynamic-price="<?php echo esc_attr( $dynamic_pricing_price ); ?>"
		 data-dynamic-revenue="<?php echo esc_attr( $dynamic_pricing_revenue ); ?>"
	>
		<?php
		if ( ! empty( $purchase_option_items ) ) :
			foreach ( $purchase_option_items as $purchase_option ) {
				$purchase_option_id           = empty( $purchase_option['id'] ) ? '' : $purchase_option['id'];
				$purchase_option_price        = number_format( $purchase_option['price'], 2 );
				$purchase_option_type         = $purchase_option['purchase_type'];
				$is_individual                = 'individual' === $purchase_option_type;
				$individual_type              = '';
				$purchase_option_revenue      = $purchase_option['revenue'];
				$purchase_option_duration     = $is_individual ? '' : $purchase_option['duration'];
				$purchase_option_period       = $is_individual ? '' : $purchase_option['period'];
				$purchase_option_order        = $purchase_option['order'];
				$purchase_option_custom_title = ( ! empty( $purchase_option['custom_title'] ) ) ? $purchase_option['custom_title'] : '';
				$purchase_option_custom_desc  = ( ! empty( $purchase_option['custom_desc'] ) ) ? $purchase_option['custom_desc'] : '';

				$additional_class = '';
				if ( 2 === $purchase_option_order ) {
					$additional_class = 'option-item-second';
				}

				if ( $is_individual ) {
					$individual_type = empty( $purchase_option['type'] ) ? 'dynamic' : $purchase_option['type'];
				}
				?>
				<div
					class="rg-purchase-overlay-purchase-options-item <?php echo esc_attr( $additional_class ); ?>"
					data-purchase-type="<?php echo esc_attr( $purchase_option_type ); ?>"
					<?php if ( 'individual' !== $purchase_option_type ) : ?>
						data-expiry-duration="<?php echo esc_attr( $purchase_option_duration ); ?>"
						data-expiry-period="<?php echo esc_attr( $purchase_option_period ); ?>"
					<?php else : ?>
						data-pricing-type="<?php echo esc_attr( $individual_type ); ?>"
						data-paywall-id="<?php echo esc_attr( $paywall_id ); ?>"
					<?php endif; ?>
					<?php if ( 'timepass' === $purchase_option_type ) : ?>
						data-tlp-id="<?php echo esc_attr( $purchase_option_id ); ?>"
						<?php
					endif;
					if ( 'subscription' === $purchase_option_type ) :
						?>
						data-sub-id="<?php echo esc_attr( $purchase_option_id ); ?>"
					<?php endif; ?>
					data-uid=""
					data-order="<?php echo esc_attr( $purchase_option_order ); ?>"
					data-custom-title="<?php echo esc_attr( $purchase_option_custom_title ); ?>"
					data-custom-desc="<?php echo esc_attr( $purchase_option_custom_desc ); ?>"
				>
				<div class="rg-purchase-overlay-purchase-options-item-highlight"></div>
					<div class="rg-purchase-overlay-purchase-options-item-info">
						<div class="rg-purchase-overlay-purchase-options-item-info-title" contenteditable="true">
							<?php echo empty( $purchase_option['title'] ) ? esc_html__( 'Access Article Now', 'revenue-generator' ) : esc_html( $purchase_option['title'] ); ?>
						</div>
						<div class="rg-purchase-overlay-purchase-options-item-info-description" contenteditable="true">
							<?php echo empty( $purchase_option['description'] ) ? esc_html__( 'You\'ll only be charged once you\'ve reached $5.', 'revenue-generator' ) : esc_html( $purchase_option['description'] ); ?>
						</div>
					</div>
					<div class="rg-purchase-overlay-purchase-options-item-price">
						<span class="rg-purchase-overlay-purchase-options-item-price-span" data-pay-model="<?php echo esc_attr( $purchase_option_revenue ); ?>" contenteditable="true">
							<?php echo esc_html( $purchase_option_price ); ?>
						</span>
						<span class="rg-purchase-overlay-purchase-options-item-price-symbol"><sup><?php echo esc_html( $merchant_symbol ); ?></sup></span>
						<?php if ( ! empty( $individual_type ) ) : ?>
						<button data-tippy-content="<?php esc_attr_e( 'You’re using Dynamic Pricing. The revenue generator automatically assigns a price to each article this paywall is applied to based on the amount of content the article contains. The price for this specific article is shown here.', 'revenue-generator' ); ?>" class="rg-purchase-overlay-purchase-options-item-price-icon">
							<img alt="<?php esc_attr_e( 'Dynamic Option', 'revenue-generator' ); ?>" src="<?php echo esc_url( $action_icons['option_dynamic'] ); ?>" />
						</button>
						<?php endif; ?>
					</div>
				</div>
				<?php
			}
		endif;
		?>
	</div>
	<div class="rg-purchase-overlay-option-area" <?php echo ( ! empty( $purchase_option_items ) && 5 <= count( $purchase_option_items ) ) ? 'style="display:none;"' : ''; ?>>
		<div class="rg-purchase-overlay-option-area-add-option">
			<button>
				<img alt="<?php echo esc_attr( 'Option add', 'revenue-generator' ); ?>" src="<?php echo esc_url( $action_icons['option_add'] ); ?>" />
			</button>
			<hr />
			<span><?php esc_html_e( 'Add a new purchase option', 'revenue-generator' ); ?></span>
			<hr />
		</div>
	</div>
	<div class="rg-purchase-overlay-privacy">
		<p>
			<?php
			echo wp_kses(
				__(
					'By selecting an option above, I am confirming that I have read and agree to Laterpay\'s <a href="#">privacy policy</a> and <a href="#">terms of service</a>.',
					'revenue-generator'
				),
				[
					'a' => [],
				]
			);
			?>
		</p>
	</div>
	<a class="rg-purchase-overlay-already-bought" href="#"><?php esc_html__( 'I already bought this', 'revenue-generator' ); ?></a>
</script>

<!-- Template for currency confirmation modal -->
<script type="text/template" id="tmpl-rg-modal-choose-currency">
	<div class="rev-gen-modal" id="rg-modal-choose-currency">
		<div class="rev-gen-modal__inner">
			<a href="#" class="rev-gen-modal__close" id="rg_js_modal_close">x</a>

			<h4 class="rev-gen-modal__title">
				<?php esc_html_e( 'Choose your currency.', 'revenue-generator' ); ?>
			</h4>

			<span class="rev-gen-modal__icon rev-gen-modal__icon--warning">!</span>

			<p class="rev-gen-modal__message">
				<?php
				esc_html_e( 'In what currency do you sell content?', 'revenue-generator' );
				?>
			</p>

			<div class="rev-gen-modal__fields">
				<label>
					<input class="rev-gen-preview-main-currency-modal-inputs-currency" type="radio" name="currency" value="USD" />
					<?php esc_html_e( 'USD', 'revenue-generator' ); ?> $
				</label>
				<label>
					<input class="rev-gen-preview-main-currency-modal-inputs-currency" type="radio" name="currency" value="EUR" />
					<?php esc_html_e( 'EURO', 'revenue-generator' ); ?> €
				</label>
			</div>

			<div class="rev-gen-modal__buttons">
				<button id="rg_js_modal_confirm" class="rev-gen__button" disabled>
					<?php esc_html_e( 'Apply currency', 'revenue-generator' ); ?>
				</button>
			</div>
		</div>
	</div>
	<div class="rev-gen-modal-overlay"></div>
</script>

<!-- Template for option item -->
<script type="text/template" id="tmpl-revgen-default-purchase-option-item">
	<div
		class="rg-purchase-overlay-purchase-options-item"
		data-purchase-type="subscription"
		data-expiry-duration="<?php echo esc_attr( $default_option_data['duration'] ); ?>"
		data-expiry-period="<?php echo esc_attr( $default_option_data['period'] ); ?>"
		data-sub-id=""
		data-uid=""
		data-order=""
	>
		<div class="rg-purchase-overlay-purchase-options-item-info">
			<div class="rg-purchase-overlay-purchase-options-item-info-title" contenteditable="true">
				<?php echo esc_html( $default_option_data['title'] ); ?>
			</div>
			<div class="rg-purchase-overlay-purchase-options-item-info-description" contenteditable="true">
				<?php echo esc_html( $default_option_data['description'] ); ?>
			</div>
		</div>
		<div class="rg-purchase-overlay-purchase-options-item-price">
			<span class="rg-purchase-overlay-purchase-options-item-price-span" data-pay-model="<?php echo esc_attr( $default_option_data['revenue'] ); ?>" contenteditable="true">
				<?php echo esc_html( $default_option_data['price'] ); ?>
			</span>
			<span class="rg-purchase-overlay-purchase-options-item-price-symbol"><sup><?php echo esc_html( $merchant_symbol ); ?></sup></span>
		</div>
	</div>
</script>

<!-- Template for option update warning modal -->
<script type="text/template" id="tmpl-rg-modal-purchase-option-update">
	<div class="rev-gen-modal" id="rg-modal-purchase-option-update">
		<div class="rev-gen-modal__inner">
			<h4 class="rev-gen-modal__title">
				<?php esc_html_e( 'This will affect all your paywalls.', 'revenue-generator' ); ?>
			</h4>
			<span class="rev-gen-modal__icon rev-gen-modal__icon--warning">!</span>
			<p class="rev-gen-modal__message">
				<# if ( 'timepass' === data.optionType ) { #>
					<?php
					esc_html_e( 'The changes you have made will impact this time pass offer on all paywalls across your entire site.', 'revenue-generator' );
					?>
				<# } else if ( 'subscription' === data.optionType ) { #>
					<?php
					esc_html_e( 'The changes you have made will impact this subscription offer on all paywalls across your entire site.', 'revenue-generator' );
					?>
				<# } else { #>
					<?php
					esc_html_e( 'It looks like you have added a new Global Time Pass or Subscription to this Paywall. Global Time Passes and Subscriptions will show up on all paywalls across your entire site.', 'revenue-generator' );
					?>
				<# } #>
			</p>
			<div class="rev-gen-modal__buttons">
				<button id="rg_js_modal_confirm" class="rev-gen__button">
					<?php esc_html_e( 'Continue', 'revenue-generator' ); ?>
				</button>
				<button id="rg_js_modal_cancel" class="rev-gen__button rev-gen__button--secondary">
					<?php esc_html_e( 'Cancel', 'revenue-generator' ); ?>
				</button>
			</div>
		</div>
	</div>
	<div class="rev-gen-modal-overlay"></div>
</script>

<!-- Template for revenue info modal -->
<script type="text/template" id="tmpl-rg-modal-info-revenue">
	<div class="rev-gen-modal" id="rg-modal-info-revenue">
		<div class="rev-gen-modal__inner">
			<h4 class="rev-gen-modal__title">
				<?php esc_html_e( 'Pay Now v Pay Later?', 'revenue-generator' ); ?>
			</h4>
			<p class="rev-gen-modal__message">
				<?php
				esc_html_e( 'Choose the pricing model that best suits your needs!', 'revenue-generator' );
				?>
			</p>
			<p class="rev-gen-modal__message">
				<?php
				printf(
					wp_kses(
						__(
							'<b>Pay Later</b> means users agree to pay for content or timed access later - once their tab reaches $5 or 5€. Think of it as ‘the internet’s running tab.’',
							'revenue-generator'
						),
						[
							'b' => [],
						]
					)
				);
				?>
			</p>
			<p class="rev-gen-modal__message">
				<?php
				printf(
					wp_kses(
						__(
							'<b>Pay Now</b> is the traditional upfront payment method that everyone is familiar with. Recurring subscriptions automatically work on a pay now basis.',
							'revenue-generator'
						),
						[
							'b' => [],
						]
					)
				);
				?>
			</p>
		</div>
	</div>
	<div class="rev-gen-modal-overlay"></div>
</script>

<!-- Template for pricing info modal -->
<script type="text/template" id="tmpl-rg-modal-info-pricing">
	<div class="rev-gen-modal" id="rg-modal-info-pricing">
		<div class="rev-gen-modal__inner">
			<h4 class="rev-gen-modal__title">
				<?php esc_html_e( 'Static Pricing and Dynamic Pricing', 'revenue-generator' ); ?>
			</h4>
			<p class="rev-gen-modal__message">
				<?php
				esc_html_e( 'Revenue Generator allows you two ways to go about pricing content.', 'revenue-generator' );
				?>
			</p>
			<p class="rev-gen-modal__message">
				<?php
				printf(
					wp_kses(
						__(
							'Select <b>Static Pricing</b> to manually set the prices of individual articles based on your own pricing strategy. Charge as little - or as much - as you want.',
							'revenue-generator'
						),
						[
							'b' => [],
						]
					)
				);
				?>
			</p>
			<p class="rev-gen-modal__message">
				<?php
				printf(
					wp_kses(
						__(
							'If you select <b>Dynamic Pricing</b>, Laterpay’s AI will “dynamically” adjust the price based on our own data, analytics and algorithms based on the length of each article.',
							'revenue-generator'
						),
						[
							'b' => [],
						]
					)
				);
				?>
			</p>
		</div>
	</div>
	<div class="rev-gen-modal-overlay"></div>
</script>

<?php include( REVENUE_GENERATOR_PLUGIN_DIR . '/v1/templates/backend/modal-remove-paywall.php' ); ?>
<?php include( REVENUE_GENERATOR_PLUGIN_DIR . '/v1/templates/backend/modal-account-activation.php' ); ?>
<?php include( REVENUE_GENERATOR_PLUGIN_DIR . '/v1/templates/backend/modal-account-connect.php' ); ?>

<!-- Template for action to add paywall. -->
<script type="text/template" id="tmpl-revgen-add-paywall">
	<div class="rev-gen-preview-main--paywall-actions-add">
		<p>
			<?php esc_html_e( 'You don’t have a paywall on this page - all content will be publicly visible.', 'revenue-generator' ); ?>
		</p>
		<button id="rg_js_gotoDashboard" class="goto-dashboard-button" data-dashboard-url="<?php echo esc_url( $dashboard_url ); ?>">
			<?php esc_html_e( 'View Paywalls', 'revenue-generator' ); ?>
		</button>
		<button id="rj_js_addNewPaywall" data-preview-id="">
			<?php esc_html_e( 'Add Paywall', 'revenue-generator' ); ?>
		</button>
	</div>
</script>

<!-- Template for option update warning modal -->
<script type="text/template" id="tmpl-rg-modal-search-paywall-warning">
	<div class="rev-gen-modal" id="rg-modal-search-paywall-warning">
		<div class="rev-gen-modal__inner">
			<a href="#" class="rev-gen-modal__close" id="rg_js_modal_close">x</a>

			<h4 class="rev-gen-modal__title">
				<?php esc_html_e( 'This will create a new paywall.', 'revenue-generator' ); ?>
			</h4>

			<span class="rev-gen-modal__icon rev-gen-modal__icon--warning">!</span>

			<p class="rev-gen-modal__message">
				<?php
				esc_html_e( 'By choosing to preview on a different article, you will create a new paywall. Would you like to proceed?', 'revenue-generator' );
				?>
			</p>

			<div class="rev-gen-modal__buttons">
				<button id="rg_js_modal_confirm" class="rev-gen__button">
					<?php esc_html_e( 'Continue', 'revenue-generator' ); ?>
				</button>
				<button id="rg_js_modal_cancel" class="rev-gen__button rev-gen__button--secondary">
					<?php esc_html_e( 'Cancel', 'revenue-generator' ); ?>
				</button>
			</div>
		</div>
	</div>
	<div class="rev-gen-modal-overlay"></div>
</script>

<!-- Template for paywall activation modal -->
<script type="text/template" id="tmpl-rg-modal-paywall-activation">
	<div class="rev-gen-modal" id="rg-modal-paywall-activation">
		<div class="rev-gen-modal__inner">
			<a href="#" class="rev-gen-modal__close" id="rg_js_modal_close">x</a>

			<h4 class="rev-gen-modal__title">
				{{ data.paywallName }}
			</h4>

			<p class="rev-gen-modal__message">
				<# if ( 'category' === data.appliedTo || 'exclude_category' !== data.appliedTo ) { #>
					<# if ( 'category' === data.appliedTo ) { #>
						<?php
						printf(
							'%s <b>%s</b> %s <b>{{ data.categoryName }}</b>.',
							esc_html__( 'Has been published to', 'revenue-generator' ),
							esc_html__( 'all posts', 'revenue-generator' ),
							esc_html__( 'in category', 'revenue-generator' )
						);
						?>
					<# } #>

					<# if ( 'exclude_category' === data.appliedTo ) { #>
						<?php
						printf(
							'%s <b>%s</b> %s <b>{{ data.categoryName }}</b>.',
							esc_html__( 'Has been published to', 'revenue-generator' ),
							esc_html__( 'all posts, except posts under', 'revenue-generator' ),
							esc_html__( 'category', 'revenue-generator' )
						);
						?>
					<# } #>
				<# } else { #>
					<# if ( 'supported' === data.appliedTo ) { #>
						<?php
						printf(
							'%s <b>{{ data.postTitle }}</b>.',
							esc_html__( 'Has been published on', 'revenue-generator' )
						);
						?>
					<# } else if ( 'specific_post' === data.appliedTo ) { #>
						<?php
						printf(
							'%s <b>%s</b>.',
							esc_html__( 'Has been published on', 'revenue-generator' ),
							esc_html__( 'Specific Posts & Pages', 'revenue-generator' )
						);
						?>
					<# } else { #>
						<?php
						printf(
							'%s <b>%s</b>.',
							esc_html__( 'Has been published on', 'revenue-generator' ),
							esc_html__( 'all posts', 'revenue-generator' )
						);
						?>
					<# } #>
				<# } #>
			</p>

			<div class="rev-gen-modal__buttons">
				<button id="rg_js_modal_confirm" class="rev-gen__button">
					<?php esc_html_e( 'View on live post', 'revenue-generator' ); ?>
				</button>
				<button id="rg_js_modal_cancel" class="rev-gen__button rev-gen__button--secondary" data-dashboard_url="<?php echo esc_url( $dashboard_url ); ?>">
					<?php esc_html_e( 'View paywall dashboard', 'revenue-generator' ); ?>
				</button>
				<a href="<?php echo esc_url( $new_paywall_url ); ?>"><?php esc_html_e( 'Create another paywall', 'revenue-generator' ); ?></a>
			</div>
		</div>
	</div>
	<div class="rev-gen-modal-overlay"></div>
</script>

<!-- Template for dynamic title confirmation modal -->
<script type="text/template" id="tmpl-rg-modal-dynamic-title-desc">
	<div class="rev-gen-modal" id="rg-modal-dynamic-title-desc">
		<div class="rev-gen-modal__inner">
			<h4 class="rev-gen-modal__title">
				<?php esc_html_e( 'Update title and description for purchase options?', 'revenue-generator' ); ?>
			</h4>

			<p class="rev-gen-modal__message">
					<?php esc_html_e( 'Looks like you updated your time period, would you like to update your paywall to match?', 'revenue-generator' ); ?>
				</p>
			<div class="rev-gen-modal__buttons">
				<button id="rg_js_modal_confirm" class="rev-gen__button">
					<?php esc_html_e( 'Yes, update title and description', 'revenue-generator' ); ?>
				</button>
				<button id="rg_js_modal_cancel" class="rev-gen__button rev-gen__button--secondary">
					<?php esc_html_e( 'No, keep current title and description', 'revenue-generator' ); ?>
				</button>
			</div>
		</div>
	</div>
	<div class="rev-gen-modal-overlay"></div>
</script>

<!-- Template for publishing frequency modal -->
<script type="text/template" id="tmpl-rg-modal-publishing-frequency">
	<div class="rev-gen-modal rev-gen-modal--wide" id="rg-modal-publishing-frequency">
		<div class="rev-gen-modal__inner">
			<a href="#" class="rev-gen-modal__close" id="rg_js_modal_close">x</a>

			<h4 class="rev-gen-modal__title">
				<?php esc_html_e( 'How often do you publish premium content?', 'revenue-generator' ); ?>
			</h4>

			<div class="rev-gen-modal__cards">
				<div id="rg_js_modal_confirm" class="rev-gen-card">
					<div>
						<img class="rev-gen-card__icon" alt="<?php esc_attr_e( 'Fewer posts icon', 'revenue-generator' ); ?>" src="<?php echo esc_url( Config::$plugin_defaults['img_dir'] . 'low-publish.svg' ); ?>">
					</div>
					<h4 class="rev-gen-card__title">
						<?php
						printf(
							/* translators: %1$s Opening HTML bold tag %2$s Closing HTML bold tag */
							esc_html__( '%1$sFewer%2$s than 10 posts per month', 'revenue-generator' ),
							'<b>',
							'</b>'
						);
						?>
					</h4>
				</div>
				<div id="rg_js_modal_cancel" class="rev-gen-card">
					<div>
						<img class="rev-gen-card__icon" alt="<?php esc_attr_e( 'More posts icon', 'revenue-generator' ); ?>" src="<?php echo esc_url( Config::$plugin_defaults['img_dir'] . 'high-publish.svg' ); ?>">
					</div>
					<h4 class="rev-gen-card__title">
						<?php
						printf(
							/* translators: %1$s Opening HTML bold tag %2$s Closing HTML bold tag */
							esc_html__( '%1$sMore%2$s than 10 posts per month', 'revenue-generator' ),
							'<b>',
							'</b>'
						);
						?>
					</h4>
				</div>
			</div>
		</div>
	</div>
	<div class="rev-gen-modal-overlay"></div>
</script>

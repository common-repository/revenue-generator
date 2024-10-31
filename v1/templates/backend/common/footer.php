<?php
/**
 * Revenue Generator footer with logo.
 *
 * @package revenue-generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file.
	exit;
}
?>
<div class="rev-gen-footer">
	<span><?php esc_html_e( 'Powered by', 'revenue-generator' ); ?></span>
	<a href="https://www.laterpay.net/" target="_blank" rel="noopener">
		<img alt="<?php esc_attr_e( 'Laterpay Logo', 'revenue-generator' ); ?>" src="<?php echo esc_url( $laterpay_logo ); ?>">
	</a>
</div>

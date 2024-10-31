<?php
/**
 * Contribution footer.
 *
 * @package revenue-generator
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="rev-gen-contribution-footer__inner">
	<span><?php esc_html_e( 'Powered by', 'revenue-generator' ); ?></span>
	<a href="https://www.laterpay.net/" target="_blank" rel="noopener">
		<img alt="<?php esc_attr_e( 'Laterpay Logo', 'revenue-generator' ); ?>" src="<?php echo esc_url( REVENUE_GENERATOR_BUILD_URL . '/img/lp-logo.svg' ); ?>">
	</a>
</div>

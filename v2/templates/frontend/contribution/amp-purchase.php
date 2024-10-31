<?php
/**
 * Template for purchase component in AMP context.
 *
 * @package revenue-generator
 */

 defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html>
	<head>
		<title>Rev Gen Purchase Component Embed</title>

		<style>
			html, body {
				margin: 0;
				padding: 0;
			}
		</style>

		<link rel="stylesheet" href="<?php echo REVENUE_GENERATOR_BUILD_URL . 'css/frontend.css'; ?>?v=<?php echo REVENUE_GENERATOR_VERSION; ?>"> <?php // phpcs:ignore -- WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
	</head>
	<body>
		<div class="lp__root">
			<div class="lp__tab-widget-data" data-tab-amount="<?php echo esc_attr( $data['tab']['total'] ); ?>" data-tab-currency="USD" data-tab-limit="<?php echo esc_attr( $data['tab']['limit'] ); ?>" data-view-tab-url="<?php echo esc_url( $data['mytab_url'] ); ?>"></div>
		</div>
		<script src="https://assets.sbx.laterpay.net/pcpro-jsx-components-frontend/main.js"></script> <?php // phpcs:ignore -- WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
	</body>
</html>

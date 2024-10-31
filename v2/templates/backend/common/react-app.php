<?php
/**
 * Revenue Generator React app.
 *
 * @package revenue-generator
 */

use \LaterPay\Revenue_Generator\Inc\View;
use \LaterPay\Revenue_Generator\Inc\Api\Auth;
use \LaterPay\Revenue_Generator\Inc\Post_Types\Contribution;
use \LaterPay\Revenue_Generator\Inc\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file.
	exit;
}

$auth = Auth::get_instance();
$contribution = Contribution::get_instance();
?>
<script>
	window.__REV_GEN_DATA__ = {
		"nonce": "<?php echo esc_html( wp_create_nonce( 'wp_rest' ) ); ?>",
		"siteUrl": "<?php echo esc_url( site_url() ); ?>",
	};
</script>
<div class="wrap">
	<div class="rev-gen-layout-wrapper">
		<div id="rev-gen-app">
			<div id="rev-gen-react-app"></div>
			<div id="rev-gen-modal-portal"></div>
		</div>
	</div>
</div>

<?php
/**
 * Revenue Generator Contribution Short code Screen.
 *
 * @package revenue-generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	// prevent direct access to this file.
	exit;
}

use \LaterPay\Revenue_Generator\Inc\Post_Types\Contribution;

$contribution_instance = Contribution::get_instance();
$contribution_id       = ( isset( $_GET['id'] ) ) ? (int) $_GET['id'] : 0;
$contribution_data     = $contribution_instance->get( $contribution_id );

$layout_type           = ( isset( $_GET['layout_type'] ) ) ? sanitize_text_field( $_GET['layout_type'] ) : $contribution_data['layout_type'];

$shortcode             = sprintf(
	'[laterpay_contribution name="%s" thank_you="%s" type="%s" custom_amount="%s" all_amounts="%s" all_revenues="%s" selected_amount="1" dialog_header="%s" dialog_description="%s" layout_type="%s" button_label="%s"]',
	$contribution_data['name'],
	$contribution_data['thank_you'],
	$contribution_data['type'],
	$contribution_data['custom_amount'],
	join( ',', $contribution_data['all_amounts'] ),
	$contribution_data['all_revenues'],
	$contribution_data['dialog_header'],
	$contribution_data['dialog_description'],
	$layout_type,
	$contribution_data['button_label']
);
?>
<!DOCTYPE html>
<html lang="en" class="rev-gen-preview">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Revenue Generator Contribution Preview</title>

	<?php wp_head(); ?>
</head>
<body>
	<main>
		<?php echo do_shortcode( $shortcode ); ?>
	</main>
</body>
</html>

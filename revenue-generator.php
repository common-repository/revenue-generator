<?php
/**
 * Plugin Name: Revenue Generator
 * Description: Monetize your blog and content with the Revenue Generator.
 * Plugin URI: https://github.com/laterpay/revenue-generator
 * Version: 2.4.0
 * Author: Laterpay
 * Text Domain: revenue-generator
 * Author URI: https://laterpay.net/
 * Domain Path: /languages/
 *
 * @package revenue-generator
 */

defined( 'ABSPATH' ) || exit;

define( 'REVENUE_GENERATOR_VERSION', '2.4.0' );

if ( ! defined( 'REVENUE_GENERATOR_PLUGIN_FILE' ) ) {
	// Define plugin main file.
	define( 'REVENUE_GENERATOR_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'REVENUE_GENERATOR_PLUGIN_DIR' ) ) {
	// Define plugin directory path.
	define( 'REVENUE_GENERATOR_PLUGIN_DIR', untrailingslashit( plugin_dir_path( REVENUE_GENERATOR_PLUGIN_FILE ) ) );
}

define( 'REVENUE_GENERATOR_PLUGIN_URL', plugins_url( '', REVENUE_GENERATOR_PLUGIN_FILE ) );

require_once REVENUE_GENERATOR_PLUGIN_DIR . '/inc/helpers/autoloader.php';

use LaterPay\Revenue_Generator\Main;

$main = Main::get_instance();

define( 'REVENUE_GENERATOR_INTEGRATION_TYPE', $main->get_integration_type() );

$main->load_plugin();

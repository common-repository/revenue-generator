<?php
/**
 * Register Paywall post type.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc\Post_Types;

use LaterPay\Revenue_Generator\Inc\Config;
use LaterPay\Revenue_Generator\Inc\Post_Types;
use LaterPay\Revenue_Generator\Inc\View;

defined( 'ABSPATH' ) || exit;

/**
 * Class Paywall
 */
class Contribution_Preview extends Base {

	/**
	 * Slug of post type.
	 *
	 * @var string
	 */
	const SLUG = 'rg_preview';

	/**
	 * Extends parent `setup_hooks()` method to add its own hooks.
	 *
	 * @return void
	 */
	protected function setup_hooks() {
		parent::setup_hooks();

		add_filter( 'template_include', [ $this, 'set_template' ] );
	}

	/**
	 * Get array of arguments to pass to `register_post_type`.
	 *
	 * @return array
	 */
	protected function get_args() {
		return array(
			'supports'              => false,
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => false,
			'show_in_menu'          => false,
			'show_in_admin_bar'     => false,
			'show_in_nav_menus'     => false,
			'can_export'            => false,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => true,
			'capability_type'       => 'page',
		);
	}

	/**
	 * To get list of labels for paywall post type.
	 *
	 * @return array
	 */
	public function get_labels() {

		return [
			'name'          => __( 'Contribution Previews', 'revenue-generator' ),
			'singular_name' => __( 'Contribution Preview', 'revenue-generator' ),
		];

	}

	/**
	 * Set preview post template to our custom template without any theme related code around.
	 *
	 * @hooked filter `set_template`
	 *
	 * @param string $template Original template path.
	 *
	 * @return string $template Template path.
	 */
	public function set_template( $template ) {
		if ( static::SLUG !== get_post_type() ) {
			return $template;
		}

		$template = REVENUE_GENERATOR_PLUGIN_DIR . '/templates/backend/contribution/preview.php';

		return $template;
	}

}

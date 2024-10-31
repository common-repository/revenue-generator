<?php
/**
 * Load all classes that registers a post type and define methods to handle supported post types.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc;

use \LaterPay\Revenue_Generator\Inc\Traits\Singleton;
use \LaterPay\Revenue_Generator\Inc\Post_Types\Contribution;

defined( 'ABSPATH' ) || exit;

/**
 * Class Post_Types
 */
class Post_Types {

	use Singleton;

	/**
	 * To store instance of post type.
	 *
	 * @var array List of instance of post type.
	 */
	protected static $instances = [];

	/**
	 * Construct method.
	 */
	protected function __construct() {
		$this->register_post_types();
	}

	/**
	 * To initiate all post type instance.
	 *
	 * @return void
	 */
	protected function register_post_types() {

		self::$instances = [
			Contribution::SLUG         => Contribution::get_instance(),
		];

	}

	/**
	 * To get instance of all post types.
	 *
	 * @return array List of instances of the post types.
	 */
	public static function get_instances() {
		return self::$instances;
	}

	/**
	 * Get slug list of all registered custom post types.
	 *
	 * @return array List of slugs.
	 */
	public static function get_registered_post_types() {
		return array_keys( self::$instances );
	}

	/**
	 * Get slug list of all post types whose content is allowed for sale..
	 *
	 * @return array List of slugs.
	 */
	public static function get_allowed_post_types() {
		return [
			'post',
			'page',
		];
	}

}

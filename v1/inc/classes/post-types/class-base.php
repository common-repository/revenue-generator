<?php
/**
 * Abstract class to register post type.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc\Post_Types;

use \LaterPay\Revenue_Generator\Inc\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Base class to register post types.
 */
abstract class Base {

	use Singleton;

	/**
	 * Construct method.
	 */
	final protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * To register action/filters.
	 *
	 * @return void
	 */
	protected function setup_hooks() {

		/**
		 * Actions.
		 */
		add_action( 'init', [ $this, 'register_post_type' ] );
	}

	/**
	 * Get all arguments for `register_post_type`.
	 *
	 * @return array
	 */
	protected function get_args() {
		return [];
	}

	/**
	 * To register post type.
	 *
	 * @return void
	 */
	final public function register_post_type() {

		if ( empty( static::SLUG ) ) {
			return;
		}

		$args = $this->get_args();

		$labels = $this->get_labels();
		$labels = ( ! empty( $labels ) && is_array( $labels ) ) ? $labels : [];

		if ( ! empty( $labels ) && is_array( $labels ) ) {
			$args['labels'] = $labels;
		}

		register_post_type( static::SLUG, $args );
	}

	/**
	 * To get slug of post type.
	 *
	 * @return string Slug of post type.
	 */
	public function get_slug() {
		return ( ! empty( static::SLUG ) ) ? static::SLUG : '';
	}

	/**
	 * To get list of labels for custom post type.
	 * Must be in child class.
	 *
	 * @return array
	 */
	abstract public function get_labels();

}

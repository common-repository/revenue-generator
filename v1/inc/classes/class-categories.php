<?php
/**
 * Handle category based pricing functionality.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc;

use LaterPay\Revenue_Generator\Inc\Traits\Singleton;


defined( 'ABSPATH' ) || exit;

/**
 * Class Categories
 */
class Categories {

	use Singleton;

	/**
	 * Get categories that don't have a paywall associated to them.
	 *
	 * @param array $args Additional query args.
	 *
	 * @return array
	 */
	public function get_applicable_categories( $args ) {
		$query = [
			'taxonomy'   => 'category',
			'hide_empty' => false,
		];

		$args = wp_parse_args(
			$args,
			$query
		);

		$category_with_price = new \WP_Term_Query( $args );

		return $category_with_price->terms;
	}

	/**
	 * Clear category meta if a new one has been added.
	 *
	 * @param int $rg_category_id Term ID to clear meta information.
	 *
	 * @return bool
	 */
	public function clear_category_paywall_meta( $rg_category_id ) {
		return delete_term_meta( $rg_category_id, '_rg_has_paywall' );
	}

}

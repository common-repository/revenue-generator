<?php
/**
 * Autoloader file for plugin.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc\Helpers;

/**
 * Auto loader function.
 *
 * @param string $resource Source namespace.
 *
 * @return void
 */
function autoloader( $resource = '' ) {
	$resource_path   = false;
	$namespace_root  = 'LaterPay\Revenue_Generator\\';
	$resource        = trim( $resource, '\\' );

	if ( empty( $resource ) || strpos( $resource, '\\' ) === false || strpos( $resource, $namespace_root ) !== 0 ) {
		// Not our namespace, bail out.
		return;
	}

	// Remove our root namespace.
	$resource = str_replace( $namespace_root, '', $resource );

	$path = explode(
		'\\',
		str_replace( '_', '-', strtolower( $resource ) )
	);

	/**
	 * Time to determine which type of resource path it is,
	 * so that we can deduce the correct file path for it.
	 */
	if ( empty( $path[0] ) ) {
		return;
	}

	$directory = '';
	$file_name = '';

	if ( 'inc' === $path[0] ) {
		switch ( $path[1] ) {
			case 'traits':
				$directory = 'inc/traits';
				$file_name = sprintf( 'trait-%s', trim( strtolower( $path[2] ) ) );

				break;

			case 'post-types': // @todo - Remove unwanted cases before launch.
			case 'taxonomies':
			case 'blocks':
			case 'meta-boxes':
			case 'widgets':
			case 'api':
			case 'plugin-configs':
			case 'migrations': // phpcs:ignore PSR2.ControlStructures.SwitchDeclaration.TerminatingComment
				/**
				 * If there is class name provided for specific directory then load that.
				 * otherwise find in inc/ directory.
				 */
				if ( ! empty( $path[2] ) ) {
					$directory = sprintf( 'inc/classes/%s', $path[1] );
					$file_name = sprintf( 'class-%s', trim( strtolower( $path[2] ) ) );

					if ( defined( 'REVENUE_GENERATOR_INTEGRATION_TYPE' ) ) {
						$directory = REVENUE_GENERATOR_INTEGRATION_TYPE . '/' . $directory;
					}

					break;
				}
			default:
				$directory = 'inc/classes';
				$file_name = sprintf( 'class-%s', trim( strtolower( $path[1] ) ) );

				if ( defined( 'REVENUE_GENERATOR_INTEGRATION_TYPE' ) ) {
					$directory = REVENUE_GENERATOR_INTEGRATION_TYPE . '/' . $directory;
				}

				break;
		}

		$integration_dir = untrailingslashit( REVENUE_GENERATOR_PLUGIN_DIR ) . '/' . $directory;

		$resource_path = sprintf(
			'%s/%s.php',
			$integration_dir,
			$file_name
		);
	}

	if ( 'onboarding' === $path[0] ) {
		$resource_path = sprintf(
			'%s/onboarding/inc/classes/class-%s.php',
			untrailingslashit( REVENUE_GENERATOR_PLUGIN_DIR ),
			trim( strtolower( $path[1] ) )
		);
	}

	if ( empty( $path[1] ) ) {
		$resource_path = sprintf(
			'%s/inc/classes/class-%s.php',
			untrailingslashit( REVENUE_GENERATOR_PLUGIN_DIR ),
			trim( strtolower( $path[0] ) )
		);
	}

	if ( ! empty( $resource_path ) && file_exists( $resource_path ) && 0 === validate_file( $resource_path ) ) {
		// We already making sure that file is exists and valid.
		require_once( $resource_path ); // phpcs:ignore
	}

}

spl_autoload_register( __NAMESPACE__ . '\autoloader' );

// Require composer generated autoloader.
require_once( REVENUE_GENERATOR_PLUGIN_DIR . '/vendor/autoload.php' );

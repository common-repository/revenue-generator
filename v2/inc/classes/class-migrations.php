<?php
/**
 * Migrations class file.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc;

use \LaterPay\Revenue_Generator\Inc\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Migrations class.
 */
class Migrations {

	use Singleton;

	/**
	 * Key of the option holding latest migration version.
	 *
	 * @var string
	 */
	private $version_option_name = 'lp_rgv2_migration_version';

	/**
	 * Array of pending migrations.
	 *
	 * @var array
	 */
	private $pending_migrations = [];

	/**
	 * Register new migration.
	 *
	 * Adds instance of Migrations\Migration to pending_migrations var.
	 *
	 * @param Migrations\Migration $migration Migration instance.
	 *
	 * @return void
	 */
	public function register( Migrations\Migration $migration ) {
		/**
		 * Only add migration to the list if its version is greater than
		 * the last ran migration.
		 */
		if ( ! empty( $this->get_version() ) && version_compare( $migration->get_version(), $this->get_version(), '<=' ) ) {
			return;
		}

		$this->pending_migrations[] = $migration;
	}

	/**
	 * Run migrations.
	 *
	 * @return void
	 */
	public function run() {
		// Return early if no migrations are pending.
		if ( empty( $this->pending_migrations ) ) {
			return;
		}

		// Loop through all pending migrations and call `run` method.
		foreach ( $this->pending_migrations as $migration ) {
			$run = $migration->run();

			// On successful migration, update migrations version.
			if ( true === $run ) {
				$this->update_version( $migration->get_version() );
			}
		}
	}

	/**
	 * Get latest version of the migrations.
	 *
	 * @return string Version number.
	 */
	public function get_version() {
		return get_option( $this->version_option_name, '' );
	}

	/**
	 * Update migrations version.
	 *
	 * @param string $version New version.
	 *
	 * @return void
	 */
	public function update_version( $version = '' ) {
		if ( empty( $version ) ) {
			return;
		}

		update_option( $this->version_option_name, $version );
	}

}

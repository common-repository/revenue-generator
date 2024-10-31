<?php
/**
 * Migration abstract class file.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc\Migrations;

/**
 * Migration abstract class.
 */
abstract class Migration {

	/**
	 * Migration version.
	 *
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * Get the version number.
	 *
	 * @return string $this->version
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Code to run on migration.
	 */
	public function run() {}

}

<?php
/**
 * Helper encryption class (a wrapper for Sodium functions).
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc;

use \LaterPay\Revenue_Generator\Inc\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Encryption singleton class.
 */
class Encryption {

	use Singleton;

	/**
	 * Key to use in production env.
	 *
	 * @const string
	 */
	const PROD_KEY = 'XEcv76X2MZdJc7p2ugcoPPStOaIMHP4p';

	/**
	 * Key to use in sandbox env.
	 *
	 * @const string
	 */
	const SBX_KEY = 'CfmyBfhMfcVRNf4vD4qtjx4vPTGnZM0l';

	/**
	 * Cipher method.
	 *
	 * @const string
	 */
	const CIPHER_METHOD = 'aes-256-gcm';

	/**
	 * Encrypts a message.
	 *
	 * @param string $message Message to encode.
	 * @param string $tag     Authentication tag.
	 * @param string $key     Secret key.
	 *
	 * @return string base64 encoded string.
	 */
	public static function encrypt( $message, $tag = '', $key = '' ) {
		// Return early if required parameters are missing.
		if ( empty( $message ) ) {
			return false;
		}

		// Return early if OpenSSL module is not installed.
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return false;
		}

		// If key is empty, use one of pre-defined ones.
		if ( empty( $key ) ) {
			$key = static::get_key_by_environment();
		}

		// If tag is missing, generate a 16 byte tag.
		if ( empty( $tag ) ) {
			$tag = random_bytes( 16 / 2 );
		}

		$iv = static::generate_iv();

		// If the message is array, convert it to JSON format.
		if ( is_array( $message ) ) {
			$message = json_encode( $message );
		}

		$encrypted = openssl_encrypt( // phpcs:ignore -- PHPCompatibility.FunctionUse.NewFunctionParameters.openssl_encrypt_tagFound
			$message,
			static::CIPHER_METHOD,
			$key,
			OPENSSL_RAW_DATA,
			$iv,
			$tag
		);

		// Return encoded string with IV, Tag, and encrypted message.
		return base64_encode(
			sprintf(
				'%1$s%2$s%3$s',
				$iv,
				$tag,
				$encrypted
			)
		);
	}

	/**
	 * Decrypts a message.
	 *
	 * @param string $message Encrypted message encoded in base64.
	 * @param string $key     Secret key.
	 *
	 * @return string Decrypted message.
	 */
	public static function decrypt( $message, $key = '' ) {
		if ( empty( $message ) ) {
			return false;
		}

		if ( empty( $key ) ) {
			$key = static::get_key_by_environment();
		}

		// Decode base64.
		$decoded = base64_decode( $message );

		/**
		 * Do substr to get IV, Tag, and message itself.
		 */

		// IV is always first 12 bytes.
		$iv      = substr( $decoded, 0, 12 );
		// Tag is always 16 bytes following the IV.
		$tag     = substr( $decoded, 12, 16 );
		// Message begins on 28th byte after the tag.
		$message = substr( $decoded, 28, strlen( $decoded ) );

		// Decrypt.
		$decrypted = openssl_decrypt( // phpcs:ignore -- PHPCompatibility.FunctionUse.NewFunctionParameters.openssl_encrypt_tagFound
			$message,
			static::CIPHER_METHOD,
			$key,
			OPENSSL_RAW_DATA,
			$iv,
			$tag
		);

		return $decrypted;
	}

	/**
	 * Generate IV of a required length.
	 *
	 * @return string
	 */
	public static function generate_iv() {
		// Dividing by 2 as random bytes functions will always return 2x value.
		$length = openssl_cipher_iv_length( static::CIPHER_METHOD ) / 2;

		if ( function_exists( 'random_bytes' ) ) {
			return bin2hex( random_bytes( $length ) );
		}

		if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
			return bin2hex( openssl_random_pseudo_bytes( $length ) );
		}
	}

	/**
	 * Get key by environment (prod or sbx).
	 *
	 * @return string
	 */
	public static function get_key_by_environment() {
		if ( defined( 'REVENUE_GENERATOR_SANDBOX_MODE' ) && REVENUE_GENERATOR_SANDBOX_MODE ) {
			return static::SBX_KEY;
		}

		return static::PROD_KEY;
	}

}

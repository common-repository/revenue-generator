<?php
/**
 * Revenue Generator Contribution URL Generator.
 * forked from https://github.com/laterpay/laterpay-client-php.git to remove vendor dependency.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc;

use \LaterPay\Revenue_Generator\Inc\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Revenue Generator Client.
 */
class Revenue_Generator_Client {

	use Singleton;

	/**
	 * Contains the hash algorithm.
	 *
	 * @var string
	 */
	protected static $hash_algo = 'sha224';

	/**
	 * API key
	 *
	 * @var string
	 */
	protected $api_key;

	/**
	 * Backend API root.
	 *
	 * @var string
	 */
	protected $api_root;

	/**
	 * Dialog API root.
	 *
	 * @var string
	 */
	protected $web_root;

	/**
	 * Merchant Id.
	 *
	 * @var string
	 */
	protected $cp_key;

	/**
	 * POST method.
	 *
	 * @var string
	 */
	const POST = 'POST';

	/**
	 * GET method.
	 *
	 * @var string
	 */
	const GET = 'GET';

	/**
	 * LaterPay_Client constructor.
	 *
	 * @param string $cp_key   Merchant ID.
	 * @param string $api_key  Merchant Key.
	 * @param string $api_root API Root.
	 * @param string $web_root Web Root.
	 */
	public function __construct( $cp_key, $api_key, $api_root, $web_root ) {
		$this->cp_key   = $cp_key;
		$this->api_key  = $api_key;
		$this->api_root = $api_root;
		$this->web_root = $web_root;
	}

	/**
	 * Get contribution URL based on revenue.
	 *
	 * @param array $data Contribution data.
	 *
	 * @return string $url
	 */
	public function get_single_contribution_url( $data ) {
		$endpoint = 'contribute/pay_now';
		if ( 'ppu' === $data['revenue'] ) {
			$endpoint = 'contribute/pay_later';
		}
		return $this->get_web_url( $data, $endpoint );
	}

	/**
	 * Build Purchase URL.
	 *
	 * @param array  $data     Input Data.
	 * @param string $endpoint Endpoint URL.
	 * @param array  $options  Options.
	 *
	 * @return string $url
	 */
	protected function get_web_url( array $data, $endpoint, $options = array() ) {
		$default_options = array(
			'dialog'   => true,
			'jsevents' => false,
		);

		// merge with defaults.
		$options = array_merge( $default_options, $options );

		// add merchant id if not specified.
		if ( ! isset( $data['cp'] ) ) {
			$data['cp'] = $this->cp_key;
		}

		// jsevent for dialog if specified.
		if ( $options['jsevents'] ) {
			$data['jsevents'] = 1;
		}

		// is dialog url.
		if ( $options['dialog'] ) {
			$prefix = $this->web_root . '/dialog';
		} else {
			$prefix = $this->web_root;
		}

		// build puchase url.
		$base_url = join( '/', array( $prefix, $endpoint ) );
		$params   = $this->sign_and_encode( $this->api_key, $data, $base_url, self::GET );
		$url      = $base_url . '?' . $params;

		return $url;
	}

	/**
	 * Sign and encode a URL 'url' with a 'secret' key called via a HTTP 'method'.
	 * It adds the signature to the URL as the URL parameter "hmac" and also adds the required timestamp parameter 'ts'
	 * if it's not already in the 'params' dictionary. 'unicode()' instances in params are handled correctly.
	 *
	 * @param string $secret Secret.
	 * @param array  $params Parameters.
	 * @param string $url    URL.
	 * @param string $method HTTP method.
	 *
	 * @return string query params
	 */
	public static function sign_and_encode( $secret, $params, $url, $method = self::GET ) {
		// Set the time param only if ts and permalink are not set.
		if ( ! isset( $params['ts'] ) && ! isset( $params['permalink'] ) ) {
			$params['ts'] = (string) time();
		}

		if ( isset( $params['hmac'] ) ) {
			unset( $params['hmac'] );
		}

		// get the keys in alphabetical order.
		$keys = array_keys( $params );
		sort( $keys, SORT_STRING );
		$query_pairs = array();
		foreach ( $keys as $key ) {
			$aux = $params[ $key ];
			$key = utf8_encode( $key );

			if ( ! is_array( $aux ) ) {
				$aux = array( $aux );
			}
			sort( $aux, SORT_STRING );
			foreach ( $aux as $value ) {
				if ( mb_detect_encoding( $value, 'UTF-8' ) !== 'UTF-8' ) {
					$value = rawurlencode( utf8_encode( $value ) );
				}
				$query_pairs[] = rawurlencode( $key ) . '=' . rawurlencode( $value );
			}
		}

		// build the querystring.
		$encoded = join( '&', $query_pairs );

		// hash the querystring data.
		$hmac = self::sign( $secret, $params, $url, $method );

		return $encoded . '&hmac=' . $hmac;
	}

	/**
	 * Create signature for given 'params', 'url', and HTTP method.
	 *
	 * How params are canonicalized:
	 * - 'urllib.quote' every key and value that will be signed
	 * - sort the params list
	 * - '&'-join the params
	 *
	 * @param string $secret secret used to create signature.
	 * @param array  $params mapping of all parameters that should be signed.
	 * @param string $url    full URL of the target endpoint, no URL parameters.
	 * @param string $method Request method.
	 *
	 * @return string
	 */
	protected static function sign( $secret, $params, $url, $method = self::POST ) {
		$secret = utf8_encode( $secret );

		if ( isset( $params['hmac'] ) ) {
			unset( $params['hmac'] );
		}

		if ( isset( $params['gettoken'] ) ) {
			unset( $params['gettoken'] );
		}

		$aux = explode( '?', $url );
		$url = $aux[0];
		$msg = self::create_base_message( $params, $url, $method );
		$mac = self::create_hmac( $secret, $msg );

		return $mac;
	}

	/**
	 * Create base message.
	 *
	 * @param array  $params mapping of all parameters that should be signed.
	 * @param string $url    full URL of the target endpoint, no URL parameters.
	 * @param string $method request method.
	 *
	 * @return string
	 */
	protected static function create_base_message( $params, $url, $method = self::POST ) {
		$msg    = '{method}&{url}&{params}';
		$method = strtoupper( $method );

		$data   = array();
		$url    = rawurlencode( utf8_encode( $url ) );
		$params = self::normalise_param_structure( $params );

		$keys = array_keys( $params );
		sort( $keys, SORT_STRING );
		foreach ( $keys as $key ) {
			$value = $params[ $key ];
			$key   = rawurlencode( utf8_encode( $key ) );

			if ( ! is_array( $value ) ) {
				$value = array( $value );
			}

			$encoded_value = '';
			sort( $value, SORT_STRING );
			foreach ( $value as $v ) {
				if ( mb_detect_encoding( $v, 'UTF-8' ) !== 'UTF-8' ) {
					$encoded_value = rawurlencode( utf8_encode( $v ) );
				} else {
					$encoded_value = rawurlencode( $v );
				}
				$data[] = $key . '=' . $encoded_value;
			}
		}

		$param_str = rawurlencode( join( '&', $data ) );
		$result    = str_replace( array( '{method}', '{url}', '{params}' ), array( $method, $url, $param_str ), $msg );

		return $result;
	}

	/**
	 * Request parameter dictionaries are handled in different ways in different libraries,
	 * this function is required to ensure we always have something of the format
	 * { key: [ value1, value2, ... ] }.
	 *
	 * @param array $params Reqeust parameters.
	 *
	 * @return array
	 */
	protected static function normalise_param_structure( $params ) {
		$out = array();

		// this is tricky - either we have (a, b), (a, c) or we have (a, (b, c)).
		foreach ( $params as $param_name => $param_value ) {
			if ( is_array( $param_value ) ) {
				// this is (a, (b, c)). WPCS: comment ok.
				$out[ $param_name ] = $param_value;
			} else {
				// this is (a, b), (a, c). WPCS: comment ok.
				if ( ! in_array( $param_name, $out, true ) ) {
					$out[ $param_name ] = array();
				}
				$out[ $param_name ][] = $param_value;
			}
		}

		return $out;
	}

	/**
	 * Creates Hash.
	 *
	 * @param string $secret Secret.
	 * @param string $parts  data part.
	 *
	 * @return string
	 */
	protected static function create_hmac( $secret, $parts ) {
		if ( is_array( $parts ) ) {
			$data = join( '', $parts );
		} else {
			$data = (string) $parts;
		}

		// limit at length 32 for sha224 as it was the same in previously used library.
		$raw_hash = substr( hash_hmac( self::$hash_algo, $data, $secret, true ), 0, 32 );
		// hexadecimal representation of the given string.
		$hash = bin2hex( $raw_hash );

		return $hash;
	}

	/**
	 * Get all contribution URL for custom pricing.
	 *
	 * @param array $data Contribution data.
	 *
	 * @return array $urls
	 */
	public function get_contribution_urls( $data ) {
		$urls      = array();
		$endpoints = array(
			'sis' => 'contribute/pay_now',
			'ppu' => 'contribute/pay_later',
		);

		foreach ( $endpoints as $revenue => $endpoint ) {
			$urls[ $revenue ] = $this->get_web_url( $data, $endpoint );
		}
		return $urls;
	}

}

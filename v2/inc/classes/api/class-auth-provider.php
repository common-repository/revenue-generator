<?php
/**
 * OAuth2 Client provider for auth with Tapper.
 *
 * @package revenue-generator
 */

namespace LaterPay\Revenue_Generator\Inc\Api;

use \LaterPay\Revenue_Generator\Inc\API;
use \League\OAuth2\Client\Provider\AbstractProvider;
use \League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use \League\OAuth2\Client\Token\AccessToken;
use \Psr\Http\Message\ResponseInterface;
use \UnexpectedValueException;

defined( 'ABSPATH' ) || exit;

/**
 * Tapper auth provider class.
 */
class Auth_Provider extends AbstractProvider {

	/**
	 * URL for auth endpoint.
	 *
	 * @var string
	 */
	protected $authUri = ''; // phpcs:ignore -- WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * URL for getting resource owner details.
	 *
	 * @var string
	 */
	private $resource_owner_details_url = '';

	/**
	 * Constructs an OAuth 2.0 service provider.
	 *
	 * @param array $options       Array of options to set the provider.
	 * @param array $collaborators Array of collaborators to use with provider.
	 *
	 * @return void
	 */
	public function __construct( $options = [], array $collaborators = [] ) {
		parent::__construct( $options, $collaborators );

		// Set sandbox flag.
		if ( ! empty( $options['authUri'] ) ) {
			$this->authUri = $options['authUri']; // phpcs:ignore -- WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
		}
	}

	/**
	 * Get owner details URL.
	 *
	 * @param AccessToken $token Token.
	 *
	 * @inheritdoc
	 */
	public function getResourceOwnerDetailsUrl( AccessToken $token ) {
		return $this->resource_owner_details_url; /* phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase */
	}

	/**
	 * Get default scopes.
	 *
	 * @inheritdoc
	 */
	public function getDefaultScopes() {
		return [ 'read write offline_access' ];
	}

	/**
	 * Returns a new random string to use as the state parameter in an
	 * authorization flow.
	 *
	 * Adds support for PHP5 by generating state with `openssl_random_pseudo_bytes` if
	 * `random_bytes` does not exist.
	 *
	 * @param  int $length Length of the random string to be generated.
	 * @return string
	 */
	protected function getRandomState( $length = 32 ) {
		if ( function_exists( 'random_bytes' ) ) {
			return bin2hex( random_bytes( $length / 2 ) );
		}

		if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
			return bin2hex( openssl_random_pseudo_bytes( $length / 2 ) );
		}
	}

	/**
	 * Check response and throw exception if error is present.
	 *
	 * @param ResponseInterface $response Response.
	 * @param array             $data     Data.
	 *
	 * @throws IdentityProviderException Thrown when response has an error.
	 *
	 * @inheritdoc
	 */
	protected function checkResponse( ResponseInterface $response, $data ) {
		if ( ! empty( $data['error'] ) ) {
			$message = $data['error'] . ': ' . $data['error_verbose'];

			throw new IdentityProviderException( $message, $data['status_code'], $data );
		}
	}

	/**
	 * Create resource owner.
	 *
	 * @param array       $response Response.
	 * @param AccessToken $token    Access token instance.
	 *
	 * @return GenericResourceOwner Resource Owner instance.
	 *
	 * @inheritdoc
	 */
	protected function createResourceOwner( array $response, AccessToken $token ) {
		return new GenericResourceOwner( $response, $this->responseResourceOwnerId ); /* phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase */
	}

	/**
	 * Get base authorization URL.
	 *
	 * @return string URL.
	 */
	public function getBaseAuthorizationUrl() {
		return $this->authUri . '/oauth2/auth'; // phpcs:ignore -- WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
	}

	/**
	 * Get base access token URL.
	 *
	 * @param array $params Optional params passed.
	 *
	 * @return string URL.
	 */
	public function getBaseAccessTokenUrl( array $params ) {
		return $this->authUri . '/oauth2/token'; // phpcs:ignore -- WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
	}

	/**
	 * Get default headers for auth calls.
	 *
	 * @return array
	 */
	protected function getDefaultHeaders() {
		return [
			'User-Agent' => API::get_default_headers(),
		];
	}

}

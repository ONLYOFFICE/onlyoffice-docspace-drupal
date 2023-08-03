<?php

namespace Drupal\onlyoffice_docspace\RequestManager;

/**
 * Copyright (c) Ascensio System SIA 2023.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\Cookie\CookieJar;

class RequestManager extends RequestManagerBase implements RequestManagerInterface {

	/**
	 * Config factory service.
	 *
	 * @var \Drupal\Core\Config\ConfigFactoryInterface
	 */
	protected $configFactory;

	/**
	 * The HTTP client to fetch the feed data with.
	 *
	 * @var \GuzzleHttp\ClientInterface
	 */
	protected $httpClient;

	/**
	 * A logger instance.
	 *
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * Constructor.
	 *
	 * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
	 *   The config factory service.
	 * @param \GuzzleHttp\ClientInterface $http_client
   	 *   The Guzzle HTTP client.
	 */
	public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
		$this->configFactory = $config_factory;
		$this->httpClient = $http_client;
		$this->logger = $this->getLogger('onlyoffice');
	  }

  
	/**
	 * {@inheritdoc}
	 */
	public function connectDocSpace( $url = null, $login = null, $passwordHash = null ) {
		$result = array(
			'error' => null,
			'data'  => null,
		);

		$currentUrl = $this->config('onlyoffice_docspace.settings')->get('url');
		$currentLogin = $this->config('onlyoffice_docspace.settings')->get('login');
		$currentPasswordHash  = $this->config('onlyoffice_docspace.settings')->get('passwordHash');

		// Try authentication with current credintails, if new credintails equals null or new credintails equals current credintails.
		if ((null === $url && null === $login && null === $passwordHash)
			|| ($currentUrl === $url && $currentLogin === $login && $currentPasswordHash === $passwordHash)) {

			$currentToken = $this->config('onlyoffice_docspace.settings')->get('token');

			if (!empty($currentToken)) {
				// Check is admin with current token.
				$responseDocSpaceUser = $this->getDocSpaceUser($currentUrl, $currentLogin, $currentPasswordHash);

				if (!$responseDocSpaceUser['error']) {
					if (!$responseDocSpaceUser['data']['isAdmin']) {
						$result['error'] = self::FORBIDDEN; // Error user is not admin.
						return $result;
					}

					$result['data'] = $currentToken; // Return current token.
					return $result;
				}
			}

			$url = $currentUrl;
			$login = $currentLogin;
			$passwordHash = $currentPasswordHash;
		}

		// Try authentication with new credintails.
		// Try get new token.
		$responseDocSpaceToken = $this->getDocSpaceToken( $url, $login, $passwordHash );

		if ( $responseDocSpaceToken['error'] ) {
			return $responseDocSpaceToken; // Error authentication.
		}

		// Check is admin with new token.
		$responseDocSpaceUser = $this->getDocSpaceUser( $url, $login, $responseDocSpaceToken['data'] );

		if ( $responseDocSpaceUser['error'] ) {
			return $responseDocSpaceUser; // Error getting user data.
		}

		if ( ! $responseDocSpaceUser['data']['isAdmin'] ) {
			$result['error'] = self::FORBIDDEN; // Error user is not admin.
			return $result;
		}

		// $this->config('onlyoffice_docspace.settings')
      	// 	->set('token', $res_authentication['data'])
      	// 	->save();

		$result['data'] = $responseDocSpaceToken['data']; // Return new current token.
		return $result;
	}
  
	/**
	 * {@inheritdoc}
	 */
	public function getDocSpaceUser($url, $login, $token) {
		$result = array(
			'error' => null,
			'data' => null,
		);

		try {
			$response = $this->httpClient->request(
				'GET',
				$url . 'api/2.0/people/email?email=' . $login,
				array(
					'cookies' => $this->createCookieJar(
						array('asc_auth_key' => $token),
						$url
					),
				)
			);
			
			if ($response->getStatusCode() !== 200) {
				$result['error'] = self::USER_NOT_FOUND;
				return $result;
			}

			$body = Json::decode((string) $response->getBody());
			$result['data'] = $body['response'];

			return $result;
		} catch (\Exception $e) {
			$result['error'] = self::USER_NOT_FOUND;
			return $result;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDocSpaceUsers() {
		$result = array(
			'error' => null,
			'data'  => null,
		);

		$responseConnect = $this->connectDocSpace();

		if ( $responseConnect['error'] ) {
			return $responseConnect;
		}

		$url = $this->config('onlyoffice_docspace.settings')->get('url');

		try {
			$response = $this->httpClient->request(
				'GET',
				$url . 'api/2.0/people',
				array(
					'cookies' => $this->createCookieJar(
						array('asc_auth_key' => $responseConnect['data']),
						$url
					),
				)
			);

			if ($response->getStatusCode() !== 200) {
				$result['error'] = self::ERROR_GET_USERS;
				return $result;
			}

			$body = Json::decode((string) $response->getBody());
			$result['data'] = $body['response'];
			
			return $result;
		} catch (\Exception $e) {
			$result['error'] = self::ERROR_GET_USERS;
			return $result;
		}
	}

	/**
	 * Get DocSpace Token.
	 *
	 * @param string $url DocSpace URL.
	 * @param string $login DocSpace User login.
	 * @param string $pass DocSpace User password.
	 */
	private function getDocSpaceToken( $url, $login, $passwordHash ) {
		$result = array(
			'error' => null,
			'data'  => null,
		);
		
		try {
			$response = $this->httpClient->request(
				'POST',
				$url . 'api/2.0/authentication',
				array(
					'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
					'body'    =>Json::encode(
						array(
							'userName'     => $login,
							'passwordHash' => $passwordHash,
						)
					),
					'method'  => 'POST',
				)
			);

			if ($response->getStatusCode() !== 200) {
				$result['error'] = self::UNAUTHORIZED;
				return $result;
			}

			$body = Json::decode((string) $response->getBody());

			$result['data'] = $body['response']['token'];
			return $result;
		} catch (\Exception $e) {
			$result['error'] = self::UNAUTHORIZED;
			return $result;
		}
	}

	private function createCookieJar($cookieArray, $url) {
		return CookieJar::fromArray($cookieArray, parse_url($url, PHP_URL_HOST));
	}
}

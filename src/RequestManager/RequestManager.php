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
use GuzzleHttp\Exception\ClientException;

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
	public function connectDocSpace( $url = null, $login = null, $pass = null ) {
		$result = array(
			'error' => null,
			'data'  => null,
		);

		$current_url = $this->config('onlyoffice_docspace.settings')->get('url');
		$current_login = $this->config('onlyoffice_docspace.settings')->get('login');
		$current_pass  = $this->config('onlyoffice_docspace.settings')->get('password');

		// Try authentication with current credintails, if new credintails equals null or new credintails equals current credintails.
		if (
			( 	null === $url
				&& null === $login
				&& null === $pass )
			|| (
				$current_url === $url
				&& $current_login === $login
				&& $current_pass === $pass
			) ) {

			$current_docspace_token = $this->config('onlyoffice_docspace.settings')->get('token');

			if (!empty($current_docspace_token)) {
				// Check is admin with current token.
				$res_docspace_user = $this->getDocSpaceUser($current_url, $current_login, $current_pass);

				if (!$res_docspace_user['error']) {
					if (!$res_docspace_user['data']['isAdmin']) {
						$result['error'] = self::FORBIDDEN; // Error user is not admin.
						return $result;
					}

					$result['data'] = $current_docspace_token; // Return current token.
					return $result;
				}
			}

			$url = $current_url;
			$login = $current_login;
			$pass = $current_pass;
		}

		// Try authentication with new credintails.
		// Try get new token.
		$res_authentication = $this->getDocSpaceToken( $url, $login, $pass );

		if ( $res_authentication['error'] ) {
			return $res_authentication; // Error authentication.
		}

		// Check is admin with new token.
		$res_docspace_user = $this->getDocSpaceUser( $url, $login, $res_authentication['data'] );

		if ( $res_docspace_user['error'] ) {
			return $res_docspace_user; // Error getting user data.
		}

		if ( ! $res_docspace_user['data']['isAdmin'] ) {
			$result['error'] = self::FORBIDDEN; // Error user is not admin.
			return $result;
		}

		// $this->config('onlyoffice_docspace.settings')
      	// 	->set('token', $res_authentication['data'])
      	// 	->save();

		$result['data'] = $res_authentication['data']; // Return new current token.
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
				$url . 'api/2.0/people/email1?email=' . $login,
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
		} catch (ClientException $e) {
			$result['error'] = self::USER_NOT_FOUND;
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
	private function getDocSpaceToken( $url, $login, $pass ) {
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
							'passwordHash' => $pass,
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
		} catch (ClientException $e) {
			$result['error'] = self::UNAUTHORIZED;
			return $result;
		}
	}

	private function createCookieJar($cookieArray, $url) {
		return CookieJar::fromArray($cookieArray, parse_url($url, PHP_URL_HOST));
	}
}

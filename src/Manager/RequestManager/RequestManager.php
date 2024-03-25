<?php

namespace Drupal\onlyoffice_docspace\Manager\RequestManager;

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

use Drupal\Component\Serialization\Json;
use Drupal\onlyoffice_docspace\Manager\ManagerBase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJar;

/**
 * The ONLYOFFICE DocSpace Request Manager.
 */
class RequestManager extends ManagerBase implements RequestManagerInterface {

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
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client.
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
    $this->logger = $this->getLogger('onlyoffice_docspace');
  }

  /**
   * {@inheritdoc}
   */
  public function connectDocSpace($url = NULL, $login = NULL, $password_hash = NULL) {
    $result = [
      'error' => NULL,
      'data'  => NULL,
    ];

    $currentUrl = rtrim($this->config('onlyoffice_docspace.settings')->get('url'), "/") . '/';
    $currentLogin = $this->config('onlyoffice_docspace.settings')->get('login');
    $currentPasswordHash = $this->config('onlyoffice_docspace.settings')->get('passwordHash');

    // Try authentication with current credintails, if new credintails equals
    // null or new credintails equals current credintails.
    if (($url === NULL &&  $login === NULL && $password_hash === NULL)
      || ($currentUrl === $url && $currentLogin === $login && $currentPasswordHash === $password_hash)) {

      $currentToken = $this->config('onlyoffice_docspace.settings')->get('token');

      if (!empty($currentToken)) {
        // Check is admin with current token.
        $responseDocSpaceUser = $this->getDocSpaceUser($currentUrl, $currentLogin, $currentToken);

        if (!$responseDocSpaceUser['error']) {
          if (!$responseDocSpaceUser['data']['isAdmin']) {
            // Error user is not admin.
            $result['error'] = self::FORBIDDEN;
            return $result;
          }

          // Return current token.
          $result['data'] = $currentToken;
          return $result;
        }
      }

      $url = $currentUrl;
      $login = $currentLogin;
      $password_hash = $currentPasswordHash;
    }

    // Try authentication with new credintails.
    // Try get new token.
    $responseDocSpaceToken = $this->getDocSpaceToken($url, $login, $password_hash);

    if ($responseDocSpaceToken['error']) {
      // Error authentication.
      return $responseDocSpaceToken;
    }

    // Check is admin with new token.
    $responseDocSpaceUser = $this->getDocSpaceUser($url, $login, $responseDocSpaceToken['data']);

    if ($responseDocSpaceUser['error']) {
      // Error getting user data.
      return $responseDocSpaceUser;
    }

    if (!$responseDocSpaceUser['data']['isAdmin']) {
      // Error user is not admin.
      $result['error'] = self::FORBIDDEN;
      return $result;
    }

    $this->config('onlyoffice_docspace.settings')
      ->set('token', $responseDocSpaceToken['data'])
      ->save();

    // Return new current token.
    $result['data'] = $responseDocSpaceToken['data'];
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getDocSpaceUser($url, $login, $token) {
    $result = [
      'error' => NULL,
      'data' => NULL,
    ];

    try {
      $response = $this->httpClient->request(
        'GET',
        $url . 'api/2.0/people/email?email=' . $login,
        [
          'cookies' => $this->createCookieJar(
            ['asc_auth_key' => $token],
            $url
          ),
        ]
      );

      if ($response->getStatusCode() !== 200) {
        $result['error'] = self::USER_NOT_FOUND;
        return $result;
      }

      $body = Json::decode((string) $response->getBody());
      $result['data'] = $body['response'];

      return $result;
    }
    catch (\Exception $e) {
      $result['error'] = self::USER_NOT_FOUND;
      return $result;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDocSpaceUsers() {
    $result = [
      'error' => NULL,
      'data'  => NULL,
    ];

    $responseConnect = $this->connectDocSpace();

    if ($responseConnect['error']) {
      return $responseConnect;
    }

    $url = rtrim($this->config('onlyoffice_docspace.settings')->get('url'), "/") . '/';

    try {
      $response = $this->httpClient->request(
        'GET',
        $url . 'api/2.0/people',
        [
          'cookies' => $this->createCookieJar(
            ['asc_auth_key' => $responseConnect['data']],
            $url
          ),
        ]
      );

      if ($response->getStatusCode() !== 200) {
        $result['error'] = self::ERROR_GET_USERS;
        return $result;
      }

      $body = Json::decode((string) $response->getBody());
      $result['data'] = $body['response'];

      return $result;
    }
    catch (\Exception $e) {
      $result['error'] = self::ERROR_GET_USERS;
      return $result;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function inviteToDocSpace($email, $password_hash, $firstname, $lastname, $type, $token = NULL) {
    $result = [
      'error' => NULL,
      'data'  => NULL,
    ];

    if (!$token) {
      $responseConnect = $this->connectDocSpace();

      if ($responseConnect['error']) {
        return $responseConnect;
      }

      $token = $responseConnect['data'];
    }

    $url = rtrim($this->config('onlyoffice_docspace.settings')->get('url'), "/") . '/';

    try {
      $response = $this->httpClient->request(
        'POST',
        $url . 'api/2.0/people/active',
        [
          'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
          'cookies' => $this->createCookieJar(
            ['asc_auth_key' => $token],
            $url
          ),
          'body' => Json::encode(
            [
              'email' => $email,
              'passwordHash' => $password_hash,
              'firstname' => $firstname,
              'lastname' => $lastname,
              'type' => $type,
            ]
          ),
          'method' => 'POST',
        ]
      );

      if ($response->getStatusCode() !== 200) {
        $result['error'] = self::ERROR_USER_INVITE;
        return $result;
      }

      $body = Json::decode((string) $response->getBody());
      $result['data'] = $body['response'];

      return $result;
    }
    catch (\Exception $e) {
      $result['error'] = self::ERROR_USER_INVITE;
      return $result;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setUserPassword($user_id, $password_hash, $token) {
    $result = [
      'error' => NULL,
      'data'  => NULL,
    ];

    $url = rtrim($this->config('onlyoffice_docspace.settings')->get('url'), "/") . '/';

    try {
      $response = $this->httpClient->request(
        'PUT',
        $url . 'api/2.0/people/' . $user_id . '/password',
        [
          'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
          'cookies' => $this->createCookieJar(
            ['asc_auth_key' => $token],
            $url
          ),
          'body' => Json::encode(
            [
              'passwordHash' => $password_hash,
            ]
          ),
          'method' => 'PUT',
        ]
      );

      if ($response->getStatusCode() !== 200) {
        $result['error'] = self::ERROR_SET_USER_PASS;
        return $result;
      }

      $body = Json::decode((string) $response->getBody());
      $result['data'] = $body['response'];

      return $result;
    }
    catch (\Exception $e) {
      $result['error'] = self::ERROR_SET_USER_PASS;
      return $result;
    }

  }

  /**
   * Get ONLYOFFICE DocSpace Token.
   *
   * @param string $url
   *   The ONLYOFFICE DocSpace URL.
   * @param string $login
   *   The ONLYOFFICE DocSpace User login.
   * @param string $password_hash
   *   The ONLYOFFICE DocSpace User password.
   */
  private function getDocSpaceToken($url, $login, $password_hash) {
    $result = [
      'error' => NULL,
      'data'  => NULL,
    ];

    try {
      $response = $this->httpClient->request(
        'POST',
        $url . 'api/2.0/authentication',
        [
          'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
          'body' => Json::encode(
            [
              'userName'     => $login,
              'passwordHash' => $password_hash,
            ]
          ),
          'method'  => 'POST',
        ]
      );

      if ($response->getStatusCode() !== 200) {
        $result['error'] = self::UNAUTHORIZED;
        return $result;
      }

      $body = Json::decode((string) $response->getBody());

      $result['data'] = $body['response']['token'];
      return $result;
    }
    catch (\Exception $e) {
      $result['error'] = self::UNAUTHORIZED;
      return $result;
    }
  }

  /**
   * Create Cookie Jar.
   */
  private function createCookieJar($cookieArray, $url) {
    return CookieJar::fromArray($cookieArray, parse_url($url, PHP_URL_HOST));
  }

}

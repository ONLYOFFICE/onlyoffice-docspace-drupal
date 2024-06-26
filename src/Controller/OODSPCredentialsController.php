<?php

namespace Drupal\onlyoffice_docspace\Controller;

/**
 * Copyright (c) Ascensio System SIA 2024.
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

use Drupal\Core\Controller\ControllerBase;
use Drupal\onlyoffice_docspace\Manager\SecurityManager\SecurityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for ONLYOFFICE DocSpace Credentials route.
 */
class OODSPCredentialsController extends ControllerBase {
  /**
   * The ONLYOFFICE DocSpace security manager.
   *
   * @var \Drupal\onlyoffice_docspace\Manager\SecurityManager\SecurityManagerInterface
   */
  protected $securityManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs an OODSPCredentialsController object.
   *
   * @param \Drupal\onlyoffice_docspace\Manager\SecurityManager\SecurityManagerInterface $security_manager
   *   The ONLYOFFICE DocSpace security manager.
   */
  public function __construct(SecurityManagerInterface $security_manager) {
    $this->securityManager = $security_manager;
    $this->logger = $this->getLogger('onlyoffice_docspace');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('onlyoffice_docspace.security_manager')
      );
  }

  /**
   * Method for processing credentials.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request entity.
   */
  public function credentials(Request $request) {
    $user = $this->currentUser()->getAccount();

    $body = json_decode($request->getContent());

    if (!$body) {
      $this->logger->error('The request body is missing.');
      return new JsonResponse(
        ['error' => 1, 'message' => 'The request body is missing.'],
        400
      );
    }

    $hash = $this->securityManager->getPasswordHash($user->id());

    if (empty($hash)) {
      return new JsonResponse(NULL, 404);
    }

    return new JsonResponse($hash, 200);
  }

}

<?php

namespace Drupal\onlyoffice_docspace\Manager\ComponentManager;

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

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\onlyoffice_docspace\Controller\OODSPCredentialsController;
use Drupal\onlyoffice_docspace\Manager\ManagerBase;

/**
 * The ONLYOFFICE DocSpace Component Manager.
 */
class ComponentManager extends ManagerBase {

  /**
   * The list of available modules.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $extensionListModule;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   */
  public function __construct(ModuleExtensionList $extension_list_module) {
    $this->extensionListModule = $extension_list_module;
    $this->logger = $this->getLogger('onlyoffice');
  }

  /**
   * Create ONLYOFFICE DocSpace Component.
   * 
   * @param array $build
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   */
  public function buildComponent(array $build, AccountInterface $user) {
    $error_message = 'Portal unavailable! Please contact the administrator!';

        if (true) { //ToDo
            $error_message ='Go to the settings to configure ONLYOFFICE DocSpace connector.';
        }

    $isAnonymous = $user->isAnonymous();
    $email = $isAnonymous ? OODSPCredentialsController::OODSP_PUBLIC_USER_LOGIN : $user->getEmail();

    $build['#attached']['library'][] = 'onlyoffice_docspace/onlyoffice_docspace.component';

    $build['#attached']['drupalSettings']['DocSpaceComponent'] = [
      'currentUser' => $email,
      'isPublic' => $isAnonymous,
      'url' => rtrim($this->config('onlyoffice_docspace.settings')->get('url'),"/").'/',
      'ajaxUrl' => Url::fromRoute('onlyoffice_docspace.credentilas')->setAbsolute()->toString(),
      'loginUrl' => Url::fromRoute('onlyoffice_docspace.page_login')->setAbsolute()->toString(),
      'adminUrl' => Url::fromRoute('onlyoffice_docspace.page')->setAbsolute()->toString(),
      'messages' => [
        'error' => $error_message,
      ],
      'images' => [
        'onlyoffice'  => '/' . $this->extensionListModule->getPath('onlyoffice_docspace') . '/images/onlyoffice.svg',
        'unavailable' => '/' . $this->extensionListModule->getPath('onlyoffice_docspace') . '/images/unavailable.svg',
      ],
    ];

    return $build;
  }
}

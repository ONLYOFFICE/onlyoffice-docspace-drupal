<?php

namespace Drupal\onlyoffice_docspace\Manager\UtilsManager;

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
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\onlyoffice_docspace\Manager\ManagerBase;

/**
 * The ONLYOFFICE DocSpace Utils Manager.
 */
class UtilsManager extends ManagerBase {

  /**
   * Locales for ONLYOFFICE DocSpace.
   */
  public const LOCALES = [
    'az', 'bg', 'cs', 'de', 'el-GR', 'en-GB', 'en-US', 'es', 'fi', 'fr',
    'hy-AM', 'it', 'ja-JP', 'ko-KR', 'lo-LA', 'lv', 'nl', 'pl', 'pt', 'pt-BR',
    'ro', 'ru', 'sk', 'sl', 'tr', 'uk-UA', 'vi', 'zh-CN',
  ];

  /**
   * The list of available modules.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $extensionListModule;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

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
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   */
  public function __construct(ModuleExtensionList $extension_list_module, LanguageManagerInterface $language_manager, CurrentPathStack $current_path) {
    $this->extensionListModule = $extension_list_module;
    $this->languageManager = $language_manager;
    $this->currentPath = $current_path;

    $this->logger = $this->getLogger('onlyoffice_docspace');
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
    $isAnonymous = $user->isAnonymous();
    $currentUser = $user->getEmail();

    $messageDocspaceUnavailable = $this->t('Portal unavailable! Please contact the administrator!');

    if (!$isAnonymous) {
      if ($user->hasPermission('administer onlyoffice_docspace configuration')) {
        $messageDocspaceUnavailable = $this->t('Go to the settings to configure ONLYOFFICE DocSpace connector.');
      }

      $messageUnauthorizedHeader = $this->t('Authorization unsuccessful');
      $messageUnauthorizedMessage = $this->t('Please contact the administrator.');

      if ($user->hasPermission('administer site configuration')) {
        $messageUnauthorizedMessage = $this->t(
          'Please go to <a href="@login_page">ONLYOFFICE DocSpace Login page</a> and enter your password to restore access.',
          [
            '@login_page' => Url::fromRoute(
              'onlyoffice_docspace.page_login',
              [],
              ['query' => ['redirect' => $this->currentPath->getPath()]]
            )->toString(),
          ]
        );
      }
    }
    else {
      $messageUnauthorizedHeader = $this->t('Access denied!');
      $messageUnauthorizedMessage = $this->t('Please log in to the site!');
    }

    $build['#attached']['library'][] = 'onlyoffice_docspace/onlyoffice_docspace.docspace-integration-sdk';
    $build['#attached']['library'][] = 'onlyoffice_docspace/onlyoffice_docspace.utils';

    $build['#attached']['drupalSettings']['OODSP_Settings'] = [
      'currentUser' => $currentUser,
      'isPublic' => $isAnonymous,
      'isAnonymous' => $isAnonymous,
      'url' => rtrim($this->config('onlyoffice_docspace.settings')->get('url'), "/") . '/',
      'ajaxUrl' => Url::fromRoute('onlyoffice_docspace.credentilas')->setAbsolute()->toString(),
      'loginUrl' => Url::fromRoute('onlyoffice_docspace.page_login')->setAbsolute()->toString(),
      'adminUrl' => Url::fromRoute('onlyoffice_docspace.page')->setAbsolute()->toString(),
      'messages' => [
        'docspaceUnavailable' => $messageDocspaceUnavailable,
        'unauthorizedHeader' => $messageUnauthorizedHeader,
        'unauthorizedMessage' => $messageUnauthorizedMessage,
      ],
      'images' => [
        'onlyoffice'  => '/' . $this->extensionListModule->getPath('onlyoffice_docspace') . '/images/onlyoffice-docspace.svg',
        'unavailable' => '/' . $this->extensionListModule->getPath('onlyoffice_docspace') . '/images/unavailable.svg',
        'room-icon'   => '/' . $this->extensionListModule->getPath('onlyoffice_docspace') . '/images/room-icon.svg',
        'file-icon'   => '/' . $this->extensionListModule->getPath('onlyoffice_docspace') . '/images/file-icon.svg',
      ],
      'labels' => [
        'room' => $this->t('DocSpace Room'),
        'file' => $this->t('DocSpace File'),
      ],
      'locale' => $this->getLocaleForDocspace(),
    ];

    return $build;
  }

  /**
   * Return locale for ONLYOFFICE DocSpace.
   */
  public function getLocaleForDocspace() {
    $locale = str_replace('_', '-', $this->languageManager->getCurrentLanguage()->getId());

    if (in_array($locale, self::LOCALES)) {
      return $locale;
    }
    else {
      $locale = explode('-', $locale)[0];
      foreach (self::LOCALES as $value) {
        if (str_starts_with($value, $locale)) {
          return $value;
        }
      }
    }

    return 'en-US';
  }

}

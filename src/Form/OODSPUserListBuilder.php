<?php

namespace Drupal\onlyoffice_docspace\Form;

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
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\onlyoffice_docspace\Manager\RequestManager\RequestManagerInterface;
use Drupal\onlyoffice_docspace\Manager\SecurityManager\SecurityManagerInterface;
use Drupal\user\UserListBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of user entities.
 *
 * @see \Drupal\user\Entity\User
 */
class OODSPUserListBuilder extends UserListBuilder {

  /**
   * Is connected to ONLYOFFICE DocSpace.
   *
   * @var bool
   */
  private $isConnectedToDocSpace = FALSE;

  /**
   * List ONLYOFFICE DocSpace Users.
   *
   * @var array
   */
  private $listDocSpaceUsers = [];

  /**
   * The ONLYOFFICE DocSpace request manager.
   *
   * @var \Drupal\onlyoffice_docspace\Manager\RequestManager\RequestManagerInterface
   */
  protected $requestManager;

  /**
   * The ONLYOFFICE DocSpace security manager.
   *
   * @var \Drupal\onlyoffice_docspace\Manager\SecurityManager\SecurityManagerInterface
   */
  protected $securityManager;

  /**
   * The list of available modules.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $extensionListModule;

  /**
   * The factory for configuration objects.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new OODSPUserListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Drupal\onlyoffice_docspace\Manager\RequestManager\RequestManagerInterface $request_manager
   *   The ONLYOFFICE DocSpace request manager.
   * @param \Drupal\onlyoffice_docspace\Manager\SecurityManager\SecurityManagerInterface $security_manager
   *   The ONLYOFFICE DocSpace security manager.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    DateFormatterInterface $date_formatter,
    RedirectDestinationInterface $redirect_destination,
    RequestManagerInterface $request_manager,
    SecurityManagerInterface $security_manager,
    ModuleExtensionList $extension_list_module,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($entity_type, $storage, $date_formatter, $redirect_destination);

    $this->requestManager = $request_manager;
    $this->securityManager = $security_manager;
    $this->extensionListModule = $extension_list_module;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('redirect.destination'),
      $container->get('onlyoffice_docspace.request_manager'),
      $container->get('onlyoffice_docspace.security_manager'),
      $container->get('extension.list.module'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_query = $this->storage->getQuery();
    $entity_query->accessCheck(TRUE);
    $entity_query->condition('uid', 0, '<>');
    $entity_query->condition('mail', NULL, 'IS NOT NULL');

    try {
      $settins_view_user_admin_people = $this->configFactory->get('views.view.user_admin_people');
      if ($settins_view_user_admin_people) {
        $display = $settins_view_user_admin_people->get('display');
        $items_per_page = ($display['default']['display_options']['pager']['options']['items_per_page']);

        if (!empty($items_per_page)) {
          $entity_query->pager($items_per_page);
        }
      }
    }
    catch (\Exception $e) {

    }

    $header = $this->buildHeader();
    $entity_query->tableSort($header);
    $uids = $entity_query->execute();
    $this->loadDocSpaceUsers();

    return $this->storage->loadMultiple($uids);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = parent::buildHeader();

    unset($header['member_for']);
    unset($header['access']);
    unset($header['operations']);

    $header['docspace_user_status'] = [
      'data' => $this->t('DocSpace User Status'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];

    $header['docspace_user_type'] = [
      'data' => $this->t('DocSpace User Type'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = parent::buildRow($entity);

    unset($row['member_for']);
    unset($row['access']);
    unset($row['operations']);

    $status = $row['status'];
    $row['status'] = [];
    $row['status']['data']['#markup'] = $status;

    $docSpaceUserStatus = -1;
    $docSpaceUserRoleLabel = '';

    for ($i = 0; $i < count($this->listDocSpaceUsers); ++$i) {
      if ($this->listDocSpaceUsers[$i]['email'] === $entity->getEmail()) {
        $docSpaceUserStatus = $this->listDocSpaceUsers[$i]['activationStatus'];
        $docSpaceUserRoleLabel = $this->getDocSpaceUserRoleLabel($this->listDocSpaceUsers[$i]);
      }
    }

    $user_pass = $this->securityManager->getPasswordHash($entity->id());

    if ($docSpaceUserStatus === 0 || $docSpaceUserStatus === 1) {
      if (!empty($user_pass)) {
        $row['docspace_user_status']['data'] = [
          '#type' => 'html_tag',
          '#tag' => 'img',
          '#attributes' => [
            'src' => '/' . $this->extensionListModule->getPath('onlyoffice_docspace') . '/images/done.svg',
          ],
        ];
      }
      else {
        $row['docspace_user_status']['data'] = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => ['tooltip'],
          ],
          'tooltip' => [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => $this->getLabelForUnauthorized(),
            '#attributes' => [
              'class' => ['tooltip-message'],
            ],
          ],
          'status' => [
            '#type' => 'html_tag',
            '#tag' => 'img',
            '#attributes' => [
              'src' => '/' . $this->extensionListModule->getPath('onlyoffice_docspace') . '/images/not_authorization.svg',
            ],
          ],
        ];
      }
    }
    else {
      $row['docspace_user_status']['data']['#markup'] = '';
    }

    $row['docspace_user_type']['data']['#markup'] = $docSpaceUserRoleLabel;

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['onlyoffice_docspace_users'] = $build['table'];
    $build['onlyoffice_docspace_users']['#tableselect'] = TRUE;

    foreach ($build['onlyoffice_docspace_users']['#rows'] as $key => $value) {
      $build['onlyoffice_docspace_users'][$key] = $value;
    }

    unset($build['onlyoffice_docspace_users']['#rows']);
    unset($build['table']);
    unset($build['pager']);

    return $build;
  }

  /**
   * Load users from ONLYOFFICE DocSpace.
   */
  private function loadDocSpaceUsers() {
    $responseDocSpaceUsers = $this->requestManager->getDocSpaceUsers();

    if (!$responseDocSpaceUsers['error']) {
      $this->listDocSpaceUsers = $responseDocSpaceUsers['data'];
      $this->isConnectedToDocSpace = TRUE;
    }
  }

  /**
   * Return label for role ONLYOFFICE DocSpace user.
   *
   * @param string $user
   *   The ONLYOFFICE DocSpace user.
   */
  private function getDocSpaceUserRoleLabel($user) {
    if ($user['isOwner']) {
      return $this->t('Owner');
    }
    elseif ($user['isAdmin']) {
      return $this->t('DocSpace admin');
    }
    elseif ($user['isCollaborator']) {
      return $this->t('Power user');
    }
    elseif ($user['isVisitor']) {
      return $this->t('User');
    }
    else {
      return $this->t('Room admin');
    }
  }

  /**
   * Return label tooltip for unauthorized users.
   */
  private function getLabelForUnauthorized() {
    $output  = '<b>' . $this->t('Problem with the account synchronization between Drupal and ONLYOFFICE DocSpace') . '</b></br></br>';
    $output .= '<b>' . $this->t('Possible cause:') . '</b> ' . $this->t('DocSpace account was not created via the DocSpace plugin for Drupal') . '</br></br>';
    $output .= $this->t('Seamless login is unavailable. Users will need to login into DocSpace to have access to the plugin.');

    return $output;
  }

}

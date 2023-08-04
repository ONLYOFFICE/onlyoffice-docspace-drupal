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

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\onlyoffice_docspace\Manager\RequestManager\RequestManagerInterface;
use Drupal\user\UserListBuilder;

/**
 * Defines a class to build a listing of user entities.
 *
 * @see \Drupal\user\Entity\User
 */
class OODSPUserListBuilder extends UserListBuilder {

  /**
   * Is connected to DocSpace.
   *
   * @var boolean
   */
  private $isConnectedToDocSpace = FALSE;

  /**
   * List DocSpace Users.
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
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter, RedirectDestinationInterface $redirect_destination, RequestManagerInterface $request_manager) {
    parent::__construct($entity_type, $storage, $date_formatter, $redirect_destination);

    $this->requestManager = $request_manager;
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
      $container->get('onlyoffice_docspace.request_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_query = $this->storage->getQuery();
    $entity_query->accessCheck(TRUE);
    $entity_query->condition('uid', 0, '<>');
    $entity_query->pager(10);
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

        for ( $i = 0; $i < count($this->listDocSpaceUsers); ++$i ) {
            if ($this->listDocSpaceUsers[$i]['email'] === $entity->getEmail()) {
                $docSpaceUserStatus= $this->listDocSpaceUsers[$i]['activationStatus'];
                $docSpaceUserRoleLabel = $this->getDocSpaceUserRoleLabel($this->listDocSpaceUsers[$i]);
            }
        }

    // $oodsp_security_manager = new OODSP_Security_Manager();
    // $user_pass = $oodsp_security_manager->get_oodsp_user_pass( $user_object->ID );
    $user_pass = "1111";

    if ( 0 === $docSpaceUserStatus || 1 === $docSpaceUserStatus ) {
      if ( ! empty( $user_pass ) ) {
        $row['docspace_user_status']['data']['#markup'] = $this->t('In DocSpace');
      } else {
        $row['docspace_user_status']['data']['#markup'] = $this->t('Unauthorized');
      }
    } else {
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

    foreach ($build['onlyoffice_docspace_users']['#rows'] as $key => $value ) {
      $build['onlyoffice_docspace_users'][$key] = $value;
    }

    unset($build['onlyoffice_docspace_users']['#rows']);
    unset($build['table']);
    unset($build['pager']);

    return $build;
  }

  private function loadDocSpaceUsers() {
        $responseDocSpaceUsers = $this->requestManager->getDocSpaceUsers();

        if (!$responseDocSpaceUsers['error'] ) {
            $this->listDocSpaceUsers = $responseDocSpaceUsers['data'];
            $this->isConnectedToDocSpace = true;
    }
  }

  /**
     * Return label for role DocSpace user.
     *
     * @param string $user The DocSpace user.
     */
    private function getDocSpaceUserRoleLabel($user) {
        if ($user['isOwner']) {
            return $this->t('Owner');
        } elseif ($user['isAdmin']) {
            return $this->t('DocSpace admin');
        } elseif ($user['isCollaborator'] ) {
            return $this->t('Power user');
        } elseif ($user['isVisitor']) {
            return $this->t('User');
        } else {
            return $this->t('Room admin');
        }
    }
}
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

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\UserListBuilder;

/**
 * Defines a class to build a listing of user entities.
 *
 * @see \Drupal\user\Entity\User
 */
class OODSPUserListBuilder extends UserListBuilder {
  
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
      'field' => 'docspace_user_status',
      'specifier' => 'docspace_user_status',
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];

    $header['docspace_user_type'] = [
      'data' => $this->t('DocSpace User Type'),
      'field' => 'docspace_user_type',
      'specifier' => 'docspace_user_type',
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

    $row['docspace_user_status']['data']['#markup'] = $this->t('In DocSpace');
    $row['docspace_user_type']['data']['#markup'] = $this->t('Room Admin');
    
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

    return $build;
  }
}
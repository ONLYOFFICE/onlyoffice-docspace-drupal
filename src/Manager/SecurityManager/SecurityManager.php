<?php

namespace Drupal\onlyoffice_docspace\Manager\SecurityManager;

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

use Drupal\Core\Database\Connection;
use Drupal\onlyoffice_docspace\Manager\ManagerBase;

/**
 * The ONLYOFFICE DocSpace Security Manager.
 */
class SecurityManager extends ManagerBase implements SecurityManagerInterface {

  /**
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructor a new SecurityManager.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Active database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
    $this->logger = $this->getLogger('onlyoffice_docspace');
  }

  /**
   * {@inheritdoc}
   */
  public function getPasswordHash($user_id) {
    $query = $this->database->select('users_docspace', 't');
    $query->addField('t', 'user_pass');
    $query->condition('t.uid', $user_id);
    return $query->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function setPasswordHash($user_id, $password_hash) {
    $this->database->merge('users_docspace')->key(['uid' => $user_id])->fields(
      [
        'uid' => $user_id,
        'user_pass' => $password_hash,
      ]
    )->execute();
  }

}

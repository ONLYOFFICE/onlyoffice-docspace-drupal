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

/**
 * An interface for ONLYOFFICE DocSpace Security Manager.
 */
interface SecurityManagerInterface {

  /**
   * Get password hash for ONLYOFFICE DocSpace user.
   *
   * @param string $user_id
   *   The user id.
   */
  public function getPasswordHash($user_id);

  /**
   * Set password hash for ONLYOFFICE DocSpace user.
   *
   * @param string $user_id
   *   The user id.
   * @param string $password_hash
   *   The password hash.
   */
  public function setPasswordHash($user_id, $password_hash);

}

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

interface RequestManagerInterface {

	const UNAUTHORIZED = 1;
	const USER_NOT_FOUND = 2;
	const FORBIDDEN = 3;
	const ERROR_USER_INVITE = 4;
	const ERROR_GET_USERS = 5;
	const ERROR_SET_USER_PASS = 6;
	const ERROR_GET_FILE_INFO = 7;
	const ERROR_GET_FOLDER_INFO = 8;
	const ERROR_SHARE_ROOM = 9;

  /**
	 * Connect to ONLYOFFICE DocSapace.
	 *
	 * @param string $url DocSpace URL.
	 * @param string $login DocSpace user login.
	 * @param string $pass DocSpace user password.
	 */
  public function connectDocSpace($url = null, $login = null, $pass = null);

  /**
	 * Get ONLYOFFICE DocSpace user.
	 *
	 * @param string $url DocSpace URL.
	 * @param string $login DocSpace user login.
	 * @param string $token DocSpace token.
	 */
  public function getDocSpaceUser($url, $login, $token);

    /**
	 * Get ONLYOFFICE DocSpace users.
	 *
	 */
	public function getDocSpaceUsers();

	/**
	 * Invite user to ONLYOFFICE DocSpace.
	 * 
	 * @param string $email User email.
	 * @param string $password_hash User password hash.
	 * @param string $firstname User firstname.
	 * @param string $lastname User lastname.
	 * @param string $type User type.
	 * @param string $token DocSpace token.
	 */
	public function inviteToDocSpace($email, $password_hash, $firstname, $lastname, $type, $token = null);
}

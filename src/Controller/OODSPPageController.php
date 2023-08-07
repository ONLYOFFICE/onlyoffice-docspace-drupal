<?php

namespace Drupal\onlyoffice_docspace\Controller;

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

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns response for ONLYOFFICE DocSpace page route.
 */
class OODSPPageController extends ControllerBase {

  /**
   * Method for processing page.
   */
  public function getOnlyofficeDocSpacePage($scheme, Request $request, RouteMatchInterface $route_match) {
    $build = [];

    $build['onlyoffice_docspace-admin-container']['#attached']['library'][] = 'onlyoffice_docspace/onlyoffice_docspace.page';
    $build['onlyoffice_docspace-admin-container']['#theme'] = 'onlyoffice_docspace_page';

    return $build;
  }

}

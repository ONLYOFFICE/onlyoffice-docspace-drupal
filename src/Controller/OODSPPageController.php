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
use Drupal\onlyoffice_docspace\Manager\ComponentManager\ComponentManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns response for ONLYOFFICE DocSpace page route.
 */
class OODSPPageController extends ControllerBase {

  /**
   * The ONLYOFFICE DocSpace Component manager.
   *
   * @var \Drupal\onlyoffice_docspace\Manager\ComponentManager\ComponentManager
   */
  protected $componentManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs an OODSPPageController object.
   *
   * @param \Drupal\onlyoffice_docspace\Manager\ComponentManager\ComponentManager $component_manager
   *   The ONLYOFFICE DocSpace Request manager.
   */
  public function __construct(ComponentManager $component_manager) {
    $this->componentManager = $component_manager;
    $this->logger = $this->getLogger('onlyoffice_docspace');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('onlyoffice_docspace.component_manager')
    );
  }

  /**
   * Method for processing page.
   */
  public function getOnlyofficeDocSpacePage($scheme, Request $request, RouteMatchInterface $route_match) {
    $build = [];
    $build['onlyoffice_docspace-admin-container'] = [];

    $build['onlyoffice_docspace-admin-container'] = $this->componentManager->buildComponent($build['onlyoffice_docspace-admin-container'], $this->currentUser());
    
    $build['onlyoffice_docspace-admin-container']['#theme'] = 'onlyoffice_docspace_page';
    $build['onlyoffice_docspace-admin-container']['#attached']['library'][] = 'onlyoffice_docspace/onlyoffice_docspace.page';

    return $build;
  }

}

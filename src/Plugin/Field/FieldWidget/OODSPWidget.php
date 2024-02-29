<?php

namespace Drupal\onlyoffice_docspace\Plugin\Field\FieldWidget;

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
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\onlyoffice_docspace\Manager\UtilsManager\UtilsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'onlyoffice_docspace_widget' widget.
 *
 * @FieldWidget(
 *   id = "onlyoffice_docspace_widget",
 *   label = @Translation("ONLYOFFICE DocSpace"),
 *   field_types = {
 *     "onlyoffice_docspace"
 *   }
 * )
 */
class OODSPWidget extends WidgetBase {

  /**
   * The ONLYOFFICE DocSpace Utils manager.
   *
   * @var \Drupal\onlyoffice_docspace\Manager\UtilsManager\UtilsManager
   */
  protected $utilsManager;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The list of available modules.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $extensionListModule;

  /**
   * Constructs a MediaLibraryWidget widget.
   *
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\onlyoffice_docspace\Manager\UtilsManager\UtilsManager $utils_manager
   *   The ONLYOFFICE DocSpace Utils manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    UtilsManager $utils_manager,
    AccountInterface $current_user,
    ConfigFactoryInterface $config_factory,
    ModuleExtensionList $extension_list_module
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->utilsManager = $utils_manager;
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->extensionListModule = $extension_list_module;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('onlyoffice_docspace.utils_manager'),
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('extension.list.module'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['#type'] = 'fieldset';
    $element['#attributes'] = [
      'class' => ['onlyoffice-docspace-widget'],
    ];

    $element = $this->utilsManager->buildComponent($element, $this->currentUser);
    $element['#attached']['library'][] = 'onlyoffice_docspace/onlyoffice_docspace.widget';

    $element['target_id'] = [
      '#type' => 'hidden',
      '#default_value' => $items[$delta]->target_id ?? '',
    ];

    $element['type'] = [
      '#type' => 'hidden',
      '#default_value' => $items[$delta]->type ?? '',
    ];

    $element['title'] = [
      '#type' => 'hidden',
      '#default_value' => $items[$delta]->title ?? '',
    ];

    $element['image'] = [
      '#type' => 'hidden',
      '#default_value' => $items[$delta]->image ?? '',
    ];

    $element['fields'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      'field' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => ['oodsp-fields'],
        ],
        'image' => [
          '#type' => 'html_tag',
          '#tag' => 'img',
          '#attributes' => [
            'src' => $this->getAbsoluteDocSpaceUrl($items[$delta]->image ?? ''),
            'width' => '100',
            'height' => '100',
            'class' => ['oodsp-image'],
            'onerror' => 'if (this.src != "' . $this->getDefaultWidgetImage($items[$delta]->type) . '") this.src = "' . $this->getDefaultWidgetImage($items[$delta]->type) . '";',
          ],
          '#weight' => -12,
        ],
        'items' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => [
            'class' => ['oodsp-container-items'],
          ],
          'title' => [
            '#type' => 'textfield',
            '#title' => new TranslatableMarkup('Title'),
            '#default_value' => $items[$delta]->title ?? '',
            '#disabled' => TRUE,
            '#maxlength' => 1024,
            '#weight' => -11,
            '#wrapper_attributes' => [
              'id' => 'title',
              'class' => ['oodsp-container-inline'],
            ],
          ],
        ],
      ],
      'remove' => [
        '#type' => 'button',
        '#value' => $this->t('Remove'),
        '#attributes' => [
          'class' => ['oodsp-remove-button'],
        ],
      ],
    ];

    $element['buttons'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['oodsp-buttons'],
      ],
      'select_room' => [
        '#type' => 'button',
        '#value' => $this->t('Select room'),
        '#attributes' => [
          'class' => ['oodsp-select-button'],
          'data-mode' => 'room-selector',
          'data-title' => $this->t('Select room'),
        ],
      ],
      'select_file' => [
        '#type' => 'button',
        '#value' => $this->t('Select file'),
        '#attributes' => [
          'class' => ['oodsp-select-button'],
          'data-mode' => 'file-selector',
          'data-title' => $this->t('Select file'),
        ],
      ],
    ];

    if (isset($items[$delta]->target_id)) {
      $element['buttons']['#attributes']['class'][] = 'hidden';
    }
    else {
      $element['fields']['#attributes']['class'][] = 'hidden';
    }

    return $element;
  }

  /**
   * Return absolute ONLYOFFICE DocSpace URL.
   *
   * @param string $url
   *   The url.
   */
  private function getAbsoluteDocSpaceUrl($url) {
    return rtrim($this->configFactory->get('onlyoffice_docspace.settings')->get('url'), "/") . parse_url($url)['path'];
  }

  /**
   * Returns default widget image.
   *
   * @param string $type
   *   The item type.
   */
  private function getDefaultWidgetImage($type) {
    $images = [
      'manager' => '/' . $this->extensionListModule->getPath('onlyoffice_docspace') . '/images/room-icon.svg',
      'editor' => '/' . $this->extensionListModule->getPath('onlyoffice_docspace') . '/images/file-icon.svg',
    ];

    return $images[$type];
  }

}

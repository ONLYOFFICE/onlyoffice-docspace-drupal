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
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\onlyoffice_docspace\Manager\ComponentManager\ComponentManager;
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
   * The ONLYOFFICE DocSpace Component manager.
   *
   * @var \Drupal\onlyoffice_docspace\Manager\ComponentManager\ComponentManager
   */
  protected $componentManager;

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
   * @param \Drupal\onlyoffice_docspace\Manager\ComponentManager\ComponentManager $component_manager
   *   The ONLYOFFICE DocSpace Request manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ComponentManager $component_manager, AccountInterface $current_user, ConfigFactoryInterface $config_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->componentManager = $component_manager;
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
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
      $container->get('onlyoffice_docspace.component_manager'),
      $container->get('current_user'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['#type'] = 'fieldset';
    $element = $this->componentManager->buildComponent($element, $this->currentUser);
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

  private function getAbsoluteDocSpaceUrl($url) {
    return rtrim($this->configFactory->get('onlyoffice_docspace.settings')->get('url'), "/") . parse_url($url)['path'];
  }

}

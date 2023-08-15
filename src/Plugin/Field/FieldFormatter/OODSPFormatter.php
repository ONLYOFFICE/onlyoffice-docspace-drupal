<?php

namespace Drupal\onlyoffice_docspace\Plugin\Field\FieldFormatter;

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
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\onlyoffice_docspace\Manager\ComponentManager\ComponentManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'onlyoffice_docspace' formatter.
 *
 * @FieldFormatter(
 *   id = "onlyoffice_docspace",
 *   label = @Translation("ONLYOFFICE DocSpace"),
 *   description = @Translation("Displaying ONLYOFFICE DocSapce."),
 *   field_types = {
 *     "onlyoffice_docspace"
 *   }
 * )
 */
class OODSPFormatter extends FormatterBase {

  /**
   * The ONLYOFFICE DocSpace Component manager.
   *
   * @var \Drupal\onlyoffice_docspace\Manager\ComponentManager\ComponentManager
   */
  protected $componentManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The page cache disabling policy.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $pageCacheKillSwitch;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Construct the OnlyofficePreviewFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\onlyoffice_docspace\Manager\ComponentManager\ComponentManager $component_manager
   *   The ONLYOFFICE DocSpace Component manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $page_cache_kill_switch
   *   The page cache disabling policy.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    ComponentManager $component_manager,
    DateFormatterInterface $date_formatter,
    LanguageManagerInterface $language_manager,
    KillSwitch $page_cache_kill_switch,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->componentManager = $component_manager;
    $this->dateFormatter = $date_formatter;
    $this->languageManager = $language_manager;
    $this->pageCacheKillSwitch = $page_cache_kill_switch;

    $this->logger = \Drupal::service('logger.factory')->get('onlyoffice_docspace');
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('onlyoffice_docspace.component_manager'),
      $container->get('date.formatter'),
      $container->get('language_manager'),
      $container->get('page_cache_kill_switch'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return true;
  }

    /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'width_unit' => '%',
      'width' => 100,
      'height_unit' => 'px',
      'height' => 640,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return parent::settingsForm($form, $form_state) + [
      'width_unit' => [
        '#type' => 'radios',
        '#title' => $this->t('Width units'),
        '#default_value' => $this->getSetting('width_unit'),
        '#options' => [
          '%' => $this->t('Percents'),
          'px' => $this->t('Pixels'),
        ],
      ],
      'width' => [
        '#type' => 'number',
        '#title' => $this->t('Width'),
        '#default_value' => $this->getSetting('width'),
        '#size' => 5,
        '#maxlength' => 5,
        '#min' => 0,
        '#required' => TRUE,
      ],
      'height_unit' => [
        '#type' => 'radios',
        '#title' => $this->t('Height units'),
        '#default_value' => $this->getSetting('height_unit'),
        '#options' => [
          '%' => $this->t('Percents'),
          'px' => $this->t('Pixels'),
        ],
      ],
      'height' => [
        '#type' => 'number',
        '#title' => $this->t('Height'),
        '#default_value' => $this->getSetting('height'),
        '#size' => 5,
        '#maxlength' => 5,
        '#min' => 0,
        '#required' => TRUE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Width')->render() . ': ' . $this->getSetting('width') . $this->getSetting('width_unit');
    $summary[] = $this->t('Height')->render() . ': ' . $this->getSetting('height') . $this->getSetting('height_unit');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $this->pageCacheKillSwitch->trigger();

    $element = [];
    $element = $this->componentManager->buildComponent($element, \Drupal::currentUser()->getAccount());

    $element['#attached']['library'][] = 'onlyoffice_docspace/onlyoffice_docspace.formater';

    foreach ($items as $delta => $item) {
      $editorId = sprintf(
        'onlyoffice-docpace-block-%s',
        $delta,
      );

      $editor_width = $this->getSetting('width') . $this->getSetting('width_unit');
      $editor_height = $this->getSetting('height') . $this->getSetting('height_unit');

      $config = [
        'frameId' => $editorId,
        'id' => $item->target_id,
        'mode' => $item->type,
        'width' => $editor_width,
        'height' => $editor_height,
      ];

      $element[$delta] = [
        '#markup' => sprintf('<div id="%s" class="onlyoffice-docspace-block"></div>', $editorId),
        '#cache' => [
          'max-age' => 0,
        ],
      ];

      $element['#attached']['drupalSettings']['OODSP'][$editorId] = $config;

    }

    return $element;
  }

}

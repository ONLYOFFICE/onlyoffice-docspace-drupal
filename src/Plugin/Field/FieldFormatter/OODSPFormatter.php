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
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\file\Entity\File;
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
    DateFormatterInterface $date_formatter,
    LanguageManagerInterface $language_manager,
    KillSwitch $page_cache_kill_switch,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

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
      $container->get('date.formatter'),
      $container->get('language_manager'),
      $container->get('page_cache_kill_switch'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // if (!parent::isApplicable($field_definition)) {
    //   return FALSE;
    // }

    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $this->pageCacheKillSwitch->trigger();

    $element = [
      '#attached' => [
        'library' => [
          'onlyoffice_docspace/onlyoffice_docspace.formater',
        ],
      ],
    ];

    foreach ($items as $delta => $item) {
      $config = [
        'id' => $item->target_id,
        'mode' => $item->type,
      ];

      $editorId = sprintf(
        'onlyoffice-docpace-block-%s',
        $delta,
      );

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

  // /**
  //  * Method getting configuration for document editor service.
  //  */
  // private function getEditorConfig(File $file) {

    
  // }

  /**
   * {@inheritdoc}
   */
  // protected function needsEntityLoad(EntityReferenceItem $item) {
  //   return parent::needsEntityLoad($item) && $item->isDisplayed();
  // }

  // /**
  //  * {@inheritdoc}
  //  */
  // protected function checkAccess(EntityInterface $entity) {
  //   // Only check access if the current file access control handler explicitly
  //   // opts in by implementing FileAccessFormatterControlHandlerInterface.

  //   return AccessResult::allowed();
  // }

}

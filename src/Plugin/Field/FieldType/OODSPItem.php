<?php

namespace Drupal\onlyoffice_docspace\Plugin\Field\FieldType;

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

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'onlyoffice_docspace' field type.
 *
 * @FieldType(
 *   id = "onlyoffice_docspace",
 *   label = @Translation("ONLYOFFICE DocSpace"),
 *   description = @Translation("This field stores a ONLYOFFICE DocSpace in the database."),
 *   default_widget = "onlyoffice_docspace_widget",
 *   default_formatter = "onlyoffice_docspace"
 * )
 */
class OODSPItem extends FieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @var array
   */
  public static $propertyDefinitions;

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['target_id'] = DataDefinition::create('integer')
      ->setLabel(t('Id'));

    $properties['title'] = DataDefinition::create('string')
      ->setLabel(t('Title'));

    $properties['type'] = DataDefinition::create('string')
      ->setLabel(t('Type'));

    $properties['image'] = DataDefinition::create('string')
      ->setLabel(t('Image'));

    $properties['request_token'] = DataDefinition::create('string')
      ->setLabel(t('Request Token'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'target_id' => [
          'description' => 'The ID of the DocSpace entity',
          'type' => 'int',
          'unsigned' => TRUE,
        ],
        'title' => [
          'description' => "DocSpace entity title",
          'type' => 'varchar',
          'length' => 1024,
        ],
        'type' => [
          'description' => "DocSpace entity type",
          'type' => 'varchar',
          'length' => 8,
        ],
        'image' => [
          'description' => "Image url for DocSpace entity",
          'type' => 'varchar',
          'length' => 1024,
        ],
        'request_token' => [
          'description' => "DocSpace entity requestToken",
          'type' => 'varchar',
          'length' => 1024,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->target_id === NULL || $this->target_id === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'target_id';
  }

}

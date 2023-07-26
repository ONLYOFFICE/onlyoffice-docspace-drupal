<?php

namespace Drupal\onlyoffice_docspace\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;


/**
 * Defines the 'onlyoffice_docspace' field type.
 *
 * @FieldType(
 *   id = "onlyoffice_docspace",
 *   label = @Translation("ONLYOFFICE DocSpace"),
 *   description = @Translation("This field stores a ONLYOFFICE DocSpace in the database."),
 *   default_widget = "onlyoffice_docspace_widget",
 *   default_formatter = "basic_string"
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

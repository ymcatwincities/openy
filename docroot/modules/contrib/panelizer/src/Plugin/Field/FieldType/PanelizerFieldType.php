<?php

namespace Drupal\panelizer\Plugin\Field\FieldType;

use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Plugin implementation of the 'panelizer' field type.
 *
 * @FieldType(
 *   id = "panelizer",
 *   label = @Translation("Panelizer"),
 *   description = @Translation("Panelizer"),
 *   default_widget = "panelizer",
 *   default_formatter = "panelizer"
 * )
 */
class PanelizerFieldType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['view_mode'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('View mode'))
      ->setSetting('case_sensitive', FALSE)
      ->setRequired(TRUE);
    $properties['default'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Default name'))
      ->setSetting('case_sensitive', FALSE)
      ->setRequired(FALSE);
    $properties['panels_display'] = MapDataDefinition::create('map')
      ->setLabel(new TranslatableMarkup('Panels display'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * @inheritDoc
   */
  public static function mainPropertyName() {
    return 'panels_display';
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'view_mode' => [
          'type' => 'varchar',
          'length' => '255',
          'binary' => FALSE,
        ],
        'default' => [
          'type' => 'varchar',
          'length' => '255',
          'binary' => FALSE,
        ],
        'panels_display' => [
          'type' => 'blob',
          'size' => 'normal',
          'serialize' => TRUE,
        ],
      ],
      'indexes' => [
        'default' => ['default'],
      ]
    ];

    return $schema;
  }

  /**
   * Returns the Panels display plugin manager.
   *
   * @return \Drupal\panels\PanelsDisplayManagerInterface
   */
  protected static function getPanelsDisplayManager() {
    return \Drupal::service('panels.display_manager');
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $panels_manager = static::getPanelsDisplayManager();
    $sample_display = $panels_manager->createDisplay();

    $values['view_mode'] = 'default';
    $values['default'] = NULL;
    $values['panels_display'] = $panels_manager->exportDisplay($sample_display);
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $panels_display = $this->get('panels_display')->getValue();
    $default = $this->get('default')->getValue();
    return empty($panels_display) && empty($default);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    $panels_manager = $this->getPanelsDisplayManager();
    $panels_display_config = $this->get('panels_display')->getValue();

    // If our field has custom panelizer display config data.
    if (!empty($panels_display_config) && is_array($panels_display_config)) {
      $panels_display = $panels_manager->importDisplay($panels_display_config, FALSE);
    }
    if (!empty($panels_display)) {
      // Set the storage id to include the current revision id.
      $entity = $this->getEntity();
      $storage_id_parts = [
        $entity->getEntityTypeId(),
        $entity->id(),
        $this->get('view_mode')->getValue()
      ];
      if ($entity instanceof RevisionableInterface && $entity->getEntityType()->isRevisionable()) {
        $storage_id_parts[] = $entity->getRevisionId();
      }
      $panels_display->setStorage('panelizer_field', implode(':', $storage_id_parts));
      $this->set('panels_display', $panels_manager->exportDisplay($panels_display));

      return TRUE;
    }
  }

}

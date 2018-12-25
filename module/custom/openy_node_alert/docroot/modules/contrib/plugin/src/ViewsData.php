<?php

namespace Drupal\plugin;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\plugin\PluginType\PluginTypeManagerInterface;

/**
 * Provides/alters Views data.
 */
class ViewsData {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The plugin type manager.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeManagerInterface
   */
  protected $pluginTypeManager;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\plugin\PluginType\PluginTypeManagerInterface
   *   The plugin type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, PluginTypeManagerInterface $plugin_type_manager) {
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->pluginTypeManager = $plugin_type_manager;
  }

  /**
   * Implements hook_views_data_alter().
   */
  public function alterViewsData(array &$data) {
    // We need to work with entity type database table mappings, which are
    // available per entity type.
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      // Skip non-fieldable entity types.
      if (!$entity_type->isSubclassOf(FieldableEntityInterface::class)) {
        continue;
      }

      $base_field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type->id());
      $entity_storage = $this->entityTypeManager
        ->getStorage($entity_type->id());

      // We cannot alter Views data if we cannot map fields to tables.
      if (!($entity_storage instanceof SqlEntityStorageInterface)) {
        continue;
      }

      $table_mapping = $entity_storage->getTableMapping();

      // Loop through all of this entity type's stored fields.
      foreach ($table_mapping->getTableNames() as $table_name) {
        foreach ($table_mapping->getFieldNames($table_name) as $field_name) {
          // Skip fields that are no base fields.
          if (!isset($base_field_definitions[$field_name])) {
            continue;
          }

          $base_field_storage_definition = $base_field_definitions[$field_name]->getFieldStorageDefinition();

          // Skip if this is no "plugin" base field.
          if (strpos($base_field_storage_definition->getType(), 'plugin:') !== 0) {
            continue;
          }

          // Get the column names.
          if ($base_field_storage_definition->getCardinality() == 1) {
            $plugin_id_column_name = $table_mapping->getFieldColumnName($base_field_storage_definition, 'plugin_id');
            $plugin_configuration_column_name = $table_mapping->getFieldColumnName($base_field_storage_definition, 'plugin_configuration');
          }
          else {
            $plugin_id_column_name = $base_field_storage_definition->getName() . '__plugin_id';
            $plugin_configuration_column_name = $base_field_storage_definition->getName() . '__plugin_configuration';
          }

          $this->alterPluginFieldData($data, $base_field_storage_definition, $table_name, $plugin_id_column_name, $plugin_configuration_column_name);
        }
      }
    }
  }

  /**
   * Implements hook_field_views_data_alter().
   */
  public function alterFieldViewsData(array &$data, FieldStorageConfigInterface $field_storage) {
    // Alters Views data for configurable "plugin" fields.
    if (strpos($field_storage->getType(), 'plugin:') === 0) {
      $table_name = $field_storage->getTargetEntityTypeId() . '__' . $field_storage->getName();
      $plugin_id_column_name = $field_storage->getName() . '_plugin_id';
      $plugin_configuration_column_name = $field_storage->getName() . '_plugin_configuration';

      // Skip if there is no Views data for this field.
      if (!isset($data[$table_name][$plugin_id_column_name])) {
        return;
      }

      $this->alterPluginFieldData($data, $field_storage, $table_name, $plugin_id_column_name, $plugin_configuration_column_name);
    }
  }

  /**
   * Alters Views data for the "plugin" field type.
   *
   * @param array[] $data
   *   An array of Views data.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage_definition
   *   The storage definition of the field to alter the data for.
   * @param string $table_name
   *   The name of the table to alter the data for.
   * @param string $plugin_id_column_name
   *   The name of the plugin ID column.
   * @param string|null $plugin_configuration_column_name
   *   The name of the plugin configuration column, or NULL if there is none.
   *
   * @throws \InvalidArgumentException
   */
  protected function alterPluginFieldData(array &$data, FieldStorageDefinitionInterface $field_storage_definition, $table_name, $plugin_id_column_name, $plugin_configuration_column_name = NULL) {
    if (strpos($field_storage_definition->getType(), 'plugin:')) {
      throw new \InvalidArgumentException('The Views data being altered is not for a "plugin" field.');
    }

    // Do nothing if no Views data exists. This is the case for entity types
    // without Views data handlers, for instance.
    if (!isset($data[$table_name][$plugin_id_column_name])) {
      return;
    }

    $plugin_type_id = substr($field_storage_definition->getType(), 7);
    $plugin_type = $this->pluginTypeManager->getPluginType($plugin_type_id);

    // Alter the plugin ID column.
    /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $plugin_id_old_title */
    $plugin_id_old_title = $data[$table_name][$plugin_id_column_name]['title'];
    $plugin_id_title_arguments = [
      '@type_label' => $plugin_type->getLabel(),
      '@name' => $field_storage_definition->getName(),
      '@column' => $plugin_id_column_name,
    ];
    $data[$table_name][$plugin_id_column_name]['title'] = new TranslatableMarkup('@type_label ID (@name:@column)', $plugin_id_title_arguments, $plugin_id_old_title->getOptions());
    $data[$table_name][$plugin_id_column_name]['filter']['id'] = 'plugin_id';
    $data[$table_name][$plugin_id_column_name]['filter']['plugin_type_id'] = $plugin_type_id;

    // Alter the plugin configuration column. We cannot display it, or filter by
    // it, so we remove it to prevent confusion.
    if ($plugin_configuration_column_name) {
      unset($data[$table_name][$plugin_configuration_column_name]);
    }
  }

}

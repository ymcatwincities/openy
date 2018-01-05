<?php

namespace Drupal\file_entity;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\file\FileStorageSchema;

/**
 * Extends the file storage schema handler.
 */
class FileEntityStorageSchema extends FileStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    if ($storage_definition->getName() == 'type') {
      $schema['fields']['type']['initial'] = FILE_TYPE_NONE;
    }
    return $schema;
  }

}

<?php

namespace Drupal\openy_tools;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Class FieldHelper.
 */
class FieldHelper implements FieldHelperInterface {

  /**
   * {@inheritdoc}
   */
  public function remove($entityTypeId, $bundles, $fieldName, $backup = TRUE) {
    if ($backup) {
      $db = \Drupal::database();
      $db->query("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");
      $db->query(sprintf("DROP TABLE IF EXISTS {copy_%s__%s}", $entityTypeId, $fieldName));
      $db->query(sprintf("CREATE TABLE {copy_%s__%s} LIKE %s__%s", $entityTypeId, $fieldName, $entityTypeId, $fieldName));
      $db->query(sprintf("INSERT INTO {copy_%s__%s} SELECT * FROM {%s__%s}", $entityTypeId, $fieldName, $entityTypeId, $fieldName));
    }

    try {
      // Delete field.
      foreach ($bundles as $bundle) {
        if ($field_config = FieldConfig::loadByName($entityTypeId, $bundle, $fieldName)) {
          $field_config->delete();
        }
      }
    } catch (\Exception $e) {
      watchdog_exception('openy_tools', $e);
      return FALSE;
    }

    try {
      // Delete field storage.
      if ($field_storage_config = FieldStorageConfig::loadByName($entityTypeId, $fieldName)) {
        $field_storage_config->delete();
      }
    }
    catch (\Exception $e) {
      watchdog_exception('openy_tools', $e);
      return FALSE;
    }

    return TRUE;
  }

}

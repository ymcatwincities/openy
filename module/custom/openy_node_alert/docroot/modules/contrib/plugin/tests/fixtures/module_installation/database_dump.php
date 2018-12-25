<?php

/**
 * @file
 * Contains a database dump to mimic a Plugin installation.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Set the schema version.
$connection->insert('key_value')
  ->fields([
    'collection' => 'system.schema',
    'name' => 'plugin',
    'value' => 'i:8000;',
  ])
  ->execute();
$connection->insert('key_value')
  ->fields([
    'collection' => 'system.schema',
    'name' => 'plugin_test_helper',
    'value' => 'i:8000;',
  ])
  ->execute();

// Update core.extension.
$extensions = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['plugin'] = 8000;
$extensions['module']['plugin_test_helper'] = 8000;
$connection->update('config')
  ->fields([
    'data' => serialize($extensions),
  ])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();

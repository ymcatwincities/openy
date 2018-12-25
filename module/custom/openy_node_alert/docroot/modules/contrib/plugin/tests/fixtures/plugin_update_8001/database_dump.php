<?php

/**
 * @file
 * Updates the database with fixtures to test plugin_update_8001().
 */

use Drupal\Core\Database\Database;
use Drupal\Component\Serialization\Yaml;
use Drupal\field\Entity\FieldStorageConfig;

$connection = Database::getConnection();

// Create the "Plugin selector" plugin field storage.
$config = Yaml::decode(file_get_contents(__DIR__ . '/config/field.storage.user.field_plugin_selector.yml'));
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'field.storage.' . $config['id'],
    'data' => serialize($config),
  ])
  ->execute();
// We need to Update the registry of "last installed" field definitions.
$installed = $connection->select('key_value')
  ->fields('key_value', ['value'])
  ->condition('collection', 'entity.definitions.installed')
  ->condition('name', 'user.field_storage_definitions')
  ->execute()
  ->fetchField();
$installed = unserialize($installed);
$installed['field_plugin_selector'] = new FieldStorageConfig($config);
$connection->update('key_value')
  ->condition('collection', 'entity.definitions.installed')
  ->condition('name', 'user.field_storage_definitions')
  ->fields([
    'value' => serialize($installed)
  ])
  ->execute();

// Create the "Plugin selector" plugin field.
$config = Yaml::decode(file_get_contents(__DIR__ . '/config/field.field.user.user.field_plugin_selector.yml'));
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'field.field.' . $config['id'],
    'data' => serialize($config),
  ])
  ->execute();

// Create the table for the "Plugin selector" plugin field's values.
$connection->schema()->createTable('user__field_plugin_selector', array(
  'fields' => array(
    'bundle' => array(
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ),
    'deleted' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
      'default' => '0',
    ),
    'entity_id' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'revision_id' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'langcode' => array(
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ),
    'delta' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'field_plugin_selector_plugin_id' => array(
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '255',
    ),
    'field_plugin_selector_plugin_configuration' => array(
      'type' => 'blob',
      'not null' => FALSE,
      'size' => 'normal',
    ),
  ),
  'primary key' => array(
    'entity_id',
    'deleted',
    'delta',
    'langcode',
  ),
  'indexes' => array(
    'bundle' => array(
      'bundle',
    ),
    'revision_id' => array(
      'revision_id',
    ),
  ),
  'mysql_character_set' => 'utf8mb4',
));

// Create the "Mock plugin" plugin field storage.
$config = Yaml::decode(file_get_contents(__DIR__ . '/config/field.storage.user.field_plugin_test_helper_mock.yml'));
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'field.storage.' . $config['id'],
    'data' => serialize($config),
  ])
  ->execute();
// We need to Update the registry of "last installed" field definitions.
$installed = $connection->select('key_value')
  ->fields('key_value', ['value'])
  ->condition('collection', 'entity.definitions.installed')
  ->condition('name', 'user.field_storage_definitions')
  ->execute()
  ->fetchField();
$installed = unserialize($installed);
$installed['field_plugin_test_helper_mock'] = new FieldStorageConfig($config);
$connection->update('key_value')
  ->condition('collection', 'entity.definitions.installed')
  ->condition('name', 'user.field_storage_definitions')
  ->fields([
    'value' => serialize($installed)
  ])
  ->execute();

// Create the "Mock plugin" plugin field.
$config = Yaml::decode(file_get_contents(__DIR__ . '/config/field.field.user.user.field_plugin_test_helper_mock.yml'));
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'field.field.' . $config['id'],
    'data' => serialize($config),
  ])
  ->execute();

// Create the table for the "Mock plugin" plugin field's values.
$connection->schema()->createTable('user__field_plugin_test_helper_mock', array(
  'fields' => array(
    'bundle' => array(
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '128',
      'default' => '',
    ),
    'deleted' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'tiny',
      'default' => '0',
    ),
    'entity_id' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'revision_id' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'langcode' => array(
      'type' => 'varchar_ascii',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ),
    'delta' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'field_plugin_test_helper_mock_plugin_id' => array(
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '255',
    ),
    'field_plugin_test_helper_mock_plugin_configuration' => array(
      'type' => 'blob',
      'not null' => FALSE,
      'size' => 'normal',
    ),
  ),
  'primary key' => array(
    'entity_id',
    'deleted',
    'delta',
    'langcode',
  ),
  'indexes' => array(
    'bundle' => array(
      'bundle',
    ),
    'revision_id' => array(
      'revision_id',
    ),
  ),
  'mysql_character_set' => 'utf8mb4',
));

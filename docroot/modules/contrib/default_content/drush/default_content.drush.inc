<?php

/**
 * @file
 * Drush integration for the default_content module.
 */

/**
 * Implements hook_drush_command().
 */
function default_content_drush_command() {
  $items['default-content-export'] = [
    'description' => dt('Exports a single entity'),
    'arguments' => [
      'entity_type' => dt('The entity type to export.'),
      'entity_id' => dt('The ID of the entity to export.'),
    ],
    'options' => [
      'file' => dt('Write out the exported content to a file instead of stdout'),
    ],
    'aliases' => ['dce'],
    'required-arguments' => 2,
  ];
  $items['default-content-export-references'] = [
    'description' => dt('Exports an entity and all its referenced entities.'),
    'arguments' => [
      'entity_type' => dt('The entity type to export.'),
      'entity_id' => dt('The ID of the entity to export.'),
    ],
    'options' => [
      'folder' => dt('Folder to export to, entities are grouped by entity type into directories.'),
    ],
    'aliases' => ['dcer'],
    'required-arguments' => 2,
  ];

  return $items;
}

/**
 * Exports a piece of content into the stdout or into a file.
 *
 * @param string $entity_type_id
 *   The entity type ID.
 * @param $entity_id
 *   The entity ID to export.
 */
function drush_default_content_export($entity_type_id, $entity_id) {
  /** @var \Drupal\default_content\DefaultContentManagerInterface $manager */
  $manager = \Drupal::service('default_content.manager');
  $export = $manager->exportContent($entity_type_id, $entity_id);

  if ($file = drush_get_option('file')) {
    file_put_contents($file, $export);
  }
  else {
    drush_print($export);
  }
}

/**
 * Exports a piece of content and all its referenced entities.
 *
 * @param string $entity_type_id
 *   The entity type ID.
 * @param mixed $entity_id
 *   The entity ID to export.
 */
function drush_default_content_export_references($entity_type_id, $entity_id) {
  /** @var \Drupal\default_content\DefaultContentManagerInterface $manager */
  $manager = \Drupal::service('default_content.manager');

  $folder = drush_get_option('folder', '.');

  $serialized_by_type = $manager->exportContentWithReferences($entity_type_id, $entity_id);
  foreach ($serialized_by_type as $entity_type => $serialized_entities) {
    // Ensure that the folder per entity type exists.
    $entity_type_folder = "$folder/$entity_type";
    file_prepare_directory($entity_type_folder, FILE_CREATE_DIRECTORY);
    foreach ($serialized_entities as $entity_id => $serialized_entity) {
      file_put_contents($entity_type_folder . '/' . $entity_id . '.json', $serialized_entity);
    }
  }
}

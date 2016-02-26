<?php
/**
 * @file
 * Include various helper functions.
 */

use Drupal\Core\Database\Database;

/**
 * Fixes menu_link_content entities.
 *
 * Replaces 'internal:/alias' link__uri items with 'entity:node/id'
 * in the menu_link_content_data table.
 */
function ymca_fix_menu_link_content() {
  $nodes = \Drupal::entityQuery('node')
    ->condition('type', 'article')
    ->execute();

  $db = Database::getConnection('default');
  $table = 'menu_link_content_data';
  $col = 'link__uri';

  foreach ($nodes as $nid) {
    $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $nid);
    $result = $db->select($table, 'd')
      ->fields('d')
      ->condition('d.' . $col, $db->escapeLike('internal:' . $alias), 'LIKE')
      ->execute();

    while ($row = $result->fetchObject()) {
      $db->update($table)
        ->fields([
          $col => 'entity:node/' . $nid,
        ])
        ->condition('id', $row->id)
        ->execute();
    }
  }
}

/**
 * Run migration.
 *
 * @param string $migration
 *   Migration ID.
 * @param bool|FALSE $update
 *   Run migration in 'update' mode.
 * @param bool|FALSE $force
 *   Run migration without dependencies.
 */
function ymca_run_migration($migration, $update = FALSE, $force = FALSE) {
  $id = $migration;

  /** @var \Drupal\migrate\Entity\Migration $migration */
  $migration = \Drupal\migrate\Entity\Migration::load($id);
  $migrate_message = new \Drupal\migrate\MigrateMessage();
  $event_dispatcher = \Drupal::service('event_dispatcher');

  // Run migration without dependencies.
  if ($force) {
    $migration->set('requirements', []);
  }

  // Run migration in 'update' mode.
  if ($update) {
    $migration->getIdMap()->prepareUpdate();
  }

  $migration->setStatus(\Drupal\migrate\Entity\MigrationInterface::STATUS_IDLE);

  $executable = new \Drupal\migrate\MigrateExecutable($migration, $migrate_message, $event_dispatcher);
  $executable->import();
}

/**
 * Get legacy address of the page by ID.
 *
 * @param int $id
 *   Page ID.
 *
 * @return mixed
 *   Page address.
 */
function ymca_get_legacy_page_address($id) {
  $db = Database::getConnection('default', 'amm_source');
  $field = $db->select('amm_site_page', 'p')
    ->fields('p', ['page_subdirectory'])
    ->condition('p.site_page_id', $id)
    ->execute()
    ->fetchField();
  return $field;
}

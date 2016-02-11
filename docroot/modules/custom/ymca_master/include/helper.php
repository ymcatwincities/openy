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
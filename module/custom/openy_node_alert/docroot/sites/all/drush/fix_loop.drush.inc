<?php

use \Drupal\redirect\Entity\Redirect;

/**
 * @file
 * Removes infinite loops redirects.
 */

/**
 * Implements hook_drush_command().
 */
function fix_loop_drush_command() {
  $items = [];
  $items['fix-loop'] = [
    'description' => 'Fix loop.',
    'options' => [],
    'arguments' => [],
    'core' => ['8+'],
    'aliases' => [],
  ];
  return $items;
}

/**
 * Rebuild a Drupal 8 site in a quick way.
 */
function drush_fix_loop() {
  $invalid = [];
  $db = \Drupal::database();

  $redirects = $db->select('redirect', 'r')
    ->fields('r')
    ->condition('r.redirect_redirect__uri', 'internal:/node/%', 'LIKE')
    ->execute();

  while ($redirect = $redirects->fetchObject()) {
    // Get nid from the redirect destination.
    $uri_parts = explode('/', $redirect->redirect_redirect__uri);
    $nid = array_pop($uri_parts);

    $aliases = $db->select('url_alias', 'a')
      ->fields('a')
      ->condition('a.source', '/node/' . $nid)
      ->execute();

    while ($alias = $aliases->fetchObject()) {
      if (ltrim($alias->alias, '/') == $redirect->redirect_source__path) {
        $invalid[] = $redirect->rid;
      }
    }
  }

  foreach ($invalid as $item) {
    $redirect = Redirect::load($item);
    $redirect->delete();
  }

  drush_print(sprintf('%d invalid redirects have been deleted.', count($invalid)));
}

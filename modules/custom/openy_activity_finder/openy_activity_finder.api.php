<?php

/**
 * @file
 * Hooks specific to the openy_activity_finder module.
 */

use Drupal\node\NodeInterface;

/**
 * Alter the search results.
 */
function hook_activity_finder_program_search_results_alter(&$data) {

}

/**
 * Alter the process results.
 *
 * @param array $data
 *   The array of processed result item for program search.
 * @param \Drupal\node\NodeInterface $entity
 *   The node that has just been processed.
 *
 * @see Drupal\openy_activity_finder\OpenyActivityFinderSolrBackend
 */
function hook_activity_finder_program_process_results_alter(array &$data, NodeInterface $entity) {
  $data['description'] = t('Test session description');
}

/**
 * Alter more info request results.
 */
function hook_activity_finder_program_more_info_alter(&$data) {

}

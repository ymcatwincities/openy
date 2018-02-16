<?php

/**
 * @file
 * OpenY Node Activity batch update class.
 */

namespace Drupal\openy_node_activity;

class ActivityBatchUpdate {

  /**
   * Update batch operation.
   *
   * @param array $activity_ids
   *   Activity nodes to update.
   * @param bool $status
   *   Whether to publish or unpublish. True means publish.
   * @param array $context
   *   Batch context.
   */
  public static function updateActivities($activity_ids, $status, &$context) {
    // Setup.
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($activity_ids);
    }
    $items_per_iteration = 50;

    $batch_ids = array_slice($activity_ids, $context['sandbox']['progress'], $items_per_iteration);
    $activities = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($batch_ids);

    if ($status) {
      // Publish all Activity nodes.
      foreach ($activities as $node) {
        if (!$node->isPublished()) {
          $node->setPublished();
          $node->save();
        }
        $context['sandbox']['progress']++;
        $context['results'][] = $node->id();
      }
    } else {
      // Unpublish all Category nodes.
      foreach ($activities as $node) {
        if ($node->isPublished()) {
          $node->setUnPublished();
          $node->save();
        }
        $context['sandbox']['progress']++;
        $context['results'][] = $node->id();
      }
    }

    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }

  /**
   * Finish operation at batch completion.
   *
   * @param $success
   * @param $results
   * @param $operations
   */
  public static function finished($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One activity updated.', '@count activities updated.'
      );
    } else {
      $message = t('There was an error updating activities.');
    }
    drupal_set_message($message);
  }
}

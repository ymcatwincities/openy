<?php

/**
 * @file
 * OpenY Node batch update class.
 */

namespace Drupal\openy_node;

class BatchNodeUpdate {

  /**
   * Update batch operation.
   *
   * @param array $ids
   *   Ids of nodes to update.
   * @param bool $status
   *   Whether to publish or unpublish. True means publish.
   * @param string $type
   *   The name of the type to be updated. Used for messages.
   * @param array $context
   *   Batch context.
   */
  public static function update($ids, $status, $type, &$context) {
    // Setup.
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($ids);
      $context['results']['status'] = $status;
      $context['results']['type'] = $type;
    }
    $items_per_iteration = 50;

    $batch_ids = array_slice($ids, $context['sandbox']['progress'], $items_per_iteration);
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($batch_ids);

    foreach ($nodes as $node) {
      if ($status && !$node->isPublished()) {
        // Publish all Activity nodes.
        $node->setPublished();
        $node->save();
      } elseif (!$status && $node->isPublished()) {
        // Unpublish all Category nodes.
        $node->setUnPublished();
        $node->save();
      }
      $context['sandbox']['progress']++;
      $context['results']['ids'][] = $node->id();
    }

    if (!empty($nodes) || $context['sandbox']['max'] !== 0) {
      $first_node = reset($nodes);
      $context['message'] = t('Now processing %node', ['%node' => $first_node->getTitle()]);
      // When result is 1, the batch will finish.
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
    else {
      $context['finished'] = 1;
    }
  }

  /**
   * Finish operation at batch completion.
   *
   * @param bool $success
   *   A boolean indicating whether the batch has completed successfully.
   * @param array $results
   *   An array of result data set in $context['results'].
   * @param $operations
   *   If $success is FALSE, contains the operations that remained unprocessed.
   */
  public static function finished($success, $results, $operations) {
    $messenger = \Drupal::messenger();

    if ($success) {
      if ($results['status']) {
        $status = 'published';
      } else {
        $status = 'unpublished';
      }
      $message = \Drupal::translation()->formatPlural(
        count($results['ids']),
        'One @type node @status.',
        '@count @type nodes @status.',
        [
          '@type' => $results['type'],
          '@status' => $status,
        ]
      );
    } else {
      $message = t('There was an error updating @type.', ['@type' => $results['type']]);
    }
    $messenger->addMessage($message);
  }
}

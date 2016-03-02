<?php

/**
 * @file
 * API documentation for the Scheduler module.
 */

/**
 * Allows to prevent publication of a scheduled node.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The scheduled node that is about to be published.
 *
 * @return bool
 *   FALSE if the node should not be published. TRUE otherwise.
 */
function hook_scheduler_allow_publishing(NodeInterface $node) {
  // Prevent publication of nodes that do not have the 'Approved for publication
  // by the CEO' checkbox ticked.
  $allowed = !empty($node->field_approved->value);

  // If publication is denied then inform the user why.
  if (!$allowed) {
    drupal_set_message(t('The content will only be published after approval by the CEO.'), 'status', FALSE);
  }

  return $allowed;
}

/**
 * Allows to prevent unpublication of a scheduled node.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The scheduled node that is about to be unpublished.
 *
 * @return bool
 *   FALSE if the node should not be unpublished. TRUE otherwise.
 */
function hook_scheduler_allow_unpublishing(NodeInterface $node) {
  $allowed = TRUE;

  // Prevent unpublication of competition entries if not all prizes have been
  // claimed.
  if ($node->getType() == 'competition' && $items = $node->field_competition_prizes->getValue()) {
    $allowed = (bool) count($items);

    // If unpublication is denied then inform the user why.
    if (!$allowed) {
      drupal_set_message(t('The competition will only be unpublished after all prizes have been claimed by the winners.'));
    }
  }

  return $allowed;
}

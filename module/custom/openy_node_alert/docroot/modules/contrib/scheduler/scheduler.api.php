<?php

/**
 * @file
 * API documentation for the Scheduler module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Hook function to add node ids to the list being processed.
 *
 * This hook allows modules to add more node ids into the list being processed
 * in the current cron run. It is invoked during cron runs only. This function
 * is retained for backwards compatibility but is superceded by the more
 * flexible hook_scheduler_nid_list_alter().
 *
 * @param string $action
 *   The action being done to the node - 'publish' or 'unpublish'.
 *
 * @return array
 *   Array of node ids to add to the existing list of nodes to be processed.
 */
function hook_scheduler_nid_list($action) {
  $nids = [];
  // Do some processing to add new node ids into $nids.
  return $nids;
}

/**
 * Hook function to manipulate the list of nodes being processed.
 *
 * This hook allows modules to add or remove node ids from the list being
 * processed in the current cron run. It is invoked during cron runs only. It
 * can do everything that hook_scheduler_nid_list() does, plus more.
 *
 * @param array $nids
 *   An array of node ids being processed.
 * @param string $action
 *   The action being done to the node - 'publish' or 'unpublish'.
 *
 * @return array
 *   The full array of node ids to process, adjusted as required.
 */
function hook_scheduler_nid_list_alter(array &$nids, $action) {
  // Do some processing to add or remove node ids.
  return $nids;
}

/**
 * Hook function to deny or allow a node to be published.
 *
 * This hook gives modules the ability to prevent publication of a node at the
 * scheduled time. The node may be scheduled, and an attempt to publish it will
 * be made during the first cron run after the publishing time. If this hook
 * returns FALSE the node will not be published. Attempts at publishing will
 * continue on each subsequent cron run until this hook returns TRUE.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The scheduled node that is about to be published.
 *
 * @return bool
 *   TRUE if the node can be published, FALSE if it should not be published.
 */
function hook_scheduler_allow_publishing(NodeInterface $node) {
  // If there is no 'approved' field do nothing to change the result.
  if (!isset($node->field_approved)) {
    $allowed = TRUE;
  }
  else {
    // Prevent publication of nodes that do not have the 'Approved for
    // publication by the CEO' checkbox ticked.
    $allowed = !empty($node->field_approved->value);

    // If publication is denied then inform the user why. This message will be
    // displayed during node edit and save.
    if (!$allowed) {
      drupal_set_message(t('The content will only be published after approval by the CEO.'), 'status', FALSE);
    }
  }

  return $allowed;
}

/**
 * Hook function to deny or allow a node to be unpublished.
 *
 * This hook gives modules the ability to prevent unpblication of a node at the
 * scheduled time. The node may be scheduled, and an attempt to unpublish it
 * will be made during the first cron run after the unpublishing time. If this
 * hook returns FALSE the node will not be unpublished. Attempts at unpublishing
 * will continue on each subsequent cron run until this hook returns TRUE.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The scheduled node that is about to be unpublished.
 *
 * @return bool
 *   TRUE if the node can be unpublished, FALSE if it should not be unpublished.
 */
function hook_scheduler_allow_unpublishing(NodeInterface $node) {
  $allowed = TRUE;

  // Prevent unpublication of competition entries if not all prizes have been
  // claimed.
  if ($node->getType() == 'competition' && $items = $node->field_competition_prizes->getValue()) {
    $allowed = (bool) count($items);

    // If unpublication is denied then inform the user why. This message will be
    // displayed during node edit and save.
    if (!$allowed) {
      drupal_set_message(t('The competition will only be unpublished after all prizes have been claimed by the winners.'));
    }
  }

  return $allowed;
}

/**
 * @} End of "addtogroup hooks".
 */

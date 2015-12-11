<?php

/**
 * @file
 * Scheduler cron functions.
 *
 * This file is included only when running a crontab job or executing the
 * lightweight cron via the admin interface.
 */

use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Publish scheduled nodes.
 *
 * @return bool
 *   TRUE if any node has been published, FALSE otherwise.
 */
function _scheduler_publish() {
  $result = FALSE;

  // If the time now is greater than the time to publish a node, publish it.
  $query = \Drupal::entityQuery('node')
    ->condition('publish_on', 0, '>')
    ->condition('publish_on', REQUEST_TIME, '<=');
  $nids = $query->execute();

  $action = 'publish';

  // Allow other modules to add to the list of nodes to be published.
  $nids = array_unique(array_merge($nids, _scheduler_scheduler_nid_list($action)));

  // Allow other modules to alter the list of nodes to be published.
  \Drupal::moduleHandler()->alter('scheduler_nid_list', $nids, $action);

  $nodes = Node::loadMultiple($nids);
  foreach ($nodes as $nid => $node) {
    // Check that other modules allow the action on this node.
    if (!_scheduler_allow($node, $action)) {
      continue;
    }

    // Invoke Scheduler API for modules to react before the node is published.
    // @todo For D8 move the 'pre' call to here.
    // See https://www.drupal.org/node/2311273

    // Update timestamps.
    $publish_on = $node->publish_on->value;
    $node->set('changed', $publish_on);
    $old_creation_date = $node->getCreatedTime();
    if ($node->type->entity->getThirdPartySetting('scheduler', 'publish_touch', FALSE)) {
      $node->setCreatedTime($publish_on);
    }

    $create_publishing_revision = $node->type->entity->getThirdPartySetting('scheduler', 'publish_revision', FALSE);
    if ($create_publishing_revision) {
      $node->setNewRevision();
      // Use a core date format to guarantee a time is included.
      $node->revision_log = t('Node published by Scheduler on @now. Previous creation date was @date.', array(
        '@now' => \Drupal::service('date.formatter')->format(REQUEST_TIME, 'short'),
        '@date' => \Drupal::service('date.formatter')->format($old_creation_date, 'short'),
      ));
    }
    // Unset publish_on so the node will not get rescheduled by subsequent calls
    // to $node->save(). Save the value for use when calling Rules.
    $node->publish_on->value = NULL;

    // Invoke scheduler API to allow modules to alter the node before it is
    // saved.
    // @todo For D8, remove this from here.
    _scheduler_scheduler_api($node, 'pre_' . $action);

    // Log the fact that a scheduled publication is about to take place.
    $view_link = $node->link(t('View'));
    \Drupal::logger('scheduler')->notice('@type: scheduled publishing of %title.', array('@type' => $node->getType(), '%title' => $node->getTitle(), 'link' => $view_link));

    // Use the actions system to publish the node.
    \Drupal::entityManager()->getStorage('action')->load('node_publish_action')->getPlugin()->execute($node);

    // Invoke the event to tell Rules that Scheduler has published this node.
    if (\Drupal::moduleHandler()->moduleExists('rules')) {
      rules_invoke_event('scheduler_node_has_been_published_event', $node, $publish_on, $node->unpublish_on->value);
    }

    // Invoke scheduler API for modules to react after the node is published.
    _scheduler_scheduler_api($node, $action);

    $result = TRUE;
  }

  return $result;
}

/**
 * Unpublish scheduled nodes.
 *
 * @return bool
 *   TRUE is any node has been unpublished, FALSE otherwise.
 */
function _scheduler_unpublish() {
  $result = FALSE;

  // If the time is greater than the time to unpublish a node, unpublish it.
  $query = \Drupal::entityQuery('node')
    ->condition('unpublish_on', 0, '>')
    ->condition('unpublish_on', REQUEST_TIME, '<=');
  $nids = $query->execute();

  $action = 'unpublish';

  // Allow other modules to add to the list of nodes to be unpublished.
  $nids = array_unique(array_merge($nids, _scheduler_scheduler_nid_list($action)));

  // Allow other modules to alter the list of nodes to be unpublished.
  \Drupal::moduleHandler()->alter('scheduler_nid_list', $nids, $action);

  $nodes = Node::loadMultiple($nids);
  foreach ($nodes as $nid => $node) {
    // Check that other modules allow the action on this node.
    if (!_scheduler_allow($node, $action)) {
      continue;
    }

    // Do not process the node if it has a publish_on time which is in the past,
    // as this implies that scheduled publishing has been blocked by one of the
    // API functions we provide. Hence unpublishing should be halted too.
    $publish_on = $node->publish_on->value;
    if (!empty($publish_on) && $publish_on <= REQUEST_TIME) {
      continue;
    }

    // Invoke scheduler API for modules to react before the node is unpublished.
    // @todo For D8, move the 'pre' call to here.
    // See https://www.drupal.org/node/2311273

    // Update timestamps.
    $old_change_date = $node->getChangedTime();
    $unpublish_on = $node->unpublish_on->value;
    $node->set('changed', $unpublish_on);

    $create_unpublishing_revision = $node->type->entity->getThirdPartySetting('scheduler', 'unpublish_revision', FALSE);
    if ($create_unpublishing_revision) {
      $node->setNewRevision();
      // Use a core date format to guarantee a time is included.
      $node->revision_log = t('Node unpublished by Scheduler on @now. Previous change date was @date.', array(
        '@now' => \Drupal::service('date.formatter')->format(REQUEST_TIME, 'short'),
        '@date' => \Drupal::service('date.formatter')->format($old_change_date, 'short'),
      ));
    }
    // Unset unpublish_on so the node will not get rescheduled by subsequent
    // calls to $node->save(). Save the value for use when calling Rules.
    $node->unpublish_on->value = NULL;

    // Invoke scheduler API to allow modules to alter the node before it is
    // saved.
    // @todo For D8, remove this from here.
    _scheduler_scheduler_api($node, 'pre_' . $action);

    // Log the fact that a scheduled unpublication is about to take place.
    $view_link = $node->link(t('View'));
    \Drupal::logger('scheduler')->notice('@type: scheduled unpublishing of %title.', array('@type' => $node->getType(), '%title' => $node->getTitle(), 'link' => $view_link));

    // Use the actions system to publish the node.
    \Drupal::entityManager()->getStorage('action')->load('node_unpublish_action')->getPlugin()->execute($node);

    // Invoke event to tell Rules that Scheduler has unpublished this node.
    if (\Drupal::moduleHandler()->moduleExists('rules')) {
      rules_invoke_event('scheduler_node_has_been_unpublished_event', $node, $node->publish_on, $unpublish_on);
    }

    // Invoke scheduler API for modules to react after the node is unpublished.
    _scheduler_scheduler_api($node, 'unpublish');

    $result = TRUE;
  }

  return $result;
}

/**
 * Gather node IDs for all nodes that need to be $action'ed.
 *
 * @param string $action
 *   The action being performed, either "publish" or "unpublish".
 *
 * @return array
 *   An array of node ids.
 */
function _scheduler_scheduler_nid_list($action) {
  $nids = array();

  foreach (\Drupal::moduleHandler()->getImplementations('scheduler_nid_list') as $module) {
    $function = $module . '_scheduler_nid_list';
    $nids = array_merge($nids, $function($action));
  }

  return $nids;
}

/**
 * Run the lightweight cron.
 *
 * The Scheduler part of the processing performed here is the same as in the
 * normal Drupal cron run. The difference is that only scheduler_cron() is
 * executed, no other modules hook_cron() functions are called.
 *
 * This function is called from the external crontab job via url /scheduler/cron
 * or it can be run interactively from the Scheduler configuration page at
 * /admin/config/content/scheduler/cron.
 */
function _scheduler_run_cron() {
  $log = \Drupal::config('scheduler.settings')->get('log');
  if ($log) {
    \Drupal::logger('scheduler')->notice('Lightweight cron run activated.');
  }
  scheduler_cron();
  if (ob_get_level() > 0) {
    $handlers = ob_list_handlers();
    if (isset($handlers[0]) && $handlers[0] == 'default output handler') {
      ob_clean();
    }
  }
  if ($log) {
    \Drupal::logger('scheduler')->notice('Lightweight cron run completed.', array('link' => \Drupal::l(t('settings'), Url::fromRoute('scheduler.cron_form'))));
  }
}

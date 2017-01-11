<?php

/**
 * @file
 * Administrative Drush commands for Purge - Command File.
 */

use Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface;
use Drupal\purge\Plugin\Purge\Queue\StatsTrackerInterface;

/**
 * Clear the queue and reset all statistics.
 *
 * @param string $format
 *   The default or selected output format of the command.
 * @param \Drupal\purge\Plugin\Purge\Queue\StatsTrackerInterface $purgeQueueStats
 *   The purge queue statistics service.
 * @param \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface $purgeQueue
 *   The purge queue service.
 *
 * @return array
 *   Drush render/format array.
 */
function purge_drush_p_queue_empty($format, StatsTrackerInterface $purgeQueueStats, QueueServiceInterface $purgeQueue) {
  $total = (int) $purgeQueueStats->total()->get();
  $purgeQueue->emptyQueue();
  if (in_array($format, ['yaml', 'json'])) {
    return $total;
  }
  elseif ($format == 'string') {
    if ($total !== 0) {
      return dt('Cleared @total items from the queue.', ['@total' => $total]);
    }
    else {
      return dt('The queue was empty, nothing to clear!');
    }
  }
}

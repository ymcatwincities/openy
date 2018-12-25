<?php

/**
 * @file
 * Administrative Drush commands for Purge - Command File.
 */

use Drupal\purge\Plugin\Purge\Queue\StatsTrackerInterface;

/**
 * Retrieve the queue statistics.
 *
 * @param string $format
 *   The default or selected output format of the command.
 * @param \Drupal\purge\Plugin\Purge\Queue\StatsTrackerInterface $purgeQueueStats
 *   The purge queue statistics service.
 *
 * @return array
 *   Drush render/format array.
 */
function purge_drush_p_queue_stats($format, StatsTrackerInterface $purgeQueueStats) {
  if ($format === 'table') {
    return [
      [
        'title' => dt('Claimed'),
        'value' => $purgeQueueStats->claimed()->getInteger(),
        'description' => dt('The number of currently claimed invalidation objects from the queue, which is likely to be (close to) zero.'),
      ],
      [
        'title' => dt('Deleted'),
        'description' => dt('The number of items deleted from the queue and thus successfully invalidated.'),
        'value' => $purgeQueueStats->deleted()->getInteger(),
      ],
      [
        'title' => dt('Total'),
        'description' => dt('The total number of items currently in the queue.'),
        'value' => $purgeQueueStats->total()->getInteger(),
      ],
    ];
  }
  else {
    return [
      'claimed' => $purgeQueueStats->claimed()->getInteger(),
      'deleted' => $purgeQueueStats->deleted()->getInteger(),
      'total' => $purgeQueueStats->total()->getInteger(),
    ];
  }
}

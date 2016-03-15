<?php

/**
 * @file
 * Administrative Drush commands for Purge - Command File.
 */

use Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface;
use Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface;
use Drupal\purge\Plugin\Purge\Purger\Exception\CapacityException;
use Drupal\purge\Plugin\Purge\Purger\Exception\DiagnosticsException;
use Drupal\purge\Plugin\Purge\Purger\Exception\LockException;
use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;

/**
 * Claim a chunk of items from the queue and process them.
 *
 * @param string $format
 *   The default or selected output format of the command.
 * @param \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface $purgeQueue
 *   The purge queue service.
 * @param \Drupal\purge\Plugin\Purge\Processor\ProcessorsServiceInterface $purgeProcessors
 *   The purge processors service.
 * @param \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface $purgePurgers
 *   The purge purgers service.
 *
 * @return array
 *   Drush render/format array.
 */
function purge_drush_p_queue_work($format, QueueServiceInterface $purgeQueue, ProcessorsServiceInterface $purgeProcessors, PurgersServiceInterface $purgePurgers) {
  $return_exception = function($exception, $message = '') use($format) {
    if ($format === 'string') {
      return drush_set_error($message ? $message : $exception->getMessage());
    }
    return FALSE;
  };

  // Retrieve our queuer object and fail when it is not returned.
  if (!($processor = $purgeProcessors->get('drush_purge_queue_work'))) {
    if (in_array($format, ['yaml', 'json'])) {
      return FALSE;
    }
    return drush_set_error('NOT_AUTHORIZED', dt('Not authorized, processor missing!'));
  }

  // Claim items from the queue.
  if (!($claims = $purgeQueue->claim())) {
    if (in_array($format, ['yaml', 'json'])) {
      return TRUE;
    }
    return dt('There were no items to be claimed from the queue.');
  }
  // Attempt the cache invalidation and deal with errors.
  try {
    $purgePurgers->invalidate($processor, $claims);
  }
  catch (DiagnosticsException $e) {
    return $return_exception($e);
  }
  catch (CapacityException $e) {
    return $return_exception($e);
  }
  catch (LockException $e) {
    return $return_exception($e);
  }
  finally {
    $purgeQueue->handleResults($claims);
  }

  // Return messaging.
  if (in_array($format, ['yaml', 'json'])) {
    return TRUE;
  }
  return dt('Processed @number objects...', ['@number' => count($claims)]);
}

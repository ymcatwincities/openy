<?php

namespace Drupal\queue_ui;

/**
 * Class QueueUIBatch
 *
 * Batch controller to process a queue from the UI.
 */
class QueueUIBatch {

  /**
   * Batch step definition to process one queue item.
   *
   * Based on \Drupal\Core\Cron::processQueues().
   */
  public static function step($queue_name,  $context) {

    if (isset($context['interrupted']) && $context['interrupted']) {
      return;
    }

    /** @var $queueManager \Drupal\Core\Queue\QueueWorkerManagerInterface */
    $queueManager = \Drupal::service('plugin.manager.queue_worker');
    $queueFactory = \Drupal::service('queue');

    $info = $queueManager->getDefinition($queue_name);
    $title = $info['title'];

    // Make sure every queue exists. There is no harm in trying to recreate
    // an existing queue.
    $queueFactory->get($queue_name)->createQueue();

    $queue_worker = $queueManager->createInstance($queue_name);
    $queue = $queueFactory->get($queue_name);

    if ($item = $queue->claimItem()) {
      try {
        $queue_worker->processItem($item->data);

        // Let other modules alter the title of the item being processed
        \Drupal::moduleHandler()->alter('queue_ui_batch_title', $title, $item->data);
        $context['message'] = $title;

        $queue->deleteItem($item);
      }
      catch (SuspendQueueException $e) {
        // If the worker indicates there is a problem with the whole queue,
        // release the item and skip to the next queue.
        $queue->releaseItem($item);

        watchdog_exception('cron', $e);

        // Skip to the next queue.
        $context['interrupted'] = TRUE;
      }
      catch (\Exception $e) {
        // In case of any other kind of exception, log it and leave the item
        // in the queue to be processed again later.
        watchdog_exception('cron', $e);
      }
    }
  }
}

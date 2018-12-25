<?php

namespace Drupal\purge\Plugin\Purge\Queue;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\ReliableQueueInterface;

/**
 * Describes a ReliableQueueInterface compliant queue that can hold queue items.
 */
interface QueueInterface extends ReliableQueueInterface, ContainerFactoryPluginInterface {

  /**
   * Add multiple items to the queue and store them efficiently.
   *
   * @param array $items
   *   Non-associative array containing arrays with arbitrary data to be
   *   associated with the new tasks in the queue.
   *
   * @return array|false
   *   Non-associative array containing unique ID's for the items that were
   *   saved successfully, otherwise FALSE. We don't guarantee the item was
   *   committed to disk etc, but as far as we know, the item is now in the
   *   queue.
   */
  public function createItemMultiple(array $items);

  /**
   * Claims multiple items from the queue for processing.
   *
   * @param int $claims
   *   Determines how many claims at once should be claimed from the queue. When
   *   the queue is unable to return as many items as requested it will return
   *   as much items as it can.
   * @param int $lease_time
   *   How long the processing is expected to take in seconds, defaults to an
   *   hour. After this lease expires, the item will be reset and another
   *   consumer can claim the item. For idempotent tasks (which can be run
   *   multiple times without side effects), shorter lease times would result
   *   in lower latency in case a consumer fails. For tasks that should not be
   *   run more than once (non-idempotent), a larger lease time will make it
   *   more rare for a given task to run multiple times in cases of failure,
   *   at the cost of higher latency.
   *
   * @return array[]
   *   On success we return a non-associative array with item objects. When the
   *   queue has no items that can be claimed, this doesn't return FALSE as
   *   claimItem() does, but an empty array instead.
   *
   *   If claims return, the objects have at least these properties:
   *   - data: the same as what what passed into createItem().
   *   - item_id: the unique ID returned from createItem().
   *   - created: timestamp when the item was put into the queue.
   */
  public function claimItemMultiple($claims = 10, $lease_time = 3600);

  /**
   * Delete multiple items from the queue at once.
   *
   * @param array $items
   *   Non-associative array with item objects as returned by
   *   claimItemMultiple() or \Drupal\Core\Queue\QueueInterface::claimItem().
   * @return void
   */
  public function deleteItemMultiple(array $items);

  /**
   * Release multiple items that the worker could not process.
   *
   * Another worker can come in and process these before the timeout expires.
   *
   * @param array $items
   *   Non-associative array with item objects as returned by
   *   claimItemMultiple() or \Drupal\Core\Queue\QueueInterface::claimItem().
   *
   * @return array
   *   Empty array upon full success, else the remaining items that failed.
   */
  public function releaseItemMultiple(array $items);

  /**
   * Select a page of queue data with a limited number of items.
   *
   * This method facilitates end-user inspection of the queue by letting it
   * select a set of data records, without the ability to further interact with
   * the returned data. The items returned aren't claimed and no action is taken
   * on them.
   *
   * @param int $page
   *   Pages always start at 1 and the highest available page is returned by
   *   ::selectPageMax(), which bases its information on the set limit that
   *   in turn gets returned by selectPageLimit(). When page numbers are given
   *   without any data in it, the resulting return value will be empty.
   *
   * @see \Drupal\purge\Plugin\Purge\Queue\QueueInterface::selectPageLimit
   * @see \Drupal\purge\Plugin\Purge\Queue\QueueInterface::selectPageMax
   *
   * @return array[]
   *   Returns a non-associative array with item objects or the array is simply
   *   empty. The objects contain at least the following properties:
   *   - data: the same as what what passed into createItem().
   *   - item_id: the unique ID returned from createItem().
   *   - created: timestamp when the item was put into the queue.
   */
  public function selectPage($page = 1);

  /**
   * Retrieve or configure the number of items per data page.
   *
   * @param int $set_limit_to
   *   When this argument is not NULL, it will change the known limit to the
   *   integer given. From this call and on, the limit returned has changed.
   *
   * @return int
   *   The maximum number of items returned on a selected data page.
   */
  public function selectPageLimit($set_limit_to = NULL);

  /**
   * Retrieve the highest page number containing data in the queue.
   *
   * This method relies on ::selectPageLimit() for finding out how many items
   * are shown on a single page. The resulting division is rounded up so that
   * the last page will usually have less items then the limit.
   *
   * @see \Drupal\purge\Plugin\Purge\Queue\QueueInterface::selectPageLimit
   *
   * @return int
   *   The highest page number number with data on it.
   */
  public function selectPageMax();

}

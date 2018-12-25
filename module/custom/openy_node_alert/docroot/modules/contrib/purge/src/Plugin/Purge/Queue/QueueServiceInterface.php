<?php

namespace Drupal\purge\Plugin\Purge\Queue;

use Drupal\purge\ServiceInterface;
use Drupal\purge\ModifiableServiceInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Queuer\QueuerInterface;

/**
 * Describes a service that lets invalidations interact with a queue backend.
 */
interface QueueServiceInterface extends ServiceInterface, ModifiableServiceInterface {

  /**
   * Add invalidation objects to the queue, schedule for later purging.
   *
   * @param \Drupal\purge\Plugin\Purge\Queuer\QueuerInterface $queuer
   *   The queuer plugin that is queueing the invalidation objects.
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface[] $invalidations
   *   A non-associative array with invalidation objects to be added to the
   *   queue. After the items have been added to the queue, they can be claimed
   *   to be processed by a queue processor.
   *
   * @return void
   */
  public function add(QueuerInterface $queuer, array $invalidations);

  /**
   * Claim invalidation objects from the queue.
   *
   * @param int $claims
   *   Determines how many claims should be taken from the queue. When the queue
   *   has less items available, less will be returned. When this parameter is
   *   left as NULL, CapacityTrackerInterface::getRemainingInvalidationsLimit()
   *   will be used as input.
   * @param int $lease_time
   *   The expected (maximum) time needed per claim, which will get multiplied
   *   for you by the number of claims you request. When this is left NULL, this
   *   value comes from CapacityTrackerInterface::getTimeHint().
   *
   *   After the lease_time expires, another running request or CLI process can
   *   also claim the items and process them, therefore too short lease times
   *   are dangerous as it could lead to double processing.
   *
   * @return \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface[]|array
   *   Returned will be a non-associative array with the given amount of
   *   invalidation objects as claimed. Be aware that it can be expected that
   *   the claimed invalidations will need to be processed by the purger within
   *   the given $lease_time, else they will become available again. The
   *   returned array is empty when the queue is.
   */
  public function claim($claims = NULL, $lease_time = NULL);

  /**
   * Delete invalidation objects from the queue.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface[] $invalidations
   *   A non-associative array with invalidation objects to be deleted from the
   *   queue. The object instances and references thereto, remain to exist until
   *   the queue service is destructed, but should not be accessed anymore as
   *   they will be deleted anyway.
   *
   * @return void
   */
  public function delete(array $invalidations);

  /**
   * Empty the entire queue and reset all statistics.
   *
   * @return void
   */
  public function emptyQueue();

  /**
   * Retrieve the description of the queue backend.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getDescription();

  /**
   * Retrieve the label of the queue backend.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getLabel();

  /**
   * Handle processing results and either release back, or delete objects.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface[] $invalidations
   *   The invalidation objects after processing.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgersService::invalidate
   *
   * @return void
   */
  public function handleResults(array $invalidations);

  /**
   * Retrieves the number of items in the queue.
   *
   * @return int
   *   The number of items in the queue.
   */
  public function numberOfItems();

  /**
   * Release invalidation objects back to the queue.
   *
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface[] $invalidations
   *   A non-associative array with invalidation objects to be released back to
   *   the queue, usually FAILED, PROCESSING or NOT_SUPPORTED. Once released,
   *   other processors can claim them again for further processing.
   *
   * @return void
   */
  public function release(array $invalidations);

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
   * @see \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface::selectPageLimit
   * @see \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface::selectPageMax
   *
   * @return \Drupal\purge\Plugin\Purge\Invalidation\ImmutableInvalidationInterface[]
   *   Immutable invalidation objects, which aren't usable besides data display.
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
   * @see \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface::selectPageLimit
   *
   * @return int
   *   The highest page number number with data on it.
   */
  public function selectPageMax();

}

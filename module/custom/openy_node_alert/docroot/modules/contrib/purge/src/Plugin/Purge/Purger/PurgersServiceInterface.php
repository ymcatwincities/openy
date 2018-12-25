<?php

namespace Drupal\purge\Plugin\Purge\Purger;

use Drupal\purge\ServiceInterface;
use Drupal\purge\ModifiableServiceInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge\Plugin\Purge\Processor\ProcessorInterface;

/**
 * Describes a service that distributes access to one or more purgers.
 */
interface PurgersServiceInterface extends ServiceInterface, ModifiableServiceInterface {

  /**
   * Get the capacity tracker.
   *
   * \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface implementations
   * always hold a single capacity tracker instance. The capacity tracker tracks
   * runtime resource consumption and predicts capacity information.
   *
   * @return \Drupal\purge\Plugin\Purge\Purger\CapacityTrackerInterface;
   */
  public function capacityTracker();

  /**
   * Create a unique instance ID for new purger instances.
   *
   * Every purger has a unique instance identifier set by the purgers service,
   * whether it is multi-instantiable or not. This helper creates a unique,
   * random string, 10 characters long.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::getId()
   *
   * @return string
   */
  public function createId();

  /**
   * Retrieve all user-readable labels for all enabled purger instances.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::getId()
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::getLabel()
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   Associative array with instance ID's in the key and the label as value.
   */
  public function getLabels();

  /**
   * Retrieve the list of supported invalidation types.
   *
   * @return string[]
   *   List of supported invalidation type plugins.
   */
  public function getTypes();

  /**
   * Retrieve the list of supported invalidation types per purger instance.
   *
   * @see \Drupal\purge\Annotation\PurgePurger::$types.
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::getId().
   *
   * @return string[]
   *   Array with the purger instance ID as key, and list of invalidation types.
   */
  public function getTypesByPurger();

  /**
   * Invalidate content from external caches.
   *
   * Implementations of this method have the responsibility of invalidating the
   * given list of invalidation objects from their external caches. Besides the
   * invalidation itself, it also needs to call ::setState() on each object to
   * reflect the correct state after invalidation.
   *
   * You can set it to the following states:
   *
   * - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::SUCCEEDED
   * - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::FAILED
   * - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::PROCESSING
   * - \Drupal\purge\Plugin\Purge\Invalidation\InvStatesInterface::NOT_SUPPORTED
   *
   * PROCESSING is a special state only intended to be used on caching platforms
   * where more time is required than 1-2 seconds to clear its cache. Usually
   * CDNs with special status API calls where you can later find out if the
   * object succeeded invalidation. When set to this state, the object flows
   * back to the queue to be offered to your plugin again later.
   *
   * NOT_SUPPORTED will be rarely needed, as invalidation types not listed as
   * supported by your plugin will already be put to this state before it is
   * offered to your plugin by PurgersServiceInterface::invalidate(). However,
   * if there is any technical reason why you couldn't support a particular
   * invalidation at that given time, you can set it as such and it will be
   * offered again later.
   *
   * @param \Drupal\purge\Plugin\Purge\Processor\ProcessorInterface $processor
   *   The processor plugin that is initiating cache invalidation.
   * @param \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface[] $invalidations
   *   Non-associative array of invalidation objects that each describe what
   *   needs to be invalidated by the external caching system. Usually these
   *   objects originate from the queue but direct invalidation is also
   *   possible, in either cases the behavior of your plugin stays the same.
   *
   *   The number of objects given is dictated by the outer limit of Purge's
   *   capacity tracking mechanism and is dynamically calculated. The lower your
   *   ::getTimeHint() implementation returns, the more that will be offered at
   *   once. However, your real execution time can and should never exceed the
   *   defined hint, to protect system stability.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   *   Thrown when $invalidations contains other data than derivatives of
   *   \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\DiagnosticsException
   *   Thrown when ::isSystemOnFire() of the diagnostics service reported a
   *   SEVERITY_ERROR level issue, this forces all purging to be halted.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\CapacityException
   *   Thrown when the capacity tracker's global resource limit returns zero or
   *   when more $invalidations are given exceeding this limit. Any claimed
   *   objects should be released back to the queue (or will expire naturally)
   *   and your code should depend on the next processing window.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\LockException
   *   Thrown when other processors are currently busy.
   *
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setState()
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerCapacityDataInterface::getTimeHint()
   *
   * @return void
   */
  public function invalidate(ProcessorInterface $processor, array $invalidations);

  /**
   * Move the purger instance down in the plugin execution order.
   *
   * @param string $purger_instance_id
   *   The instance ID of the purger that should move one place down.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   *   Thrown when $purger_instance_id is not enabled or does not exist.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface::setPluginsEnabled()
   *
   * @return void
   */
  public function movePurgerDown($purger_instance_id);

  /**
   * Move the purger instance up in the plugin execution order.
   *
   * @param string $purger_instance_id
   *   The instance ID of the purger that should move one place up.
   *
   * @throws \Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException
   *   Thrown when $purger_instance_id is not enabled or does not exist.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface::setPluginsEnabled()
   *
   * @return void
   */
  public function movePurgerUp($purger_instance_id);

}

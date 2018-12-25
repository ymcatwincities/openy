<?php

namespace Drupal\purge\Plugin\Purge\Purger;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\purge\Logger\PurgeLoggerAwareInterface;
use Drupal\purge\Plugin\Purge\Purger\PurgerCapacityDataInterface;

/**
 * Describes a purger - the cache invalidation executor.
 */
interface PurgerInterface extends ContainerFactoryPluginInterface, PurgerCapacityDataInterface, PurgeLoggerAwareInterface {

  /**
   * The current instance of this purger plugin is about to be deleted.
   *
   * When end-users decide to uninstall this purger through the user interface,
   * this method gets called. Especially when this purger is multi-instantiable
   * this gets useful as it allows to remove configuration and perform cleanup
   * prior to when the instance gets uninstalled.
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface::setPluginsEnabled()
   *
   * @return void
   */
  public function delete();

  /**
   * Retrieve the unique instance ID for this purger instance.
   *
   * Every purger has a unique instance identifier set by the purgers service,
   * whether it is multi-instantiable or not. Plugins with 'multi_instance' set
   * to TRUE in their annotations, are likely to require the use of this method
   * to differentiate their purger instance (e.g. through configuration).
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface::createId()
   *
   * @return string
   *   The unique identifier for this purger instance.
   */
  public function getId();

  /**
   * Retrieve the user-readable label for this purger instance.
   *
   * @see \Drupal\purge\Annotation\PurgePurger::$label
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getLabel();

  /**
   * Retrieve the list of supported invalidation types.
   *
   * @see \Drupal\purge\Annotation\PurgePurger::$types.
   *
   * @return string[]
   *   List of supported invalidation type plugins.
   */
  public function getTypes();

  /**
   * Invalidate content from external caches.
   *
   * Implementations of this method have the responsibility of invalidating the
   * given list of invalidation objects from their external caches. Besides the
   * invalidation itself, it also needs to call ::setState() on each object to
   * reflect the correct state after invalidation.
   *
   * You can set it to the following states:
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
   * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface::setState()
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerCapacityDataInterface::getTimeHint()
   *
   * @return void
   */
  public function invalidate(array $invalidations);

  /**
   * Route certain type of invalidations to other methods.
   *
   * Simple purgers supporting just one type - for example 'tag' - will get that
   * specific type offered in ::invalidate(). However, when supporting multiple
   * types it might be useful to have PurgersService sort and route these for
   * you to the methods you specify. The expected signature and method behavior
   * is equal to \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::invalidate.
   *
   * One note of warning: depending on the implementation specifics of a plugin,
   * sorting and dispatching types to different code paths can be less efficient
   * compared to external platforms allowing you to mix and send everyhing in
   * one single batch. Therefore, consult the API of the platform your plugin
   * supports to decide what the most efficient implementation will be.
   *
   * A simple implementation will look like this:
   * @code
   *   public function routeTypeToMethod($type) {
   *     $methods = [
   *       'path' => 'invalidatePaths',
   *       'tag'  => 'invalidateTags',
   *       'url'  => 'invalidateUrls',
   *     ];
   *     return isset($methods[$type]) ? $methods[$type] : 'invalidate';
   *   }
   * @endcode
   *
   * @param string $type
   *   The type of invalidation(s) about to be offered to the purger.
   *
   * @return string
   *   The PHP method name called on the purger with a $invalidations parameter.
   */
  public function routeTypeToMethod($type);

}

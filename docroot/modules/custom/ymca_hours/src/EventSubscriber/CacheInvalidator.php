<?php

namespace Drupal\ymca_hours\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * Class CacheInvalidator.
 *
 * @package Drupal\ymca_hours\EventSubscriber
 */
class CacheInvalidator implements EventSubscriberInterface {

  /**
   * Alias manager.
   *
   * @var AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Entity Type Manager.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * State.
   *
   * @var StateInterface
   */
  protected $state;

  /**
   * The cache tags invalidator.
   *
   * @var CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Constructs CacheInvalidator.
   *
   * @param RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(AliasManagerInterface $alias_manager, EntityTypeManagerInterface $entity_type_manager, StateInterface $state, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->aliasManager = $alias_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * Invalidates hours block cache if it's expired.
   */
  public function checkHoursBlock(GetResponseEvent $event) {
    // Get current path.
    $alias = parse_url($_SERVER['HTTP_REFERER'])['path'];
    if (!$path = $this->aliasManager->getPathByAlias($alias, 'en')) {
      return;
    }

    // Get node by path.
    $explode = explode('/', $path);
    $nid = end($explode);
    $storage = $this->entityTypeManager->getStorage('node');

    if (!$node = $storage->load($nid)) {
      return;
    }

    if ($node->bundle() != 'location') {
      return;
    }

    if (!$hours_block_id = $node->get('field_working_hours')->get(0)->target_id) {
      return;
    }

    // Invalidate cache if there is no expiration date or block is expired already.
    $expire = $this->state->get('ymca_hours_' . $hours_block_id);
    if (!$expire || REQUEST_TIME > $expire) {
      $this->cacheTagsInvalidator->invalidateTags(['block_content:' . $hours_block_id]);
    }
  }

  /**
   * {@inheritdoc}
   */
  static public function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkHoursBlock'];
    return $events;
  }

}

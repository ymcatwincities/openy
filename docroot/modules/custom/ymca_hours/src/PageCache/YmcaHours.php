<?php

namespace Drupal\ymca_hours\PageCache;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Cache policy for the toolbar page cache service.
 *
 * This policy allows caching of requests directed to /toolbar/subtrees/{hash}
 * even for authenticated users.
 */
class YmcaHours implements RequestPolicyInterface {

  /**
   * AliasManager.
   *
   * @var AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * EntityTypeManager.
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
   * CacheTagsInvalidator.
   *
   * @var CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * YmcaHours constructor.
   *
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   AliasManager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   EntityTypeManager.
   * @param \Drupal\Core\State\StateInterface $state
   *   State.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   CacheTagsInvalidator.
   */
  public function __construct(AliasManagerInterface $alias_manager, EntityTypeManagerInterface $entity_type_manager, StateInterface $state, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->aliasManager = $alias_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    $alias = $request->getPathInfo();
    $path = $this->aliasManager->getPathByAlias($alias, 'en');

    // Get node by path.
    $explode = explode('/', $path);
    $nid = end($explode);
    $storage = $this->entityTypeManager->getStorage('node');

    if (!$node = $storage->load($nid)) {
      return static::ALLOW;
    }

    if ($node->bundle() != 'location') {
      return static::ALLOW;
    }

    if ($field = $node->get('field_working_hours')) {
      foreach ($field as $item) {
        if ($target = $item->get('target_id')) {
          $id = $target->getValue();
          $expire = $this->state->get('ymca_hours_' . $id);
          if (!$expire || REQUEST_TIME > $expire) {
            $this->cacheTagsInvalidator->invalidateTags(['block_content:' . $id]);
          }
        }
      }
    }

    return static::ALLOW;
  }

}

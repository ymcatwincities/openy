<?php

namespace Drupal\ymca_alters;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\node\NodeInterface;

/**
 * Class ListCacheInvalidator.
 *
 * Invalidates cache tags for parent nodes (camps, locations) by inspecting
 * blog post entity reference fields.
 */
class ListCacheInvalidator {

  /**
   * CacheTagsInvalidator.
   *
   * @var CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * ListCacheInvalidator constructor.
   *
   * @param CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   CacheTagsInvalidatorInterface.
   */
  public function __construct(CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * Invalidate tags.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Blog node.
   */
  public function invalidate(NodeInterface $node) {
    $tags = [];

    $fields = ['field_site_section', 'field_related_camps_locations'];
    foreach ($fields as $field_name) {
      if ($node->hasField($field_name)) {
        $field = $node->get($field_name);
        if (!$field->isEmpty()) {
          /** @var EntityReferenceItem $item */
          foreach ($field as $item) {
            $tags[] = 'node:' . $item->get('target_id')->getValue();
          }
        }
      }
    }

    $this->cacheTagsInvalidator->invalidateTags($tags);
  }

}

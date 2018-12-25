<?php

namespace Drupal\purge_queuer_coretags;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * Queues invalidated cache tags.
 */
class CacheTagsQueuer implements CacheTagsInvalidatorInterface, ContainerAwareInterface {
  use ContainerAwareTrait;

  /**
   * A list of tag prefixes that should not go into the queue.
   *
   * @var string[]
   */
  protected $blacklistedTagPrefixes;

  /**
   * A list of tags that have already been invalidated in this request.
   *
   * Used to prevent the invalidation of the same cache tag multiple times.
   *
   * @var string[]
   */
  protected $invalidatedTags = [];

  /**
   * @var null|\Drupal\purge\Plugin\Purge\Invalidation\InvalidationsServiceInterface
   */
  protected $purgeInvalidationFactory;

  /**
   * @var null|\Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface
   */
  protected $purgeQueue;

  /**
   * The queuer plugin or FALSE when disabled.
   *
   * @var false|\Drupal\purge_queuer_coretags\Plugin\Purge\Queuer\CoreTagsQueuer
   */
  protected $queuer;

  /**
   * Initialize the invalidation factory and queue service.
   *
   * @return bool
   *   TRUE when everything is available, FALSE when our plugin is disabled.
   */
  protected function initialize() {
    if (is_null($this->queuer)) {
      // If the coretags queuer plugin doesn't load, this object is not allowed
      // to operate and thus loads the least possible dependencies.
      $this->queuer = $this->container->get('purge.queuers')->get('coretags');
      if ($this->queuer !== FALSE) {
        $this->purgeInvalidationFactory = $this->container->get('purge.invalidation.factory');
        $this->purgeQueue = $this->container->get('purge.queue');
        $this->blacklistedTagPrefixes = $this->container
          ->get('config.factory')
          ->get('purge_queuer_coretags.settings')
          ->get('blacklist');

        // Make sure to disable ourselves during module installation.
        if (!is_array($this->blacklistedTagPrefixes)) {
          $this->queuer = FALSE;
          return FALSE;
        }
      }
    }
    return $this->queuer !== FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * Queues invalidated cache tags as tag purgables.
   */
  public function invalidateTags(array $tags) {
    if (!$this->initialize()) {
      return;
    }

    // Iterate each given tag and only add those we didn't queue before.
    $invalidations = [];
    foreach ($tags as $tag) {
      if (!in_array($tag, $this->invalidatedTags)) {

        // Check the tag against the blacklist and skip if it matches.
        $blacklisted = FALSE;
        foreach ($this->blacklistedTagPrefixes as $prefix) {
          if (strpos($tag, $prefix) !== FALSE) {
            $blacklisted = TRUE;
          }
        }
        if (!$blacklisted) {
          try {
            $invalidations[] = $this->purgeInvalidationFactory->get('tag', $tag);
            $this->invalidatedTags[] = $tag;
          }
          catch (PluginNotFoundException $e) {
            // When Drupal uninstalls Purge, rebuilds plugin caches it might
            // run into the condition where the tag plugin isn't available. In
            // these scenarios we want the queuer to silently fail.
            return;
          }
        }
      }
    }

    // The queue buffers invalidations, though we don't care about that here.
    if (count($invalidations)) {
      $this->purgeQueue->add($this->queuer, $invalidations);
    }
  }

}

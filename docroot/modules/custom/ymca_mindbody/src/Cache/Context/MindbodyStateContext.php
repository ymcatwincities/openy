<?php

namespace Drupal\ymca_mindbody\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Cache\Context\CacheContextInterface;

/**
 * Defines the MindbodyStateContext service, for "per Mindbody state" caching.
 *
 * Cache context ID: 'mindbody_state'.
 */
class MindbodyStateContext implements CacheContextInterface {

  /**
   * Amount of free daily API calls.
   */
  const FREE_API_CALLS_LIMIT = 1000;

  /**
   * Limit is exceeded value.
   */
  const LIMIT_EXCEEDED = 1;

  /**
   * Limit isn't exceeded value.
   */
  const LIMIT_NOT_EXCEEDED = -1;

  /**
   * The Key/Value Store to use for state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new MindbodyCacheContext class.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state keyvalue store.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Mindbody state');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    $mindbody_proxy_state = $this->state->get('mindbody_cache_proxy');
    if (isset($mindbody_proxy_state->miss) && $mindbody_proxy_state->miss >= $this::FREE_API_CALLS_LIMIT) {
      return $this::LIMIT_EXCEEDED;
    }
    return $this::LIMIT_NOT_EXCEEDED;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    $cacheable_metadata = new CacheableMetadata();
    $cacheable_metadata->setCacheTags(['mindbody_state']);
    return $cacheable_metadata;
  }

}

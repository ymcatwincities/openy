<?php

namespace Drupal\personify_mindbody_sync;

/**
 * Class PersonifyMindbodySyncFetcher.
 *
 * @package Drupal\personify_mindbody_sync
 */
class PersonifyMindbodySyncFetcher implements PersonifyMindbodySyncFetcherInterface {

  /**
   * PersonifyMindbodySyncWrapper definition.
   *
   * @var PersonifyMindbodySyncWrapper
   */
  protected $wrapper;

  /**
   * PersonifyMindbodySyncFetcher constructor.
   *
   * @param PersonifyMindbodySyncWrapper $wrapper
   *   Wrapper.
   */
  public function __construct(PersonifyMindbodySyncWrapper $wrapper) {
    $this->wrapper = $wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch() {
    $a = 10;
  }

}

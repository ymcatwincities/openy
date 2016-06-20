<?php

namespace Drupal\personify_mindbody_sync;

/**
 * Class PersonifyMindbodySyncProxy.
 *
 * @package Drupal\personify_mindbody_sync
 */
class PersonifyMindbodySyncProxy implements PersonifyMindbodySyncProxyInterface {

  /**
   * PersonifyMindbodySyncWrapper definition.
   *
   * @var PersonifyMindbodySyncWrapper
   */
  protected $wrapper;

  /**
   * PersonifyMindbodySyncProxy constructor.
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
  public function saveEntities() {
    $a = 10;
  }

}

<?php

namespace Drupal\purge\Plugin\Purge\Queue;

/**
 * Adds default selectPage* method implementations to queue implementations.
 */
trait QueueBasePageTrait {

  /**
   * The configured limit of items on selected data pages.
   *
   * @var int
   */
  protected $selectPageLimit = 15;

  /**
   * {@inheritdoc}
   */
  public function selectPageLimit($set_limit_to = NULL) {
    if (is_int($set_limit_to)) {
      $this->selectPageLimit = $set_limit_to;
    }
    return $this->selectPageLimit;
  }

  /**
   * {@inheritdoc}
   */
  public function selectPageMax() {
    $max = ( (int) $this->numberOfItems() ) / $this->selectPageLimit();
    return intval(ceil($max));
  }

}

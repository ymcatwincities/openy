<?php

namespace Drupal\purge_purger_test\Plugin\Purge\Purger;

use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge_purger_test\Plugin\Purge\Purger\NullPurgerBase;

/**
 * A purger that always succeeds.
 *
 * @PurgePurger(
 *   id = "good",
 *   label = @Translation("Good Purger"),
 *   configform = "",
 *   cooldown_time = 1.0,
 *   description = @Translation("A purger that always succeeds."),
 *   multi_instance = FALSE,
 *   types = {"tag", "path", "domain"},
 * )
 */
class GoodPurger extends NullPurgerBase {

  /**
   * {@inheritdoc}
   */
  public function invalidate(array $invalidations) {
    foreach ($invalidations as $invalidation) {
      $invalidation->setState(InvalidationInterface::SUCCEEDED);
    }
  }

}

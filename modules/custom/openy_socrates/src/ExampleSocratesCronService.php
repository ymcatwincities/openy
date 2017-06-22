<?php

namespace Drupal\openy_socrates;

/**
 * Class ExampleSocratesCronService.
 *
 * @package Drupal\openy_socrates
 */
class ExampleSocratesCronService implements OpenyCronServiceInterface {

  /**
   * {@inheritdoc}
   */
  public function runCronServices() {
    \Drupal::logger('test')->info("Example socrates cron service run succeeded.");
  }

}

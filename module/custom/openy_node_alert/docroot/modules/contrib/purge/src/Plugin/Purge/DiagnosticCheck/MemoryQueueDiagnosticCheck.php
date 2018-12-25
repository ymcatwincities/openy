<?php

namespace Drupal\purge\Plugin\Purge\DiagnosticCheck;

use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckBase;

/**
 * Issues a warning on how unreliable the memory queue is for day-day use.
 *
 * @PurgeDiagnosticCheck(
 *   id = "memoryqueuewarning",
 *   title = @Translation("Memory queue"),
 *   description = @Translation("Checks when the memory queue is in use."),
 *   dependent_queue_plugins = {"memory"},
 *   dependent_purger_plugins = {}
 * )
 */
class MemoryQueueDiagnosticCheck extends DiagnosticCheckBase implements DiagnosticCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function run() {

    // There's nothing to test for here, as this check only gets loaded when
    // the memory queue is active, so we can jump straight to conclusions.
    $this->recommendation = $this->t("You are using the memory queue, which is not recommend for day to day use. Anything stored in this queue, gets lost if it doesn't get processed during the same request.");
    return SELF::SEVERITY_WARNING;
  }

}

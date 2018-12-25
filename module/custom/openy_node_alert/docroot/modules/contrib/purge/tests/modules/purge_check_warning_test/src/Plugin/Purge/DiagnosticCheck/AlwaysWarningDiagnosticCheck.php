<?php

namespace Drupal\purge_check_warning_test\Plugin\Purge\DiagnosticCheck;

use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckBase;

/**
 * Checks if there is a purger plugin that invalidates an external cache.
 *
 * @PurgeDiagnosticCheck(
 *   id = "alwayswarning",
 *   title = @Translation("Always a warning"),
 *   description = @Translation("A fake test to test the diagnostics api."),
 *   dependent_queue_plugins = {},
 *   dependent_purger_plugins = {}
 * )
 */
class AlwaysWarningDiagnosticCheck extends DiagnosticCheckBase implements DiagnosticCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function run() {
    $this->recommendation = $this->t("This is a warning for testing.");
    return SELF::SEVERITY_WARNING;
  }

}

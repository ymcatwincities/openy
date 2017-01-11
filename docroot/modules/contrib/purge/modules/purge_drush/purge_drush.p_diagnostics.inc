<?php

/**
 * @file
 * Administrative Drush commands for Purge - Command File.
 */

use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;

/**
 * Generate a diagnostic self-service report.
 *
 * @param string $format
 *   The default or selected output format of the command.
 * @param \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface $purgeDiagnostics
 *   The purge diagnostics service.
 *
 * @return array
 *   Drush render/format array.
 */
function purge_drush_p_diagnostics($format, DiagnosticsServiceInterface $purgeDiagnostics) {
  if (!in_array($format, ['table', 'yaml', 'json'])) {
    return [];
  }
  $output = [];
  foreach ($purgeDiagnostics as $check) {
    if ($format == 'table') {
      // Empty row for readability.
      $output[] = [];
    }
    $output[] = [
      'id' => (string) $check->getPluginId(),
      'title' => (string) $check->getTitle(),
      'value' => (string) $check->getValue(),
      'severity_int' => $check->getSeverity(),
      'severity' => (string) $check->getSeverityString(),
      'description' => (string) $check->getDescription(),
      'recommendation' => (string) $check->getRecommendation(),
      'blocks_processing' => $check->getSeverity() === DiagnosticCheckInterface::SEVERITY_ERROR,
    ];
  }
  return $output;
}

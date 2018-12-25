<?php

namespace Drupal\purge\Plugin\Purge\DiagnosticCheck;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\purge\ServiceInterface;

/**
 * Describes a service that interacts with diagnostic checks.
 */
interface DiagnosticsServiceInterface extends ServiceInterface, ContainerAwareInterface, \Iterator, \Countable {

  /**
   * Generates a hook_requirements() compatible array.
   *
   * @warning
   *   Although it shares the same name, this method doesn't return a individual
   *   item array as \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface::
   *     getHookRequirementsArray() does. It returns a full array (as
   *   hook_requirements() expects) for all checks.
   *
   * @return array
   *   An associative array where the keys are arbitrary but unique (check id)
   *   and the values themselves are associative arrays with these elements:
   *   - title: The name of this check.
   *   - value: The current value (e.g., version, time, level, etc), will not
   *     be set if not applicable.
   *   - description: The description of the check.
   *   - severity: The checks result/severity level, one of:
   *     - REQUIREMENT_INFO: For info only.
   *     - REQUIREMENT_OK: The requirement is satisfied.
   *     - REQUIREMENT_WARNING: The requirement failed with a warning.
   *     - REQUIREMENT_ERROR: The requirement failed with an error.
   */
  public function getHookRequirementsArray();

  /**
   * Generates a status-report.html.twig compatible array.
   *
   * The main difference with ::getHookRequirementsArray is that this helper is
   * not intended to be used in a hook_requirements() implementation but rather
   * when rendering status reports directly using #theme = 'status_report',
   *
   * @return array
   *   An associative array where the keys are arbitrary but unique (check id)
   *   and the values themselves are associative arrays with these elements:
   *   - title: The name of this check.
   *   - value: The current value (e.g., version, time, level, etc), will not
   *     be set if not applicable.
   *   - description: The description of the check.
   *   - severity: The checks result/severity level, one of:
   *     - REQUIREMENT_INFO: For info only.
   *     - REQUIREMENT_OK: The requirement is satisfied.
   *     - REQUIREMENT_WARNING: The requirement failed with a warning.
   *     - REQUIREMENT_ERROR: The requirement failed with an error.
   */
  public function getRequirementsArray();

  /**
   * Reports if any of the diagnostic checks report a SEVERITY_ERROR severity.
   *
   * This method provides a simple - boolean evaluable - way to determine if
   * a \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface::SEVERITY_ERROR severity
   * was reported by one of the checks. If SEVERITY_ERROR was reported, purging
   * cannot continue and should happen once all problems are resolved.
   *
   * @return false|\Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface
   *   The SEVERITY_ERROR reporting check, or FALSE when everything was fine.
   */
  public function isSystemOnFire();

  /**
   * Reports if any of the diagnostic checks report a SEVERITY_WARNING severity.
   *
   * This method provides a - boolean evaluable - way to determine if a check
   * reported a \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface::SEVERITY_WARNING.
   * If SEVERITY_WARNING was reported, cache invalidation can continue but it is
   * important that the site administrator gets notified.
   *
   * @return false|\Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface
   *   The SEVERITY_WARNING reporting check, or FALSE when everything was fine.
   */
  public function isSystemShowingSmoke();

}

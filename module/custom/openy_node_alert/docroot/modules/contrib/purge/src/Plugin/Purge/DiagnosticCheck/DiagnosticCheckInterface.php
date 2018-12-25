<?php

namespace Drupal\purge\Plugin\Purge\DiagnosticCheck;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Describes a diagnostic check that checks a specific purging requirement.
 */
interface DiagnosticCheckInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Non-blocking severity -- Informational message only.
   */
  const SEVERITY_INFO = -1;

  /**
   * Non-blocking severity -- check successfully passed.
   */
  const SEVERITY_OK = 0;

  /**
   * Non-blocking severity -- Warning condition; proceed but flag warning.
   */
  const SEVERITY_WARNING = 1;

  /**
   * BLOCKING severity -- Error condition; purge.purgers service cannot operate.
   */
  const SEVERITY_ERROR = 2;

  /**
   * Perform the check and determine the severity level.
   *
   * Diagnostic checks determine whether something you are checking for is in
   * shape, for instance CMI settings on which your plugin depends. Any check
   * reporting SELF::SEVERITY_ERROR in their run() methods, will cause purge to
   * stop working. Any other severity level will let the purgers proceed
   * operating but you may report any warning through getRecommendation() to be
   * shown on Drupal's status report, purge_ui or any other diagnostic listing.
   *
   * @code
   * public function run() {
   *   if (...check..) {
   *     return SELF::SEVERITY_OK;
   *   }
   *   return SELF::SEVERITY_WARNING;
   * }
   * @endcode
   *
   * @warning
   *   As diagnostic checks can be expensive, this method is called as rarely as
   *   possible. Checks derived from \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckBase
   *   will only see the check getting executed when any of the get* methods are
   *   called.
   *
   * @throws \Drupal\purge\Plugin\Purge\DiagnosticCheck\Exception\CheckNotImplementedCorrectly
   *   Thrown when the return value is incorrect.
   *
   * @return int
   *   Integer, matching either of the following constants:
   *    - \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface::SEVERITY_INFO
   *    - \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface::SEVERITY_OK
   *    - \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface::SEVERITY_WARNING
   *    - \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface::SEVERITY_ERROR
   */
  public function run();

  /**
   * Gets the title of the check.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getTitle();

  /**
   * Gets the description of the check.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getDescription();

  /**
   * Get the severity level.
   *
   * @return int
   *   Integer, matching either of the following constants:
   *    - \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface::SEVERITY_INFO
   *    - \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface::SEVERITY_OK
   *    - \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface::SEVERITY_WARNING
   *    - \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface::SEVERITY_ERROR
   */
  public function getSeverity();

  /**
   * Get the severity level as unprefixed string.
   *
   * @return string
   *  The string comes without the 'SEVERITY_' prefix as on the constants.
   */
  public function getSeverityString();

  /**
   * Get a recommendation matching the severity level, may return NULL.
   *
   * @return NULL or \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getRecommendation();

  /**
   * Get an optional value for the check output, may return NULL.
   *
   * @return NULL or \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  public function getValue();

  /**
   * Generates a hook_requirements() compatible item array.
   *
   * @return array
   *   An associative array with the following elements:
   *   - title: The name of this check.
   *   - value: The current value (e.g., version, time, level, etc), will not
   *     be set if not applicable.
   *   - description: The description of the check.
   *   - severity: The check's result/severity level, one of:
   *     - REQUIREMENT_INFO: For info only.
   *     - REQUIREMENT_OK: The requirement is satisfied.
   *     - REQUIREMENT_WARNING: The requirement failed with a warning.
   *     - REQUIREMENT_ERROR: The requirement failed with an error.
   */
  public function getHookRequirementsArray();

  /**
   * Generates a status-report.html.twig compatible array.
   *
   * @return array
   *   An associative array with the following elements:
   *   - title: The name of this check.
   *   - value: The current value (e.g., version, time, level, etc), will not
   *     be set if not applicable.
   *   - description: The description of the check.
   *   - severity: The check's result/severity level, one of:
   *     - REQUIREMENT_INFO: For info only.
   *     - REQUIREMENT_OK: The requirement is satisfied.
   *     - REQUIREMENT_WARNING: The requirement failed with a warning.
   *     - REQUIREMENT_ERROR: The requirement failed with an error.
   */
  public function getRequirementsArray();

  /**
   * Get the severity level, expressed as a status_report severity.
   *
   * @return int
   *   Integer, matching either of the following constants:
   *    - REQUIREMENT_INFO
   *    - REQUIREMENT_OK
   *    - REQUIREMENT_WARNING
   *    - REQUIREMENT_ERROR
   */
  public function getRequirementsSeverity();

}

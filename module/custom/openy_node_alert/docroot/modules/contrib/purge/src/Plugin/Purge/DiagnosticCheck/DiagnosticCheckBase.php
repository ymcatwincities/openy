<?php

namespace Drupal\purge\Plugin\Purge\DiagnosticCheck;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\Exception\CheckNotImplementedCorrectly;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;

/**
 * Describes a diagnostic check that tests a specific purging requirement.
 */
abstract class DiagnosticCheckBase extends PluginBase implements DiagnosticCheckInterface {

  /**
   * The title of the check as described in the plugin's metadata.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  private $title;

  /**
   * The description of the check as described in the plugin's metadata.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  private $description;

  /**
   * The severity of the outcome of this check, maps to any of these constants:
   *    - \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface::SEVERITY_INFO
   *    - \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface::SEVERITY_OK
   *    - \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface::SEVERITY_WARNING
   *    - \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface::SEVERITY_ERROR
   *
   * @var int
   */
  private $severity;

  /**
   * A recommendation matching the severity level, may contain NULL.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected $recommendation;

  /**
   * Optional check outcome / value (e.g. version numbers), may contain NULL.
   *
   * @var mixed
   */
  protected $value;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Assures that \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface::run() is executed
   * and that the severity gets set on the object. Tests for invalid responses.
   */
  protected function runCheck() {
    if (!is_null($this->severity)) {
      return;
    }
    $this->severity = $this->run();
    if (!is_int($this->severity)) {
      $class = $this->getPluginDefinition()['class'];
      throw new CheckNotImplementedCorrectly("Exected integer as return from $class::run()!");
    }
    if ($this->severity < -1 || $this->severity > 2) {
      $class = $this->getPluginDefinition()['class'];
      throw new CheckNotImplementedCorrectly("Invalid const returned by $class::run()!");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    $this->runCheck();
    if (is_null($this->title)) {
      $this->title = $this->getPluginDefinition()['title'];
    }
    return $this->title;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $this->runCheck();
    if (is_null($this->description)) {
      $this->description = $this->getPluginDefinition()['description'];
    }
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getSeverity() {
    $this->runCheck();
    return $this->severity;
  }

  /**
   * {@inheritdoc}
   */
  public function getSeverityString() {
    $this->runCheck();
    $mapping = [
      SELF::SEVERITY_INFO      => 'INFO',
      SELF::SEVERITY_OK        => 'OK',
      SELF::SEVERITY_WARNING   => 'WARNING',
      SELF::SEVERITY_ERROR     => 'ERROR',
    ];
    return $mapping[$this->getSeverity()];
  }

  /**
   * {@inheritdoc}
   */
  public function getRecommendation() {
    $this->runCheck();
    if ($this->recommendation) {
      return $this->recommendation;
    }
    else {
      return $this->getDescription();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $this->runCheck();
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getHookRequirementsArray() {
    $this->runCheck();
    return [
      'title' => $this->t('Purge - @title', ['@title' => $this->getTitle()]),
      'value' => (string) $this->getValue(),
      'description' => (string) $this->getRecommendation(),
      'severity' => $this->getRequirementsSeverity(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRequirementsArray() {
    $this->runCheck();
    return [
      'title' => (string) $this->getTitle(),
      'value' => (string) $this->getValue(),
      'description' => (string) $this->getRecommendation(),
      'severity' => $this->getRequirementsSeverity(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRequirementsSeverity() {
    static $mapping;
    $this->runCheck();
    if (is_null($mapping)) {
      include_once DRUPAL_ROOT . '/core/includes/install.inc';

      // Currently, our constants hold the exact same values as core's
      // requirement constants. However, as our diagnostic checks API is more
      // than just a objectification of hook_requirements we need to assure
      // that this lasts over time, and thus map the constants.
      $mapping = [
        SELF::SEVERITY_INFO      => REQUIREMENT_INFO,
        SELF::SEVERITY_OK        => REQUIREMENT_OK,
        SELF::SEVERITY_WARNING   => REQUIREMENT_WARNING,
        SELF::SEVERITY_ERROR     => REQUIREMENT_ERROR,
      ];
    }
    return $mapping[$this->getSeverity()];
  }

}

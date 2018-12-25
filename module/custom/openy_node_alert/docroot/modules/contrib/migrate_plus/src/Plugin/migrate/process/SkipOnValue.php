<?php

/**
 * @file
 * Contains \Drupal\migrate_plus\Plugin\migrate\process\SkipOnValue.
 */

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * If the source evaluates to a configured value, skip processing or whole row.
 *
 * @MigrateProcessPlugin(
 *   id = "skip_on_value"
 * )
 */
class SkipOnValue extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function row($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($this->configuration['value']) && !array_key_exists('value', $this->configuration)) {
      throw new MigrateException('Skip on value plugin is missing value configuration.');
    }

    if (is_array($this->configuration['value'])) {
      foreach ($this->configuration['value'] as $skipValue) {
        if ($this->compareValue($value, $skipValue, !isset($this->configuration['not_equals']))) {
          throw new MigrateSkipRowException();
        }
      }
    }
    elseif ($this->compareValue($value, $this->configuration['value'], !isset($this->configuration['not_equals']))) {
      throw new MigrateSkipRowException();
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function process($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($this->configuration['value']) && !array_key_exists('value', $this->configuration)) {
      throw new MigrateException('Skip on value plugin is missing value configuration.');
    }

    if (is_array($this->configuration['value'])) {
      foreach ($this->configuration['value'] as $skipValue) {
        if ($this->compareValue($value, $skipValue, !isset($this->configuration['not_equals']))) {
          throw new MigrateSkipProcessException();
        }
      }
    }
    elseif ($this->compareValue($value, $this->configuration['value'], !isset($this->configuration['not_equals']))) {
      throw new MigrateSkipProcessException();
    }

    return $value;
  }

  /**
   * Compare values to see if they are equal.
   *
   * @param $value
   *   Actual value
   * @param $skipValue
   *   Value to compare against.
   * @param $equal
   *   Compare as equal or not equal.
   *
   * @return bool
   *   True if the compare successfully, FALSE otherwise.
   */
  protected function compareValue($value, $skipValue, $equal = TRUE) {
    if ($equal) {
      return (string) $value == (string) $skipValue;
    }

    return (string) $value != (string) $skipValue;

  }

}

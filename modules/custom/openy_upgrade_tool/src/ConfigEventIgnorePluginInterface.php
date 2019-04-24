<?php

namespace Drupal\openy_upgrade_tool;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines the interface for config event ignore.
 *
 * @see \Drupal\openy_upgrade_tool\Annotation\ConfigEventIgnore
 * @see \Drupal\openy_upgrade_tool\ConfigEventIgnoreBase
 * @see \Drupal\openy_upgrade_tool\ConfigEventIgnorePluginManager
 * @see plugin_api
 */
interface ConfigEventIgnorePluginInterface extends PluginInspectionInterface {

  /**
   * Returns the config event ignore label.
   *
   * @return string
   *   The config event ignore label.
   */
  public function label();

  /**
   * Returns the config type that related to this config event ignore.
   *
   * @return string
   *   The config type.
   */
  public function getConfigType();

  /**
   * Returns the plugin weight.
   *
   * @return int
   *   Plugin weight.
   */
  public function weight();

  /**
   * Returns total ignore status.
   *
   * @return bool
   *   TRUE in case if you need to ignore any config changes.
   */
  public function fullIgnore();

  /**
   * List of ignore rules that used for upgrade tool on config save.
   *
   * Any list item should contain "value" and "operator" keys.
   *   Value - path to config key (Example: dependencies.config), it can be
   *     normal string or regular expression.
   *   Operator - operator that used for diff keys comparing (REGEXP_OPERATOR,
   *     EQUAL_OPERATOR).
   *
   * Example for views config:
   * array(
   *   array(
   *     'value' => '^display\..+\.cache_metadata.+',
   *     'operator' => self::REGEXP_OPERATOR,
   *   ),
   *   array(
   *     'value' => 'dependencies',
   *     'operator' => self::EQUAL_OPERATOR,
   *   ),
   * );
   *
   * Example for content type config:
   * array(
   *   array(
   *     'value' => 'dependencies.module',
   *     'operator' => self::EQUAL_OPERATOR,
   *   ),
   *   array(
   *     'value' => 'third_party_settings',
   *     'operator' => self::EQUAL_OPERATOR,
   *   ),
   * );
   *
   * @return array
   *   Config keys.
   */
  public function getRules();

}

<?php

namespace Drupal\openy_upgrade_tool;

use Drupal\Component\Plugin\PluginBase;

/**
 * Provides a base class for config event ignore.
 *
 * @see \Drupal\openy_upgrade_tool\Annotation\ConfigEventIgnore
 * @see \Drupal\openy_upgrade_tool\ConfigEventIgnoreBase
 * @see \Drupal\openy_upgrade_tool\ConfigEventIgnorePluginManager
 * @see plugin_api
 */
abstract class ConfigEventIgnoreBase extends PluginBase implements ConfigEventIgnorePluginInterface {

  /**
   * Regexp operator type.
   *
   * Intended for getRules method.
   * In case using this operator, value item should contain regular expression.
   *
   * @see \Drupal\openy_upgrade_tool\Plugin\ConfigEventIgnore\Views
   */
  const REGEXP_OPERATOR = 'regexp';

  /**
   * Equal operator type.
   *
   * Intended for getRules method.
   * In case using this operator, value item should contain string for comparing.
   */
  const EQUAL_OPERATOR = 'equal';

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigType() {
    return $this->pluginDefinition['type'];
  }

  /**
   * {@inheritdoc}
   */
  public function weight() {
    return $this->pluginDefinition['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function fullIgnore() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRules() {
    return [];
  }

  /**
   * Helper function for checking modified config keys.
   *
   * @param array $diff
   *   Result from ConfigEventIgnorePluginManager::getModifiedKeys.
   *
   * @return bool
   *   TRUE if we can ignore these changes.
   */
  public function checkIgnoreDiffKeys(array $diff) {
    $result = [];
    $rules = $this->getRules();

    foreach ($diff as $config_key) {
      $ignore_key = FALSE;
      foreach ($rules as $rule) {
        if ($this->applyRule($config_key, $rule)) {
          // If any of rules are suitable - ignore changes for this config key.
          $ignore_key = TRUE;
        }
      }
      $result[$config_key] = $ignore_key;
    }

    // If at least one of the keys has FALSE - return FALSE.
    // This mean that we have changes, that can't be ignored.
    return !in_array(FALSE, $result);
  }

  /**
   * {@inheritdoc}
   */
  public function applyRule($key, $rule) {
    $ignore = FALSE;
    switch ($rule['operator']) {
      case self::REGEXP_OPERATOR:
        preg_match($rule['value'], $key, $matches, PREG_OFFSET_CAPTURE);
        $ignore = !empty($matches);
        break;

      case self::EQUAL_OPERATOR:
      default:
        // Compare config key and value from rule, if they equal
        // we can ignore these changes.
        $ignore = $key == $rule['value'];
        break;
    }

    return $ignore;
  }

}

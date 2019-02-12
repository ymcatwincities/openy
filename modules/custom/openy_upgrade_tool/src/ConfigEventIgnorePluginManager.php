<?php

namespace Drupal\openy_upgrade_tool;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Utility\DiffArray;
use Drupal\Core\Config\Config;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages config event ignore plugins.
 *
 * @see \Drupal\openy_upgrade_tool\Annotation\ConfigEventIgnore
 * @see \Drupal\openy_upgrade_tool\ConfigEventIgnoreBase
 * @see \Drupal\openy_upgrade_tool\ConfigEventIgnorePluginManager
 * @see plugin_api
 */
class ConfigEventIgnorePluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/ConfigEventIgnore',
      $namespaces,
      $module_handler,
      'Drupal\openy_upgrade_tool\ConfigEventIgnorePluginInterface',
      'Drupal\openy_upgrade_tool\Annotation\ConfigEventIgnore'
    );
    $this->alterInfo('config_event_ignore_info');
    $this->setCacheBackend($cache_backend, 'config_event_ignore');
    $this->factory = new DefaultFactory($this->getDiscovery());
  }

  /**
   * Get ignore rules for specified config type.
   *
   * @param string $config_type
   *   Config type (entity_form_display, field_config, view, etc).
   *
   * @return array
   *   Config ignore rules.
   */
  public function getPluginForConfigType($config_type) {
    $plugins = [];

    foreach ($this->getDefinitions() as $plugin_id => $plugin) {
      try {
        $instance = $this->createInstance($plugin_id);
      }
      catch (PluginException $exception) {
        continue;
      }

      if ($instance->getConfigType() !== $config_type) {
        continue;
      }
      $plugins[] = [
        'plugin' => $instance,
        'weight' => $instance->weight(),
      ];
    }

    if (empty($plugins)) {
      return NULL;
    }
    usort($plugins, ['\Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
    $data = reset($plugins);

    return $data['plugin'];
  }

  /**
   * Check config ignore rules.
   *
   * @param \Drupal\Core\Config\Config $config
   *   Config object.
   * @param $config_type
   *   Config type.
   *
   * @return bool
   *   TRUE - if changes can be ignored.
   */
  public function validateChanges(Config $config, $config_type) {
    $original = $config->getOriginal();
    $updated = $config->get();
    $ignore_plugin = $this->getPluginForConfigType($config_type);

    if (!$ignore_plugin) {
      // Ignore rules not found for this config type.
      return FALSE;
    }

    if ($ignore_plugin->fullIgnore()) {
      // Ignore any changes for this config type.
      return TRUE;
    }

    // Get config changes.
    $diff = DiffArray::diffAssocRecursive($original, $updated);
    $diff_keys = $this->getModifiedKeys($diff);

    return $ignore_plugin->checkIgnoreDiffKeys($diff_keys);
  }

  /**
   * Get modified keys from nested array.
   *
   * Example:
   * array(
   *   'parent1' => array(
   *     'child1' => array(
   *       'child1-1' => array(
   *         'child1-1-1' => TRUE,
   *         'child1-1-2' => TRUE,
   *         'child1-1-3' => TRUE,
   *        ),
   *     ),
   *   ),
   *   'parent2' => array(
   *     'child1' => TRUE,
   *   ),
   *   'parent3' => array(
   *     'child1' => array(
   *       'child1-1' => array(
   *         'child1-1-1' => TRUE,
   *        ),
   *       'child2-1' => array(
   *         'child2-1-1' => TRUE,
   *        ),
   *     ),
   *   ),
   * );
   * Result:
   * array(
   *   'parent1.child1.child1-1',
   *   'parent2.child1',
   *   'parent3.child1',
   * )
   *
   * @param array $diff
   *   Result from DiffArray::diffAssocRecursive.
   *
   * @return array
   *   List of modified keys.
   */
  public function getModifiedKeys(array $diff) {
    $diff_keys = [];
    if (!empty($diff)) {
      foreach ($diff as $first_parent => $value) {
        // Collect all multidimensional parent keys + path to changed child key.
        if (is_array($value) && !empty($value) && count($value) == 1) {
          $diff_keys[] = $first_parent . '.' . $this->getChildKey($value);
        }
        else {
          $diff_keys[] = $first_parent;
        }
      }
    }

    return $diff_keys;
  }

  /**
   * Get path to nested array child key until it has 1 child element.
   *
   * Example:
   * array(
   *   'parent' => array(
   *     'child1' => array(
   *       'child1-1' => array(
   *         'child1-1-1' => TRUE,
   *         'child1-1-2' => TRUE,
   *         'child1-1-3' => TRUE,
   *        ),
   *     ),
   *   ),
   * );
   * Result - 'parent.child1.child1-1'
   *
   * @param array $array
   *   Config object.
   *
   * @return string
   *   Path to array child key.
   */
  public function getChildKey(array $array) {
    reset($array);
    $first_parent_key = key($array);
    if (is_array($array[$first_parent_key]) && !empty($array[$first_parent_key]) && count($array[$first_parent_key]) == 1) {
      $result = $first_parent_key . '.' . $this->getChildKey($array[$first_parent_key]);
    }
    else {
      $result = $first_parent_key;
    }

    return $result;
  }

}

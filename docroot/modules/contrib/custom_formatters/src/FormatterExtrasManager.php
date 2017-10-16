<?php

namespace Drupal\custom_formatters;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Class FormatterExtrasManager.
 *
 * @package Drupal\custom_formatters
 */
class FormatterExtrasManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/CustomFormatters/FormatterExtras', $namespaces, $module_handler, NULL, '\Drupal\custom_formatters\Annotation\FormatterExtras');
    // @TODO - Add alter hook here?
    $this->setCacheBackend($cache_backend, 'custom_formatters_formatter_extras_plugins');
  }

  /**
   * Passes alterable variables to specific methods.
   */
  public function alter($method, $entity, &$data, &$context1 = NULL, &$context2 = NULL) {
    $method = $method . "Alter";
    $definitions = $this->getDefinitions();

    if (is_array($definitions) && !empty($definitions)) {
      foreach ($definitions as $definition) {
        $extra = $this->createInstance($definition['id'], ['entity' => $entity]);
        if (method_exists($extra, $method)) {
          $extra->{$method}($data, $context1, $context2);
        }
      }
    }
  }

  /**
   * Invoke method on specified extras plugin.
   */
  public function invoke($plugin_id, $method, FormatterInterface $entity) {
    $args = func_get_args();
    array_shift($args);
    array_shift($args);
    array_shift($args);
    $definitions = $this->getDefinitions();

    if (isset($definitions[$plugin_id])) {
      $extra = $this->createInstance($plugin_id, ['entity' => $entity]);
      if (method_exists($extra, $method)) {
        return empty($args) ? $extra->{$method}() : call_user_func_array([$extra, $method], $args);
      }
    }

    return FALSE;
  }

  /**
   * Invoke method on all available extras.
   */
  public function invokeAll($method, FormatterInterface $entity) {
    $args = func_get_args();
    $definitions = $this->getDefinitions();

    $returns = [];
    if (is_array($definitions) && !empty($definitions)) {
      foreach ($definitions as $definition) {
        array_unshift($args, $definition['id']);
        $return = call_user_func_array([get_class($this), 'invoke'], $args);
        if ($return) {
          $returns[$definition['id']] = $return;
        }
      }
    }
    return $returns;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = parent::getDefinitions();

    // Ensure Extras configuration dependencies are met.
    if (isset($definitions) && is_array($definitions)) {
      foreach ($definitions as $definition) {
        if (!$this->validateDependencies($definition)) {
          unset($definitions[$definition['id']]);
        }
      }
    }

    return $definitions;
  }

  /**
   * Validate definition dependencies.
   *
   * @param array $definition
   *   The definition to validate.
   *
   * @return bool
   *   TRUE if dependencies met, else FALSE.
   */
  public function validateDependencies(array $definition) {
    if (empty($definition['dependencies'])) {
      return TRUE;
    }

    foreach ($definition['dependencies'] as $type => $dependencies) {
      if (!empty($dependencies)) {
        switch ($type) {
          case 'module':
            foreach ($dependencies as $dependency) {
              if (!\Drupal::moduleHandler()->moduleExists($dependency)) {
                return FALSE;
              }
            }
            break;
        }
      }
    }

    return TRUE;
  }

}

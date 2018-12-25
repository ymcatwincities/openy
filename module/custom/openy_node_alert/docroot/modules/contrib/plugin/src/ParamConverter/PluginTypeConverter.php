<?php

namespace Drupal\plugin\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;

/**
 * Converts plugin type IDs in route parameters to plugin types.
 *
 * To use it, add a `plugin.plugin_type` key to the route parameter's options.
 * Its value is as follows:
 * @code
 * example.route:
 *   path: foo/{bar}
 *   options:
 *     parameters:
 *       bar:
 *         plugin.plugin_type:
 *           # Whether the conversion is enabled. Boolean. Optional. Defaults
 *           # to TRUE.
 *           enabled: TRUE
 * @endcode
 *
 * To use the default behavior, its value is as follows:
 * @code
 * example.route:
 *   path: foo/{bar}
 *   options:
 *     parameters:
 *       bar:
 *         plugin.plugin_type: {}
 * @endcode
 */
class PluginTypeConverter implements ParamConverterInterface {

  use PluginTypeBasedConverterTrait;

  /**
   * {@inheritdoc}
   */
  public function doConvert($plugin_type_id, array $converter_definition) {
    if ($this->pluginTypeManager->hasPluginType($plugin_type_id)) {
      return $this->pluginTypeManager->getPluginType($plugin_type_id);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getConverterDefinitionKey() {
    return 'plugin.plugin_type';
  }

  /**
   * {@inheritdoc}
   */
  protected function getConverterDefinitionConstraint() {
    return new Collection([
      'enabled' => new Optional(new Type('boolean')),
    ]);
  }

}

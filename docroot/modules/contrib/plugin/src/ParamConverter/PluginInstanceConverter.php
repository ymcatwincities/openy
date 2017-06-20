<?php

namespace Drupal\plugin\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;

/**
 * Converts plugin IDs in route parameters to plugin instances.
 *
 * This is useful in case you want to create a plugin in the URI; for example,
 * if you use plugins as entity bundles.
 *
 * To use it, add a `plugin.plugin_instance` key to the route parameter's options.
 * Its value is as follows:
 * @code
 * example.route:
 *   path: foo/{bar}
 *   options:
 *     parameters:
 *       bar:
 *         plugin.plugin_instance:
 *           # Whether the conversion is enabled. Boolean. Optional. Defaults
 *           # to TRUE.
 *           enabled: TRUE
 *           # The ID of the instance's plugin type. String. Required.
 *           plugin_type_id: "foo.bar"
 * @endcode
 *
 * To use the default behavior, its value is as follows:
 * @code
 * example.route:
 *   path: foo/{bar}d
 *   options:
 *     parameters:
 *       bar:
 *         plugin.plugin_type:
 *           # The ID of the instance's plugin type. String. Required.
 *           plugin_type_id: "foo.bar"
 * @endcode
 */
class PluginInstanceConverter implements ParamConverterInterface {

  use PluginTypeBasedConverterTrait;

  /**
   * {@inheritdoc}
   */
  public function doConvert($plugin_id, array $converter_definition) {
    $plugin_type = $this->pluginTypeManager->getPluginType($converter_definition['plugin_type_id']);

    if ($plugin_type->getPluginManager()->hasDefinition($plugin_id)) {
      return $plugin_type->getPluginManager()->createInstance($plugin_id);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getConverterDefinitionKey() {
    return 'plugin.plugin_instance';
  }

  /**
   * {@inheritdoc}
   */
  protected function getConverterDefinitionConstraint() {
    return new Collection([
      'enabled' => new Optional(new Type('boolean')),
      'plugin_type_id' => new Type('string'),
    ]);
  }

}

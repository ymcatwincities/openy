<?php

namespace Drupal\plugin\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;

/**
 * Converts plugin IDs in route parameters to plugin definitions.
 *
 * To use it, add a `plugin.plugin_definition` key to the route parameter's options.
 * Its value is as follows:
 * @code
 * example.route:
 *   path: foo/{bar}
 *   options:
 *     parameters:
 *       bar:
 *         plugin.plugin_definition:
 *           # Whether the conversion is enabled. Boolean. Optional. Defaults
 *           # to TRUE.
 *           enabled: TRUE
 *           # The ID of the definition's plugin type. String. Required.
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
 *         plugin.plugin_definition:
 *           # The ID of the definition's plugin type. String. Required.
 *           plugin_type_id: "foo.bar"
 * @endcode
 */
class PluginDefinitionConverter implements ParamConverterInterface {

  use PluginTypeBasedConverterTrait;

  /**
   * {@inheritdoc}
   */
  public function doConvert($plugin_id, array $converter_definition) {
    $plugin_type = $this->pluginTypeManager->getPluginType($converter_definition['plugin_type_id']);

    if ($plugin_type->getPluginManager()->hasDefinition($plugin_id)) {
      try {
        return $plugin_type->ensureTypedPluginDefinition($plugin_type->getPluginManager()
          ->getDefinition($plugin_id));
      }
      catch (\InvalidArgumentException $e) {
        // The only way to see if a typed definition can be ensured is by trying
        // to ensure it. An exception is therefore expected and should not
        // bubble up.
        return NULL;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getConverterDefinitionKey() {
    return 'plugin.plugin_definition';
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

<?php

namespace Drupal\plugin\ParamConverter;

use Drupal\Core\Utility\Error;
use Drupal\plugin\PluginType\PluginTypeManagerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Validation;

/**
 * Implements \Drupal\Core\ParamConverter\ParamConverterInterface for plugin
 * type-based route parameter converters.
 */
trait PluginTypeBasedConverterTrait {

  /**
   * The plugin type manager.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeManagerInterface
   */
  protected $pluginTypeManager;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\plugin\PluginType\PluginTypeManagerInterface $plugin_type_manager
   */
  public function __construct(PluginTypeManagerInterface $plugin_type_manager) {
    $this->pluginTypeManager = $plugin_type_manager;
  }

  /**
   * Implements \Drupal\Core\ParamConverter\ParamConverterInterface::convert().
   */
  public function convert($value, $definition, $name, array $defaults) {
    $valid = $this->validateParameterDefinition($definition);
    if (!$valid) {
      return FALSE;
    }

    $converter_definition = $this->getConverterDefinition($definition);
    if (is_null($converter_definition)) {
      return NULL;
    }

    try {
      return $this->doConvert($value, $converter_definition, $name, $defaults);
    }
    catch (\Exception $e) {
      trigger_error(Error::renderExceptionSafe($e), E_USER_WARNING);
      // Return NULL in order to conform to the interface.
      return NULL;
    }
  }

  /**
   * Converts path variables to their corresponding objects.
   *
   * @param mixed $value
   *   The raw value.
   * @param mixed[] $converter_definition
   *   The converter definition provided in the route options.
   *
   * @return mixed|null
   *   The converted parameter value.
   *
   * @throws \Exception
   */
  abstract protected function doConvert($value, array $converter_definition);

  /**
   * Implements \Drupal\Core\ParamConverter\ParamConverterInterface::applies().
   */
  public function applies($definition, $name, Route $route) {
    $valid = $this->validateParameterDefinition($definition);
    if (!$valid) {
      return FALSE;
    }

    if (is_null($this->getConverterDefinition($definition))) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Gets the converter-specific parameter definition.
   *
   * @param mixed[] $definition
   *
   * @return mixed[]|null
   *   The processed definition or NULL if there is no definition
   */
  protected function getConverterDefinition(array $definition) {
    // There is no converter-specific definition.
    if (!array_key_exists($this->getConverterDefinitionKey(), $definition)) {
      return NULL;
    }

    $converter_definition = $definition[$this->getConverterDefinitionKey()];

    // Merge in defaults.
    $converter_definition += [
      'enabled' => TRUE,
    ];

    // The definition is disabled.
    if (!$converter_definition['enabled']) {
      return NULL;
    }

    return $converter_definition;
  }

  /**
   * Validates a route parameter's definition.
   *
   * @param mixed $definition
   *   The route parameter definition to validate.
   *
   * @return bool
   */
  protected function validateParameterDefinition($definition) {
    $validator = Validation::createValidator();
    $constraint = new Collection([
      'allowExtraFields' => TRUE,
      'fields' => [
        $this->getConverterDefinitionKey() => new Optional($this->getConverterDefinitionConstraint()),
      ],
    ]);
    $violations = $validator->validate($definition, $constraint);
    foreach ($violations as $violation) {
      trigger_error(sprintf("Error while validating the route parameter definition in item %s: %s\n\nOriginal data:\n%s", $violation->getPropertyPath(), $violation->getMessage(), var_export($violation->getRoot(), TRUE)), E_USER_WARNING);
    }

    return count($violations) === 0;
  }

  /**
   * Gets the top-level route parameter definition key for this converter.
   *
   * @return string
   */
  abstract protected function getConverterDefinitionKey();

  /**
   * Gets the parameter's converter definition validation constraint.
   *
   * @return \Symfony\Component\Validator\Constraint
   */
  abstract protected function getConverterDefinitionConstraint();

}

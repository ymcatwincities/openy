<?php

namespace Drupal\plugin_test_helper\Controller;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles the route to test \Drupal\plugin\ParamConverter\PluginDefinitionConverter.
 */
class PluginDefinitionParamConverter {

  /**
   * Executes the route.
   *
   * @param \Drupal\Component\Plugin\Definition\PluginDefinitionInterface $plugin_definition
   *
   * @return string
   */
  public function execute(PluginDefinitionInterface $plugin_definition) {
    return new Response($plugin_definition->getClass());
  }

}

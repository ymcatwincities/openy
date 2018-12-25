<?php

namespace Drupal\plugin_test_helper\Controller;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles the route to test \Drupal\plugin\ParamConverter\PluginTypeConverter.
 */
class PluginTypeParamConverter {

  /**
   * Executes the route.
   *
   * @param \Drupal\plugin\PluginType\PluginTypeInterface $plugin_type
   *
   * @return string
   */
  public function execute(PluginTypeInterface $plugin_type) {
    return new Response($plugin_type->getId());
  }

}

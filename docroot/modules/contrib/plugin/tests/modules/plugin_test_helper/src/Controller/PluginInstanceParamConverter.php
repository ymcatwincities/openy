<?php

namespace Drupal\plugin_test_helper\Controller;
use Drupal\plugin_test_helper\Plugin\PluginTestHelper\MockPluginInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles the route to test \Drupal\plugin\ParamConverter\PluginInstanceConverter.
 */
class PluginInstanceParamConverter {

  /**
   * Executes the route.
   *
   * @param \Drupal\plugin_test_helper\Plugin\PluginTestHelper\MockPluginInterface $plugin
   *
   * @return string
   */
  public function execute(MockPluginInterface $plugin) {
    return new Response($plugin->getPluginId());
  }

}

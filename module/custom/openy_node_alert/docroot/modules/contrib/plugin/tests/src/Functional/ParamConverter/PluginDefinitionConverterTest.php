<?php

namespace Drupal\Tests\plugin\Functional\ParamConverter;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests \Drupal\plugin\ParamConverter\PluginDefinitionConverter's integration.
 *
 * @group Plugin
 */
class PluginDefinitionConverterTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['plugin', 'plugin_test_helper', 'system'];

  /**
   * Tests the integration.
   */
  public function testIntegration() {
    $this->drupalGet('plugin_test_helper/paramconverter/plugin_definition/plugin_test_helper_plugin');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('plugin_test_helper/paramconverter/plugin_definition/foo');
    $this->assertSession()->statusCodeEquals(404);
  }

}

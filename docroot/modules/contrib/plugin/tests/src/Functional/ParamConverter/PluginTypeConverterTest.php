<?php

namespace Drupal\Tests\plugin\Functional\ParamConverter;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests \Drupal\plugin\ParamConverter\PluginTypeConverter's integration.
 *
 * @group Plugin
 */
class PluginTypeConverterTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['plugin', 'plugin_test_helper', 'system'];

  /**
   * Tests the integration.
   */
  public function testIntegration() {
    $this->drupalGet('plugin_test_helper/paramconverter/plugin_type/plugin_test_helper_mock');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('plugin_test_helper/paramconverter/plugin_type/foo');
    $this->assertSession()->statusCodeEquals(404);
  }

}

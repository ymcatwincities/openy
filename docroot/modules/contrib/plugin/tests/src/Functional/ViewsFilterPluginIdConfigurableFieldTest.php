<?php

namespace Drupal\Tests\plugin\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the configurable field integration of the "plugin_id" Views filter.
 *
 * @group Plugin
 */
class ViewsFilterPluginIdConfigurableFieldTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field', 'node', 'plugin', 'plugin_test_helper', 'plugin_test_pcf', 'system', 'user', 'views'];

  /**
   * Tests the integration.
   */
  public function testIntegration() {
    $entity = \Drupal::entityTypeManager()->getStorage('node')->create([
      'type' => 'plugin_test_pcf',
      'title' => 'Plugin configurable field',
    ]);
    $entity->save();

    // The filter class itself is tested using unit tests. Here we just assert
    // that a view using this filter does not break.
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = \Drupal::entityTypeManager()->getStorage('view')->load('plugin_test_pcf');
    $view->getExecutable()->execute();
    $this->assertCount(1, $view->getExecutable()->result);
  }

}

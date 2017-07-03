<?php

namespace Drupal\Tests\plugin\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the multi-value base field integration of the "plugin_id" Views filter.
 *
 * @group Plugin
 */
class ViewsFilterPluginIdMultiValueBaseFieldTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['plugin', 'plugin_test_helper', 'plugin_test_mvpbf', 'system', 'views'];

  /**
   * Tests the integration.
   */
  public function testIntegration() {
    $entity = \Drupal::entityTypeManager()->getStorage('plugin_test_mvpbf')->create();
    $entity->save();

    // The filter class itself is tested using unit tests. Here we just assert
    // that a view using this filter does not break.
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = \Drupal::entityTypeManager()->getStorage('view')->load('plugin_test_mvpbf');
    $view->getExecutable()->execute();
    $this->assertCount(1, $view->getExecutable()->result);
  }

}

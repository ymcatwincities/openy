<?php

namespace Drupal\plugin\Tests\Plugin\PluginSelector\PluginSelector;

use Drupal\simpletest\WebTestBase;

/**
 * \Drupal\plugin\Plugin\Plugin\PluginSelector\Radios web test.
 *
 * @group Plugin
 */
class RadiosWebTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('filter', 'plugin_test_helper');

  /**
   * Tests the element.
   */
  protected function testElement() {
    $this->doTestElement(FALSE);
    $this->doTestElement(TRUE);
  }

  protected function buildFormPath(array $allowed_selectable_plugin_ids, $tree) {
    return sprintf('plugin_test_helper-plugin_selector-advanced_plugin_selector_base/%s/plugin_radios/%d', implode(',', $allowed_selectable_plugin_ids), (int) $tree);
  }

  /**
   * Tests the element.
   *
   * @param bool $tree
   *   Whether to test the element with #tree = TRUE or not.
   */
  protected function doTestElement($tree) {
    $name_prefix = $tree ? 'tree[plugin][container]' : 'container';
    $change_button_name = $tree ? 'tree__plugin__container__select__container__change' : 'container__select__container__change';

    // Test the presence of default elements without available plugins.
    $path = $this->buildFormPath(['none'], $tree);
    $this->drupalGet($path);
    $this->assertNoFieldByName($name_prefix . '[select][container][container][plugin_id]');
    $this->assertNoFieldByName($change_button_name, t('Choose'));
    $this->assertText(t('There are no available options.'));

    // Test the presence of default elements with one available plugin.
    $path = $this->buildFormPath(['plugin_test_helper_configurable_plugin'], $tree);
    $this->drupalGet($path);
    $this->assertNoFieldByName($name_prefix . '[select][container][plugin_id]');
    $this->assertNoFieldByName($change_button_name, t('Choose'));
    $this->assertNoText(t('There are no available options.'));

    // Test the presence of default elements with multiple available plugins.
    $path = $this->buildFormPath(['plugin_test_helper_plugin', 'plugin_test_helper_configurable_plugin'], $tree);
    $this->drupalGet($path);
    $this->assertFieldByName($name_prefix . '[select][container][plugin_id]');
    $this->assertFieldByName($change_button_name, t('Choose'));
    $this->assertNoText(t('There are no available options.'));

    // Choose a plugin.
    $this->drupalPostForm(NULL, array(
      $name_prefix . '[select][container][plugin_id]' => 'plugin_test_helper_plugin',
    ), t('Choose'));
    $this->assertFieldByName($name_prefix . '[select][container][plugin_id]');
    $this->assertFieldByName($change_button_name, t('Choose'));

    // Change the plugin.
    $this->drupalPostForm(NULL, array(
      $name_prefix . '[select][container][plugin_id]' => 'plugin_test_helper_configurable_plugin',
    ), t('Choose'));
    $this->assertFieldByName($name_prefix . '[select][container][plugin_id]');
    $this->assertFieldByName($change_button_name, t('Choose'));

    // Submit the form.
    $foo = $this->randomString();
    $this->drupalPostForm(NULL, array(
      $name_prefix . '[select][container][plugin_id]' => 'plugin_test_helper_configurable_plugin',
      $name_prefix . '[plugin_form][foo]' => $foo,

    ), t('Submit'));

    $state = \Drupal::state();
    /** @var \Drupal\Component\Plugin\PluginInspectionInterface|\Drupal\Component\Plugin\ConfigurablePluginInterface $selected_plugin */
    $selected_plugin = $state->get('plugin_test_helper_advanced_plugin_selector_base');
    $this->assertEqual($selected_plugin->getPluginId(), 'plugin_test_helper_configurable_plugin');
    $this->assertEqual($selected_plugin->getConfiguration(), [
      'foo' => $foo,
    ]);
  }
}

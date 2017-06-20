<?php

namespace Drupal\plugin\Tests;

use Drupal\system\Tests\Update\UpdatePathTestBase;

/**
 * Tests hook_update_N() implementations.
 *
 * @group Plugin
 */
class HookUpdateNTest extends UpdatePathTestBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->configFactory = $this->container->get('config.factory');
  }

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      realpath(DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-8.bare.standard.php.gz'),
      realpath(__DIR__ . '/../../tests/fixtures/module_installation/database_dump.php'),
      realpath(__DIR__ . '/../../tests/fixtures/plugin_update_8001/database_dump.php'),
    ];
  }

  /**
   * Tests plugin_update_8001().
   *
   * @see plugin_update_8001()
   */
  public function testPluginUpdate8001() {
    $this->runUpdates();

    // Test the integrity of the plugin selector fields.
    /** @var string[] $fields Keys are config names, and values are matching plugin configuration schema IDs */
    $fields = [
      'field.field.user.user.field_plugin_selector' => 'plugin.plugin_configuration.plugin_selector.plugin_select_list',
      'field.field.user.user.field_plugin_test_helper_mock' => 'plugin_test_helper.plugin_configuration.plugin_test_helper_mock.plugin_test_helper_configurable_plugin',
    ];
    foreach ($fields as $config_name => $plugin_config_schema_id) {
      $config = $this->configFactory->get($config_name)->get();
      foreach ($config['default_value'] as $default_value) {
        // Confirm that the "plugin_type_id" property has been removed.
        $this->assertFalse(array_key_exists('plugin_type_id', $default_value));
        // Confirm that the "plugin_configuration_schema_id" property exists and
        // has been populated.
        $this->assertTrue(array_key_exists('plugin_configuration_schema_id', $default_value));
        $this->assertEqual($default_value['plugin_configuration_schema_id'], $plugin_config_schema_id);
      }
    }
  }

}

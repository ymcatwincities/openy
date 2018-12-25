<?php

namespace Drupal\Tests\plugin\Kernel\PluginType;

use Drupal\KernelTests\KernelTestBase;
use Drupal\plugin\PluginType\PluginTypeInterface;

/**
 * @coversDefaultClass \Drupal\plugin\PluginType\PluginTypeManager
 *
 * @group Plugin
 */
class PluginTypeManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'plugin'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Get any directory within Drupal core.
    $class_reflector = new \ReflectionClass(KernelTestBase::class);
    $directory = $class_reflector->getFileName();

    // Find Drupal core's module directory.
    while ($directory = dirname($directory)) {
      $core_module_directory = $directory . '/core/modules';
      if (file_exists($core_module_directory)) {
        break;
      }
    }

    // Find the names of Drupal core modules.
    $module_directories = array_keys(iterator_to_array(new \FilesystemIterator($core_module_directory)));
    $module_names = array_map(function($module_directory) {
      return basename($module_directory);
    }, $module_directories);

    // Set all Drupal core modules to be enabled for this test.
    static::$modules = array_merge(static::$modules, $module_names);

    parent::setUp();
  }

  /**
   * @covers ::getPluginTypes
   */
  public function testGetPluginTypes() {
    /** @var \Drupal\plugin\PluginType\PluginTypeManager $plugin_type_manager */
    $plugin_type_manager = $this->container->get('plugin.plugin_type_manager');
    $plugin_types = $plugin_type_manager->getPluginTypes();
    $this->assertNotEmpty($plugin_types);
    foreach ($plugin_types as $plugin_type) {
      $this->assertInstanceOf(PluginTypeInterface::class, $plugin_type);
    }
  }

}

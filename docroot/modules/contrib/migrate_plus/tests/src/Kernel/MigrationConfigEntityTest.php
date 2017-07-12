<?php

namespace Drupal\Tests\migrate_plus\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate_plus\Entity\Migration;

/**
 * Test migration config entity discovery.
 *
 * @group migrate_plus
 */
class MigrationConfigEntityTest extends KernelTestBase {

  public static $modules = ['migrate', 'migrate_plus'];

  /**
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $pluginManager;

  protected function setUp() {
    parent::setUp();
    $this->pluginManager = \Drupal::service('plugin.manager.migration');
  }

  public function testCacheInvalidation() {
    $config = Migration::create([
      'id' => 'test',
      'label' => 'Label A',
      'migration_tags' => [],
      'source' => [],
      'destination' => [],
      'migration_dependencies' => [],
    ]);
    $config->save();

    $this->assertTrue($this->pluginManager->getDefinition('test'));
    $this->assertSame('Label A', $this->pluginManager->getDefinition('test')['label']);

    // Clear static cache in the plugin manager, the cache tag take care of the
    // persistent cache.
    $this->pluginManager->useCaches(FALSE);
    $this->pluginManager->useCaches(TRUE);

    $config->set('label', 'Label B');
    $config->save();

    $this->assertSame('Label B', $this->pluginManager->getDefinition('test')['label']);
  }

}

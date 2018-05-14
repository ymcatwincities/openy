<?php

namespace Drupal\Tests\migrate_plus\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate_plus\Entity\Migration;
use Drupal\migrate_plus\Plugin\MigrationConfigEntityPluginManager;

/**
 * Test migration config entity discovery.
 *
 * @group migrate_plus
 */
class MigrationConfigEntityTest extends KernelTestBase {

  public static $modules = ['migrate', 'migrate_plus'];

  /**
   * @var MigrationConfigEntityPluginManager
   */
  protected $pluginMananger;

  protected function setUp() {
    parent::setUp();
    $this->pluginMananger = \Drupal::service('plugin.manager.config_entity_migration');
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

    $this->assertTrue($this->pluginMananger->getDefinition('test'));
    $this->assertSame('Label A', $this->pluginMananger->getDefinition('test')['label']);

    // Clear static cache in the plugin manager, the cache tag take care of the
    // persistent cache.
    $this->pluginMananger->useCaches(FALSE);
    $this->pluginMananger->useCaches(TRUE);

    $config->set('label', 'Label B');
    $config->save();

    $this->assertSame('Label B', $this->pluginMananger->getDefinition('test')['label']);
    $this->assertSame('Label B', \Drupal::service('plugin.manager.migration')->getDefinition('test')['label']);
  }

}

<?php

namespace Drupal\Tests\address\Kernel\Plugin\migrate\field;

use Drupal\address\Plugin\migrate\field\AddressField;
use Drupal\address\Plugin\migrate\cckfield\AddressField as CckAddressField;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Tests the address field field plugin.
 *
 * @group address
 */
class AddressFieldTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'address',
    'migrate',
    'migrate_drupal',
  ];

  /**
   * Tests discovery of the field plugin.
   */
  public function testPlugin() {
    $migration = $this->prophesize(MigrationInterface::class)->reveal();
    $field_plugin_manager = $this->container->get('plugin.manager.migrate.field');
    $definition = $field_plugin_manager->getDefinition('addressfield');
    $this->assertNotEmpty($definition);
    $field_plugin = $field_plugin_manager->createInstance('addressfield', [], $migration);
    $this->assertInstanceOf(AddressField::class, $field_plugin);
  }

  /**
   * Tests discovery of the cck field plugin.
   */
  public function testLegacyPlugin() {
    $migration = $this->prophesize(MigrationInterface::class)->reveal();
    $cck_plugin_manager = $this->container->get('plugin.manager.migrate.cckfield');
    $definition = $cck_plugin_manager->getDefinition('addressfield');
    $this->assertNotEmpty($definition);
    $cck_plugin = $cck_plugin_manager->createInstance('addressfield', [], $migration);
    $this->assertInstanceOf(CckAddressField::class, $cck_plugin);
  }

}

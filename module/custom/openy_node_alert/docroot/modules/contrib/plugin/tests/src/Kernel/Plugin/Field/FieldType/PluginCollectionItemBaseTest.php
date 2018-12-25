<?php

namespace Drupal\plugin\Tests\Plugin\Field\FieldType;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\KernelTests\KernelTestBase;
use Drupal\plugin_test_helper\Plugin\PluginTestHelper\MockConfigurablePlugin;
use Drupal\plugin_test_helper\Plugin\PluginTestHelper\MockManager;

/**
 * @coversDefaultClass \Drupal\plugin\Plugin\Field\FieldType\PluginCollectionItemBase
 *
 * @group Plugin
 */
class PluginCollectionItemBaseTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['plugin', 'plugin_test_helper', 'plugin_test'];

  /**
   * The system under test.
   *
   * @var \Drupal\plugin\Plugin\Field\FieldType\PluginCollectionItemBase
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $field_definition = BaseFieldDefinition::create('plugin:plugin_test_helper_mock');

    /** @var \Drupal\Core\Field\FieldItemListInterface $field_item_list */
    $field_item_list = \Drupal::typedDataManager()->create($field_definition);
    $field_item_list->appendItem();

    $this->sut = $field_item_list->first();
  }

  /**
   * @covers ::getContainedPluginId
   * @covers ::getContainedPluginConfiguration
   * @covers ::getContainedPluginInstance
   * @covers ::setContainedPluginId
   * @covers ::setContainedPluginConfiguration
   * @covers ::setContainedPluginInstance
   * @covers ::setValue
   */
  public function testField() {
    $plugin_id = 'plugin_test_helper_plugin';
    $plugin_id_configurable = 'plugin_test_helper_configurable_plugin';
    $plugin_configuration = [
      'foo' => $this->randomMachineName()
    ];

    // Test default values.
    $this->assertEquals('', $this->sut->getContainedPluginId());
    $this->assertEquals([], $this->sut->getContainedPluginConfiguration());
    $this->assertNull($this->sut->getContainedPluginInstance());

    // Test setting values and auto-instantiation for a non-configurable plugin.
    $this->sut->setContainedPluginId($plugin_id);
    $this->assertEquals($plugin_id, $this->sut->getContainedPluginId());
    $this->sut->setContainedPluginConfiguration($plugin_configuration);
    $this->assertEquals([], $this->sut->getContainedPluginConfiguration());
    $this->assertEquals($plugin_id, $this->sut->getContainedPluginInstance()->getPluginId());

    // Test setting values and auto-instantiation for a configurable plugin.
    $this->sut->setContainedPluginId($plugin_id_configurable);
    $this->assertEquals($plugin_id_configurable, $this->sut->getContainedPluginId());
    $this->sut->setContainedPluginConfiguration($plugin_configuration);
    $this->assertEquals($plugin_configuration, $this->sut->getContainedPluginConfiguration());
    $this->assertEquals($plugin_id_configurable, $this->sut->getContainedPluginInstance()->getPluginId());
    /** @var \Drupal\plugin_test_helper\Plugin\PluginTestHelper\MockConfigurablePlugin $plugin_instance_a */
    $plugin_instance_a = $this->sut->getContainedPluginInstance();
    $this->assertTrue($plugin_instance_a instanceof MockConfigurablePlugin);
    $this->assertEquals($plugin_configuration, $plugin_instance_a->getConfiguration());
    $altered_plugin_configuration = $plugin_configuration += [
      'bar' => $this->randomMachineName(),
    ];
    $plugin_instance_a->setConfiguration($altered_plugin_configuration);
    $this->assertEquals($altered_plugin_configuration, $plugin_instance_a->getConfiguration());
    $this->assertEquals($altered_plugin_configuration, $this->sut->getContainedPluginConfiguration());

    // Test resetting the values.
    $this->sut->applyDefaultValue();
    $this->assertEquals('', $this->sut->getContainedPluginId());
    $this->assertEquals([], $this->sut->getContainedPluginConfiguration());
    $this->assertNull($this->sut->getContainedPluginInstance());

    // Test setting values again and auto-instantiation.
    $this->sut->applyDefaultValue();
    $this->sut->setContainedPluginId($plugin_id_configurable);
    $this->assertEquals($plugin_id_configurable, $this->sut->getContainedPluginId());
    $this->sut->setContainedPluginConfiguration($plugin_configuration);
    $this->assertEquals($plugin_configuration, $this->sut->getContainedPluginConfiguration());
    /** @var \Drupal\plugin_test_helper\Plugin\PluginTestHelper\MockConfigurablePlugin $plugin_instance_b */
    $plugin_instance_b = $this->sut->getContainedPluginInstance();
    $this->assertTrue($plugin_instance_b instanceof MockConfigurablePlugin);
    $this->assertEquals($plugin_configuration, $plugin_instance_b->getConfiguration());
    // Make sure this is indeed a new instance and not the old one.
    $this->assertNotSame($plugin_instance_a, $plugin_instance_b);
    // Make sure changing the configuration on the new instance changes the
    // configuration in the field item.
    $altered_plugin_configuration_a = $plugin_configuration + [
      'bar' => $this->randomMachineName(),
    ];
    $altered_plugin_configuration_b = $plugin_configuration + [
      'baz' => $this->randomMachineName(),
    ];
    $plugin_instance_b->setConfiguration($altered_plugin_configuration_b);
    $this->assertEquals($altered_plugin_configuration_b, $this->sut->getContainedPluginConfiguration());
    // Make sure changing the configuration on the old instance no longer has
    // any effect on the field item.
    $plugin_instance_a->setConfiguration($altered_plugin_configuration_a);
    $this->assertEquals($altered_plugin_configuration_b, $this->sut->getContainedPluginConfiguration());

    // Test feedback from the plugin back to the field item.
    $plugin_manager = new MockManager();
    /** @var \Drupal\plugin_test_helper\Plugin\PluginTestHelper\MockConfigurablePlugin $plugin_instance_c */
    $plugin_configuration_c = $plugin_configuration + [
        'qux' => $this->randomMachineName(),
      ];
    $plugin_instance_c = $plugin_manager->createInstance($plugin_id_configurable, $plugin_configuration_c);
    $this->sut->setContainedPluginInstance($plugin_instance_c);
    $this->assertEquals($plugin_instance_c, $this->sut->getContainedPluginInstance());
    $this->assertEquals($plugin_configuration_c, $this->sut->getContainedPluginConfiguration());
    $altered_plugin_configuration_c = $plugin_configuration_c + [
        'foobar' => $this->randomMachineName(),
      ];
    $plugin_instance_c->setConfiguration($altered_plugin_configuration_c);
    $this->assertEquals($altered_plugin_configuration_c, $this->sut->getContainedPluginConfiguration());

    // Test setting the main property.
    /** @var \Drupal\plugin_test_helper\Plugin\PluginTestHelper\MockConfigurablePlugin $plugin_instance_d */
    $plugin_instance_d = $plugin_manager->createInstance($plugin_id_configurable);
    $plugin_instance_d->setConfiguration([
      'oman' => '42',
    ]);
    $this->sut->setValue($plugin_instance_d);
    $this->assertEquals($plugin_instance_d, $this->sut->getContainedPluginInstance());
  }

}

<?php

/**
 * @file
 * Contains \Drupal\Tests\config_devel\ConfigDevelAutoExportSubscriberTest.
 */

namespace Drupal\Tests\config_devel;

use org\bovigo\vfs\vfsStream;
use Drupal\Component\Serialization\Yaml;

use Drupal\config_devel\EventSubscriber\ConfigDevelAutoExportSubscriber;

/**
 * @coversDefaultClass \Drupal\config_devel\EventSubscriber\ConfigDevelAutoExportSubscriber
 * @group config_devel
 */
class ConfigDevelAutoExportSubscriberTest extends ConfigDevelTestBase {

  /**
   * Test ConfigDevelAutoExportSubscriber::writeBackConfig().
   */
  public function testWriteBackConfig() {
    $config_data = array(
      'id' => $this->randomMachineName(),
      'langcode' => 'en',
      'uuid' => '836769f4-6791-402d-9046-cc06e20be87f',
    );

    $config = $this->getMockBuilder('\Drupal\Core\Config\Config')
      ->disableOriginalConstructor()
      ->getMock();
    $config->expects($this->any())
      ->method('getName')
      ->will($this->returnValue($this->randomMachineName()));
    $config->expects($this->any())
      ->method('get')
      ->will($this->returnValue($config_data));

    $file_names = array(
      vfsStream::url('public://' . $this->randomMachineName() . '.yml'),
      vfsStream::url('public://' . $this->randomMachineName() . '.yml'),
    );

    $configDevelSubscriber = new ConfigDevelAutoExportSubscriber($this->configFactory, $this->configManager);
    $configDevelSubscriber->writeBackConfig($config, $file_names);

    $data = $config_data;
    unset($data['uuid']);

    foreach ($file_names as $file_name) {
      $this->assertEquals($data, Yaml::decode(file_get_contents($file_name)));
    }
  }

}

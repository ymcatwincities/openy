<?php

/**
 * @file
 * Contains \Drupal\config_devel\Tests\ConfigDevelAutoImportSubscriberTestBase.
 */

namespace Drupal\config_devel\Tests;

use Drupal\Component\Serialization\Yaml;
use Drupal\simpletest\KernelTestBase;
use org\bovigo\vfs\vfsStream;

abstract class ConfigDevelSubscriberTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('config_devel');

  /**
   * Name of the config object.
   */
  const CONFIGNAME = '';

  /**
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $storage;

  /**
   * set up test environment
   */
  public function setUp() {
    vfsStream::setup('public://');
    parent::setUp();
  }

  /**
   * Test the import subscriber.
   */
  public function testSubscribers() {
    // Without this the config exporter breaks.
    \Drupal::service('config.installer')->installDefaultConfig('module', 'config_devel');
    $filename = vfsStream::url('public://'. static::CONFIGNAME . '.yml');
    drupal_mkdir(vfsStream::url('public://exported'));
    $exported_filename = vfsStream::url('public://exported/' . static::CONFIGNAME . '.yml');
    \Drupal::configFactory()->getEditable('config_devel.settings')
      ->set('auto_import', array(array(
        'filename' => $filename,
        'hash' => '',
      )))
      ->set('auto_export', array(
        $exported_filename,
      ))
      ->save();
    $this->storage = \Drupal::service('config.storage');
    $this->assertFalse($this->storage->exists(static::CONFIGNAME));
    $subscriber = \Drupal::service('config_devel.auto_import_subscriber');
    for ($i = 2; $i; $i--) {
      $data['label'] = $this->randomString();
      file_put_contents($filename, Yaml::encode($data));
      // The import fires an export too.
      $subscriber->autoImportConfig();
      $this->doAssert($data, Yaml::decode(file_get_contents($exported_filename)));
    }
  }

  /**
   * Assert that the config import succeeded.
   *
   * @param array $writen_data
   *   The config data as written.
   * @param array $exported_data
   *   The config data exported.
   */
  abstract protected function doAssert(array $data, array $exported_data);

}

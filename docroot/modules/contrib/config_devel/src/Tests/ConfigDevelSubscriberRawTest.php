<?php

/**
 * @file
 * Contains \Drupal\config_devel\Tests\ConfigDevelSubscriberRawTest.
 */

namespace Drupal\config_devel\Tests;

/**
 * Tests the automated importer for raw config objects.
 *
 * @group config
 */
class ConfigDevelSubscriberRawTest extends ConfigDevelSubscriberTestBase {

  /**
   * {@inheritdoc}
   */
  const CONFIGNAME = 'config_devel.test';

  /**
   * {@inheritdoc}
   */
  protected function doAssert(array $data, array $exported_data) {
    $this->assertIdentical($data, $this->storage->read(static::CONFIGNAME));
    $this->assertIdentical($data, $exported_data);
  }

}

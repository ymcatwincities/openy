<?php

/**
 * @file
 * Contains \Drupal\config_devel\Tests\ConfigDevelSubscriberEntityTest.
 */


namespace Drupal\config_devel\Tests;

/**
 * Tests the automated importer for config entities.
 *
 * @group config
 */
class ConfigDevelSubscriberEntityTest extends ConfigDevelSubscriberTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('config_test');

  /**
   * {@inheritdoc}
   */
  const CONFIGNAME = 'config_test.dynamic.test';

  /**
   * {@inheritdoc}
   */
  protected function doAssert(array $data, array $exported_data) {
    $entity = entity_load('config_test', 'test', TRUE);
    $this->assertIdentical($data['label'], $entity->get('label'));
    $this->assertIdentical($exported_data['label'], $data['label']);
    $this->assertIdentical($exported_data['id'], 'test');
    $this->assertFalse(isset($exported_data['uuid']));
  }
}

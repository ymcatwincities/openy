<?php

namespace Drupal\Tests\lazyloader\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the config schema.
 *
 * @group lazyloader
 */
class ConfigSchemaTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['lazyloader'];

  public function testConfig() {
    $this->installConfig('lazyloader');
  }

}

<?php

namespace Drupal\Tests\config_import\Unit;

// Testing dependencies.
use Drupal\KernelTests\KernelTestBase;

/**
 * Testing features importer service.
 *
 * @group confi
 */
class ConfigFeaturesImporterServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;
  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_import', 'system'];

  /**
   * Service must not be available when "features" module disabled.
   */
  public function testServiceUnavailabilityWhenRequiredModulesMissing() {
    static::assertFalse($this->container->has('config_import.features_importer'));
  }

  /**
   * Service must be available when "features" module installed.
   */
  public function testServiceAvailabilityWhenRequiredModulesPresented() {
    $this->container->get('module_installer')->install(['features']);

    static::assertTrue($this->container->has('config_import.features_importer'));
  }

  // @todo Add tests for features importing/revering.

}

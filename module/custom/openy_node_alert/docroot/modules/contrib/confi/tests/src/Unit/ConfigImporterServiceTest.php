<?php

namespace Drupal\Tests\config_import\Unit;

// Core components.
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\ConfigImporterException;

/**
 * Testing configuration importer service.
 *
 * @group confi
 */
class ConfigImporterServiceTest extends ConfigImporterServiceTestBase {

  /**
   * Check that default directory configured to "sync".
   */
  public function testGetDirectory() {
    static::assertSame(config_get_config_directory(CONFIG_SYNC_DIRECTORY), $this->configImporter->getDirectory());
  }

  /**
   * Check that we unable to set whatever we want.
   *
   * @param string $type
   *   Type of configuration directory.
   */
  public function testSetDirectoryWrongType($type = 'something') {
    $this->expectException(\Exception::class, "The configuration directory type '%s' does not exist", $type);
    $this->configImporter->setDirectory($type);
  }

  /**
   * Check that configuration directory physically exists.
   *
   * @param string $type
   *   Type of configuration directory.
   */
  public function testSetDirectoryNotExists($type = 'something') {
    $GLOBALS['config_directories'][$type] = "/path/to/$type/not-exists";

    $this->expectException(\InvalidArgumentException::class, '%s - is not valid path or type of directory with configurations\.', $GLOBALS['config_directories'][$type]);
    $this->configImporter->setDirectory($type);
  }

  /**
   * Check that configuration will or will not be filtered.
   *
   * @param bool $should_be_filtered
   *   Indicates that configuration must or must not be filtered.
   *
   * @dataProvider providerFilter
   *
   * @covers \Drupal\config_import\ConfigImporterService::export
   * @covers \Drupal\config_import\ConfigImporterService::filter
   */
  public function testFilter($should_be_filtered) {
    if ($should_be_filtered) {
      // The next module contains an alteration hook which will
      // deny importing of configuration.
      $this->enableModules(['config_import_test']);
    }

    // Construct intermediate files storage.
    $tmp_storage = new FileStorage("$this->siteDirectory/confi");

    // Initially we will export all configurations from active storage (it's
    // database usually) into temporary files storage. Then the "filter" method
    // will try to clean temporary storage and deny importing of some configs.
    // WARNING: an order of executions must not be changed!
    foreach (['export', 'filter'] as $method) {
      static::assertNull($this->invokeMethod($method, $tmp_storage));
    }

    static::assertSame(!$should_be_filtered, $tmp_storage->exists(static::TEST_CONFIG));
  }

  /**
   * Check that configuration will be properly imported.
   *
   * @covers \Drupal\config_import\ConfigImporterService::import
   */
  public function testImportConfigs() {
    // Get an active configurations storage.
    $config_storage = $this->getConfigStorage();
    // Remember the value before import which potentially can overwrite it.
    $original_value = $config_storage->read(static::TEST_CONFIG);
    // Set the directory to import configurations from.
    $this->configImporter->setDirectory(drupal_get_path('module', 'config_import') . '/tests/config/import');
    // Doing the import.
    $this->configImporter->importConfigs([static::TEST_CONFIG]);

    static::assertSame($original_value, $config_storage->read(static::TEST_CONFIG));
  }

  /**
   * Check that filtered configuration will not be imported.
   */
  public function testImportConfigsFiltered() {
    // Enable the module which disallows import of some configurations.
    $this->enableModules(['config_import_test']);
    // Configuration will be removed from intermediate storage by
    // filtering  and will no longer available for import.
    $this->expectException(ConfigImporterException::class, 'The %s configuration does not exist\.', static::TEST_CONFIG);
    // Do an import and wait for an exception.
    $this->testImportConfigs();
  }

  /**
   * Check that configuration will be imported in maintenance mode.
   *
   * @param string $maintenance_mode
   *   One of available maintenance modes.
   *
   * @dataProvider providerMaintenance
   */
  public function testImportConfigsInMaintenanceMode($maintenance_mode) {
    define('MAINTENANCE_MODE', $maintenance_mode);

    $this->testImportConfigs();
  }

  /**
   * Check that configuration will be exported.
   */
  public function testExportConfigs() {
    $this->configImporter->setDirectory($this->siteDirectory);
    $this->configImporter->exportConfigs([static::TEST_CONFIG]);

    static::assertFileExists(sprintf("$this->siteDirectory/%s.yml", static::TEST_CONFIG));
  }

  /**
   * Data provider.
   *
   * @return array[]
   *   Sets of arguments.
   */
  public function providerMaintenance() {
    return [['update'], ['install']];
  }

  /**
   * Data provider.
   *
   * @return array[]
   *   Sets of arguments.
   */
  public function providerFilter() {
    return [[TRUE], [FALSE]];
  }

}

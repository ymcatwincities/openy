<?php

namespace Drupal\Tests\config_import\Unit;

// Testing dependencies.
use Drupal\KernelTests\KernelTestBase;
// Core components.
use Drupal\Core\Config\StorageInterface;

/**
 * Base abstraction for testing the configuration importer service.
 */
abstract class ConfigImporterServiceTestBase extends KernelTestBase {

  /**
   * Name of config for experiments.
   */
  const TEST_CONFIG = 'core.extension';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_import', 'system'];
  /**
   * Config Importer.
   *
   * @var \Drupal\config_import\ConfigImporterService
   */
  protected $configImporter;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->configImporter = $this->container->get('config_import.importer');
  }

  /**
   * Set expected exception with a regular expression for a message.
   *
   * @param string $class
   *   FQDN of exception class.
   * @param string $regex
   *   Full regular expression for exception message. Placeholders, same as
   *   in "sprintf()" function, are available.
   * @param string[] ...$arguments
   *   List of items to replace placeholders. Each of them will be processed
   *   by "preg_quote()" function.
   */
  protected function expectException($class, $regex, ...$arguments) {
    foreach ($arguments as $i => $argument) {
      $arguments[$i] = preg_quote($argument, '/');
    }

    $this->setExpectedExceptionRegExp($class, sprintf('/^%s$/', vsprintf($regex, $arguments)));
  }

  /**
   * Call protected methods of config importing service.
   *
   * @param string $method
   *   Name of method.
   * @param mixed[] $arguments
   *   Any set of arguments for method.
   *
   * @return mixed
   *   A value, returned by called method.
   */
  protected function invokeMethod($method, ...$arguments) {
    $method = new \ReflectionMethod($this->configImporter, $method);
    $method->setAccessible(TRUE);

    return $method->invokeArgs($this->configImporter, $arguments);
  }

  /**
   * Get current configuration storage.
   *
   * @return StorageInterface
   *   Active storage of configurations.
   */
  protected function getConfigStorage() {
    return $this->getObjectAttribute($this->configImporter, 'configStorage');
  }

}

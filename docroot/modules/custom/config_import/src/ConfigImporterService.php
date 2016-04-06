<?php

namespace Drupal\config_import;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\FileStorage;
use Exception;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\ConfigImporter;

/**
 * Implements Config Importer Service.
 */
class ConfigImporterService {

  /**
   * The config manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ConfigImporterService.
   *
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The config manager.
   */
  public function __construct(ConfigManagerInterface $config_manager, ConfigFactoryInterface $config_factory) {
    $this->configManager = $config_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * Import config.
   *
   * @param array $files
   *   Array of strings, each item is a path to config file.
   */
  public function importConfigs(array $files) {
    // @todo The next string doesn't work during installation. Hardcode it.
    // $uri = 'temporary://confi_tmp_' . \Drupal::service('uuid')->generate();
    $tmp_dir = '/tmp/confi_tmp_' . \Drupal::service('uuid')->generate();

    try {
      $this->export($tmp_dir);
    }
    catch (Exception $e) {
      watchdog_exception('config_import', $e);
      return;
    }

    foreach ($files as $source) {
      file_unmanaged_copy($source, $tmp_dir, FILE_EXISTS_REPLACE);
    }

    $this->import($tmp_dir);
  }

  public function import($dir) {
    $active_storage = \Drupal::service('config.storage');
    $source_storage = new FileStorage($dir);
    $config_manager = \Drupal::service('config.manager');
    $storage_comparer = new StorageComparer($source_storage, $active_storage, $config_manager);

    if (!$storage_comparer->createChangelist()->hasChanges()) {
      return;
    }

    $config_importer = new ConfigImporter(
      $storage_comparer,
      \Drupal::service('event_dispatcher'),
      \Drupal::service('config.manager'),
      \Drupal::lock(),
      \Drupal::service('config.typed'),
      \Drupal::moduleHandler(),
      \Drupal::service('module_installer'),
      \Drupal::service('theme_handler'),
      \Drupal::service('string_translation')
    );


    try {
      $config_importer->import();
    }
    catch (Exception $e) {
      watchdog_exception('config_import', $e);
    }
  }

  /**
   * Export active configuration to temporary directory.
   *
   * @param string $dir
   *   Uri of a temporary directory.
   *
   * @throws Exception
   *   When fails to create a directory.
   */
  public function export($dir) {
    $result = \Drupal::service('file_system')->mkdir($dir);
    if (!$result) {
      throw new \Exception('Failed to create temporary directory: ' . $dir);
    }
    $source_storage = \Drupal::service('config.storage');
    $destination_storage = new FileStorage($dir);

    $filters = [
      'devel.settings',
      'config_devel.settings',
    ];

    foreach ($source_storage->listAll() as $name) {
      if (in_array($name, $filters)) {
        continue;
      }
      $destination_storage->write($name, $source_storage->read($name));
    }
  }

}

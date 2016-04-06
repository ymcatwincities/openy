<?php

namespace Drupal\config_import;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\CachedStorage;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Config\TypedConfigManager;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\ProxyClass\Extension\ModuleInstaller;
use Drupal\Core\Extension\ThemeHandler;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Config\ConfigException;
use Exception;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\ConfigImporter;

/**
 * Class ConfigImporterService.
 *
 * @package Drupal\config_import
 */
class ConfigImporterService implements ConfigImporterServiceInterface {

  /**
   * Uuid definition.
   *
   * @var UuidInterface
   */
  protected $uuid;

  /**
   * CachedStorage definition.
   *
   * @var CachedStorage
   */
  protected $configStorage;

  /**
   * ConfigManager definition.
   *
   * @var ConfigManagerInterface
   */
  protected $configManager;

  /**
   * ContainerAwareEventDispatcher definition.
   *
   * @var ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * LockBackend definition.
   *
   * @var LockBackendInterface
   */
  protected $lock;

  /**
   * TypedConfigManager definition.
   *
   * @var TypedConfigManager
   */
  protected $configTyped;

  /**
   * ModuleHandler definition.
   *
   * @var ModuleHandler
   */
  protected $moduleHandler;

  /**
   * ModuleInstaller definition.
   *
   * @var ModuleInstaller
   */
  protected $moduleInstaller;

  /**
   * ThemeHandler definition.
   *
   * @var ThemeHandler
   */
  protected $themeHandler;

  /**
   * TranslationManager definition.
   *
   * @var TranslationManager
   */
  protected $translationManager;

  /**
   * FileSystem definition.
   *
   * @var FileSystem
   */
  protected $fileSystem;

  /**
   * ConfigImporterService constructor.
   *
   * @param UuidInterface $uuid
   *   Uuid.
   * @param CachedStorage $config_storage
   *   CachedStorage.
   * @param ConfigManagerInterface $config_manager
   *   ConfigManager.
   * @param ContainerAwareEventDispatcher $event_dispatcher
   *   ContainerAwareEventDispatcher.
   * @param LockBackendInterface $lock
   *   LockBackend.
   * @param TypedConfigManager $config_typed
   *   TypedConfigManager.
   * @param ModuleHandler $module_handler
   *   ModuleHandler.
   * @param ModuleInstaller $module_installer
   *   ModuleInstaller.
   * @param ThemeHandler $theme_handler
   *   ThemeHandler.
   * @param TranslationManager $translation_manager
   *   TranslationManager.
   * @param FileSystem $file_system
   *   FileSystem.
   */
  public function __construct(UuidInterface $uuid, CachedStorage $config_storage, ConfigManagerInterface $config_manager, ContainerAwareEventDispatcher $event_dispatcher, LockBackendInterface $lock, TypedConfigManager $config_typed, ModuleHandler $module_handler, ModuleInstaller $module_installer, ThemeHandler $theme_handler, TranslationManager $translation_manager, FileSystem $file_system) {
    $this->uuid = $uuid;
    $this->configStorage = $config_storage;
    $this->configManager = $config_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->lock = $lock;
    $this->configTyped = $config_typed;
    $this->moduleHandler = $module_handler;
    $this->moduleInstaller = $module_installer;
    $this->themeHandler = $theme_handler;
    $this->translationManager = $translation_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public function importConfigs(array $files) {
    // @todo The next string doesn't work during installation. Hardcode it.
    // $uri = 'temporary://confi_tmp_' . $this->uuid->->generate();
    $tmp_dir = '/tmp/confi_tmp_' . $this->uuid->generate();

    try {
      $this->export($tmp_dir);
    }
    catch (Exception $e) {
      throw new ConfigImporterException($e->getMessage());
    }

    foreach ($files as $source) {
      file_unmanaged_copy($source, $tmp_dir, FILE_EXISTS_REPLACE);
    }

    $this->import($tmp_dir);
  }

  /**
   * Import config from temporary directory.
   *
   * @param string $dir
   *   Temporary directory.
   */
  protected function import($dir) {
    $source_storage = new FileStorage($dir);
    $storage_comparer = new StorageComparer($source_storage, $this->configStorage, $this->configManager);

    if (!$storage_comparer->createChangelist()->hasChanges()) {
      return;
    }

    $config_importer = new ConfigImporter(
      $storage_comparer,
      $this->eventDispatcher,
      $this->configManager,
      $this->lock,
      $this->configTyped,
      $this->moduleHandler,
      $this->moduleInstaller,
      $this->themeHandler,
      $this->translationManager
    );

    try {
      $config_importer->import();
    }
    catch (ConfigException $e) {
      $message = 'The import failed due for the following reasons:' . "\n";
      $message .= implode("\n", $config_importer->getErrors());
      throw new ConfigImporterException($message);
    }
  }

  /**
   * Export configuration to temporary directory.
   *
   * @param string $dir
   *   Path to directory.
   *
   * @throws \Exception
   *   When fails to create temporary directory.
   */
  protected function export($dir) {
    $result = $this->fileSystem->mkdir($dir);
    if (!$result) {
      throw new \Exception('Failed to create temporary directory: ' . $dir);
    }
    $source_storage = $this->configStorage;
    $destination_storage = new FileStorage($dir);

    $filters = [];
    $this->moduleHandler->alter('config_import_configs', $filters);

    foreach ($source_storage->listAll() as $name) {
      if (in_array($name, $filters)) {
        continue;
      }
      $destination_storage->write($name, $source_storage->read($name));
    }
  }

}

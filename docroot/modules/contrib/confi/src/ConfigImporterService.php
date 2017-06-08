<?php

namespace Drupal\config_import;

use Drupal\Core\Config\CachedStorage;
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
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ConfigImporterService.
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
   * Path to directory where configs located.
   *
   * @var string
   */
  protected $directory = '';
  /**
   * Configuration of FileCacheFactory.
   *
   * @var array
   */
  private $fileCacheConfig = [];

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
  public function __construct(
    UuidInterface $uuid,
    CachedStorage $config_storage,
    ConfigManagerInterface $config_manager,
    ContainerAwareEventDispatcher $event_dispatcher,
    LockBackendInterface $lock,
    TypedConfigManager $config_typed,
    ModuleHandler $module_handler,
    ModuleInstaller $module_installer,
    ThemeHandler $theme_handler,
    TranslationManager $translation_manager,
    FileSystem $file_system
  ) {
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

    // Save current configuration to disable file cache for a while
    // and restore afterwards.
    $this->fileCacheConfig = FileCacheFactory::getConfiguration();
    // @see https://www.drupal.org/node/2758325
    FileCacheFactory::setConfiguration([FileCacheFactory::DISABLE_CACHE => TRUE]);
    // Sync directory must be configured.
    $this->setDirectory(CONFIG_SYNC_DIRECTORY);
  }

  /**
   * Restore file cache configuration.
   */
  public function __destruct() {
    FileCacheFactory::setConfiguration($this->fileCacheConfig);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('uuid'),
      $container->get('config.storage'),
      $container->get('config.manager'),
      $container->get('event_dispatcher'),
      $container->get('lock'),
      $container->get('config.typed'),
      $container->get('module_handler'),
      $container->get('module_installer'),
      $container->get('theme_handler'),
      $container->get('string_translation'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setDirectory($directory) {
    if (!is_dir($directory)) {
      $directory = config_get_config_directory($directory);
    }

    if (!is_dir($directory)) {
      throw new \InvalidArgumentException($directory . ' - is not valid path or type of directory with configurations.');
    }

    $this->directory = $directory;
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectory() {
    return $this->directory;
  }

  /**
   * {@inheritdoc}
   */
  public function importConfigs(array $configs) {
    // Stream wrappers are not available during installation.
    $tmp_dir = (defined('MAINTENANCE_MODE') ? '/tmp' : 'temporary:/') . '/confi_' . $this->uuid->generate();

    if (!$this->fileSystem->mkdir($tmp_dir)) {
      throw new ConfigImporterException('Failed to create temporary directory: ' . $tmp_dir);
    }

    // Define temporary storage for our shenanigans.
    $tmp_storage = new FileStorage($tmp_dir);
    // Dump all configurations into temporary directory.
    $this->export($tmp_storage);

    // Overwrite exported configurations by our custom ones.
    foreach ($configs as $config) {
      $file = "$this->directory/$config.yml";

      if (file_exists($file)) {
        file_unmanaged_copy($file, $tmp_dir, FILE_EXISTS_REPLACE);
      }
      else {
        // Possibly, config has been exported a little bit above. This could
        // happen if you removed it from disc, but not from database. Export
        // operation will generate it inside of temporary storage and we should
        // take care about this.
        $tmp_storage->delete($config);
        // Remove config if it was specified, but file does not exists.
        $this->configStorage->delete($config);
      }
    }

    // Remove configurations from storage which are not allowed for import.
    $this->filter($tmp_storage);
    // Import changed, just overwritten items, into config storage.
    $this->import($tmp_storage);
  }

  /**
   * {@inheritdoc}
   */
  public function exportConfigs(array $configs) {
    $storage = new FileStorage($this->directory);

    foreach ($configs as $config) {
      $storage->write($config, $this->configStorage->read($config));
    }
  }

  /**
   * Clean storage.
   *
   * @param FileStorage $storage
   *   A storage to prepare.
   *
   * @see hook_config_import_configs_alter()
   */
  protected function filter(FileStorage $storage) {
    $configs = [];
    // Collect config names which are not allowed for import.
    $this->moduleHandler->alter('config_import_configs', $configs);

    foreach ($configs as $config) {
      $storage->delete($config);
    }
  }

  /**
   * Dump configurations to files storage.
   *
   * @param FileStorage $storage
   *   A storage to dump to.
   */
  protected function export(FileStorage $storage) {
    foreach ($this->configStorage->listAll() as $config) {
      $storage->write($config, $this->configStorage->read($config));
    }
  }

  /**
   * Import configurations from files storage.
   *
   * @param FileStorage $storage
   *   A storage to import from.
   */
  protected function import(FileStorage $storage) {
    $storage_comparer = new StorageComparer($storage, $this->configStorage, $this->configManager);

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
      $config_importer
        ->validate()
        ->import();
    }
    catch (ConfigException $e) {
      throw new ConfigImporterException(implode("\n", $config_importer->getErrors()));
    }
  }

}

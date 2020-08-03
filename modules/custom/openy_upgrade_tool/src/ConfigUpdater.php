<?php

namespace Drupal\openy_upgrade_tool;

use Drupal\Component\Serialization\Yaml;
use Drupal\config_import\ConfigImporterService;;
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
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Config\FileStorage;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\File\FileSystemInterface;

/**
 * Class ConfigUpdater.
 */
class ConfigUpdater extends ConfigImporterService {

  use StringTranslationTrait;

  /**
   * The OpenyUpgradeLogManager.
   *
   * @var \Drupal\openy_upgrade_tool\OpenyUpgradeLogManagerInterface
   */
  protected $upgradeLogManager;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
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
    FileSystem $file_system,
    OpenyUpgradeLogManagerInterface $upgrade_log_manager,
    LoggerChannelInterface $logger_factory,
    ModuleExtentionList $extension_list_module
  ) {
    parent::__construct(
      $uuid,
      $config_storage,
      $config_manager,
      $event_dispatcher,
      $lock,
      $config_typed,
      $module_handler,
      $module_installer,
      $theme_handler,
      $translation_manager,
      $file_system,
      $extension_list_module
    );
    $this->logger = $logger_factory;
    $this->upgradeLogManager = $upgrade_log_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function importConfigs(array $configs) {
    // Stream wrappers are not available during installation.
    $tmp_dir = (defined('MAINTENANCE_MODE') ? '/tmp' : 'temporary:/') . '/confi_' . $this->uuid->generate();
    // Notify ConfigEventSubscriber that this is update from OpenY.
    global $_openy_config_import_event;
    $_openy_config_import_event = TRUE;

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

      if ($this->upgradeLogManager->isManuallyChanged($config)) {
        // Skip config update and log this to logger entity.
        $this->logConfigImportError($file, $config);
        continue;
      }

      if (file_exists($file)) {
        \Drupal::service('file_system')->copy($file, $tmp_dir, FileSystemInterface::EXISTS_REPLACE);
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
    $_openy_config_import_event = FALSE;
  }

  /**
   * Simplified version of importConfigs.
   *
   * Main difference between this functions that in simple version we
   * skip export of all site configs to temp directory and just copy and import
   * only listed config for import. Also here was skipped configs filter logic.
   *
   * @param string $config
   *   Config name.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function importConfigSimple($config) {
    // Stream wrappers are not available during installation.
    $tmp_dir = (defined('MAINTENANCE_MODE') ? '/tmp' : 'temporary:/') . '/confi_simple' . $this->uuid->generate();
    // Notify ConfigEventSubscriber that this is update from OpenY.
    global $_openy_config_import_event;
    $_openy_config_import_event = TRUE;
    if (!$this->fileSystem->mkdir($tmp_dir)) {
      throw new ConfigImporterException('Failed to create temporary directory: ' . $tmp_dir);
    }
    $tmp_storage = new FileStorage($tmp_dir);
    $file = "$this->directory/$config.yml";
    if ($this->upgradeLogManager->isManuallyChanged($config)) {
      // Skip config update and log this to logger entity.
      $this->logConfigImportError($file, $config);
      $_openy_config_import_event = FALSE;
      return;
    }
    if (file_exists($file)) {
      \Drupal::service('file_system')->copy($file, $tmp_dir, FileSystemInterface::EXISTS_REPLACE);
      // Check if exist logger entity and enabled force mode.
      if ($this->upgradeLogManager->isForceMode() && $this->upgradeLogManager->isManuallyChanged($config, FALSE)) {
        // ConfigStorage->write not trigger config save event, so create
        // backup here.
        $this->upgradeLogManager->createBackup($config);
      }
      $this->configStorage->write($config, $tmp_storage->read($config));
    }
    $_openy_config_import_event = FALSE;
  }

  /**
   * Helper function for config import error log.
   *
   * @param string $file
   *   Full path to file including file name.
   * @param string $config
   *   Config name.
   */
  private function logConfigImportError($file, $config) {
    $config_data = Yaml::decode(file_get_contents($file));
    $message = $this->t('Failed attempt to update config "@name" from "@path" during Open Y update queue.', [
      '@path' => $file,
      '@name' => $config,
    ]);
    $this->upgradeLogManager->saveLoggerEntity($config, $config_data, $message);
    $dashboard_url = Url::fromRoute(OpenyUpgradeLogManager::DASHBOARD);
    $dashboard_link = Link::fromTextAndUrl(t('Open Y upgrade dashboard'), $dashboard_url);
    $this->logger->error($this->t('Could not update config @name. Please add this changes manual. More info here - @link.',
      [
        '@name' => $config,
        '@link' => $dashboard_link->toString(),
      ]
    ));
  }

}

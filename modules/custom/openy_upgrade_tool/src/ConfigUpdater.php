<?php

namespace Drupal\openy_upgrade_tool;

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
use Drupal\Core\Config\FileStorage;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Class ConfigUpdater.
 */
class ConfigUpdater extends ConfigImporterService {

  use StringTranslationTrait;

  /**
   * Entity type manger.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger Entity Storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $loggerEntityStorage;

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
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelInterface $logger_factory
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
      $file_system
    );
    $this->logger = $logger_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerEntityStorage = $this->entityTypeManager->getStorage('logger_entity');
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

      if ($this->isManuallyChanged($config)) {
        // Skip config update and log this to logger entity.
        $this->logConfigImportError($file, $config);
        continue;
      }

      if (file_exists($file)) {
        file_unmanaged_copy($file, $tmp_dir, FILE_EXISTS_REPLACE);
        // Add openy_upgrade param to config.
        file_put_contents($tmp_dir . "/$config.yml", 'openy_upgrade: true', FILE_APPEND);
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
   * Simplified version of importConfigs.
   *
   * Main difference between this functions that in simple version we
   * skip export of all site configs to temp directory and just copy and import
   * only listed config for import. Also here was skiped configs filter logic.
   *
   * @param string $config
   *   Config name.
   */
  public function importConfigSimple($config) {
    // Stream wrappers are not available during installation.
    $tmp_dir = (defined('MAINTENANCE_MODE') ? '/tmp' : 'temporary:/') . '/confi_simple' . $this->uuid->generate();
    if (!$this->fileSystem->mkdir($tmp_dir)) {
      throw new ConfigImporterException('Failed to create temporary directory: ' . $tmp_dir);
    }
    $tmp_storage = new FileStorage($tmp_dir);
    $file = "$this->directory/$config.yml";
    if ($this->isManuallyChanged($config)) {
      // Skip config update and log this to logger entity.
      $this->logConfigImportError($file, $config);
    }
    if (file_exists($file)) {
      file_unmanaged_copy($file, $tmp_dir, FILE_EXISTS_REPLACE);
      // Add openy_upgrade param to config.
      file_put_contents($tmp_dir . "/$config.yml", 'openy_upgrade: true', FILE_APPEND);
      $this->configStorage->write($config, $tmp_storage->read($config));
    }
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
    $this->updateLoggerEntity($file, $config);
    $dashboard_url = Url::fromRoute('view.openy_upgrade_dashboard.page_1');
    $dashboard_link = Link::fromTextAndUrl(t('OpenY upgrade dashboard'), $dashboard_url);
    $this->logger->error($this->t('Could not update config @name. Please add this changes manual. More info here - @link.',
      [
        '@name' => $config,
        '@link' => $dashboard_link->toString(),
      ]
    ));
  }

  /**
   * Check if config exist in openy_config_upgrade_logs.
   *
   * @param string $config_name
   *   Config name.
   *
   * @return bool
   *   TRUE if config was changed.
   */
  public function isManuallyChanged($config_name) {
    $configs = $this->loggerEntityStorage->loadByProperties([
      'type' => 'openy_config_upgrade_logs',
      'name' => $config_name,
    ]);
    return empty($configs) ? FALSE : TRUE;
  }

  /**
   * Update logger entity.
   *
   * @param string $config
   *   Config full name with path.
   * @param string $config_name
   *   Config name.
   *
   * @return int|bool
   *   Entity ID in case of success.
   */
  private function updateLoggerEntity($config, $config_name) {
    $entities = $this->loggerEntityStorage->loadByProperties([
      'type' => 'openy_config_upgrade_logs',
      'name' => $config_name,
    ]);
    if (empty($entities)) {
      return FALSE;
    }
    $logger_entity = array_shift($entities);
    $logger_entity->set('field_config_path', $config);
    $logger_entity->set('field_config_property', '-');
    $logger_entity->save();
    return $logger_entity->id();
  }

}

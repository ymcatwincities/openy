<?php

namespace Drupal\openy_upgrade_tool;

use Drupal\config\Form\ConfigSync;
use Drupal\config\StorageReplaceDataWrapper;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\features\FeaturesManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Extension\ModuleExtensionList;

/**
 * Wrapper for main logic that related to OpenyUpgradeLog.
 */
class OpenyUpgradeLogManager implements OpenyUpgradeLogManagerInterface {

  use StringTranslationTrait;

  /**
   * Upgrade path dashboard route.
   */
  const DASHBOARD = 'entity.openy_upgrade_log.collection';

  /**
   * Upgrade path dashboard route.
   */
  const MODAL_WIDTH = 1000;

  /**
   * The FeaturesManager.
   *
   * @var \Drupal\features\FeaturesManagerInterface
   */
  public $featuresManager;

  /**
   * Entity type manger.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Logger Entity Storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  public $loggerEntityStorage = NULL;

  /**
   * The config storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The renderer service.
   *
   * @var \Drupal\openy_upgrade_tool\ConfigEventIgnorePluginManager
   */
  protected $configEventIgnoreManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The database lock object.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Defines a configuration importer.
   *
   * @var \Drupal\Core\Config\ConfigImporter
   */
  protected $configImporter;

  /**
   * If the config exists, this is that object. Otherwise, FALSE.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\Entity\ConfigEntityInterface|bool
   */
  protected $configExists = FALSE;

  /**
   * ConfigImporterService constructor.
   *
   * @param \Drupal\features\FeaturesManagerInterface $features_manager
   *   Features Manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The config storage.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\openy_upgrade_tool\ConfigEventIgnorePluginManager $config_event_ignore_manager
   *   The Config Event Ignore Plugin Manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher used to notify subscribers of config import events.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend to ensure multiple imports do not occur at same time.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed configuration manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   The module installer.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $loggerChannel
   *   Logger channel.
   */
  public function __construct(
    FeaturesManagerInterface $features_manager,
    EntityTypeManagerInterface $entity_type_manager,
    StorageInterface $config_storage,
    ConfigManagerInterface $config_manager,
    ConfigFactoryInterface $config_factory,
    RendererInterface $renderer,
    ConfigEventIgnorePluginManager $config_event_ignore_manager,
    EventDispatcherInterface $event_dispatcher,
    LockBackendInterface $lock,
    TypedConfigManagerInterface $typed_config,
    ModuleHandlerInterface $module_handler,
    ModuleInstallerInterface $module_installer,
    ThemeHandlerInterface $theme_handler,
    LoggerChannelInterface $loggerChannel,
    ModuleExtensionList $moduleExtensionList) {

    $this->featuresManager = $features_manager;
    $this->logger = $loggerChannel;
    $this->entityTypeManager = $entity_type_manager;
    $this->configStorage = $config_storage;
    $this->renderer = $renderer;
    $this->configEventIgnoreManager = $config_event_ignore_manager;
    // Services necessary for \Drupal\Core\Config\ConfigImporter.
    $this->eventDispatcher = $event_dispatcher;
    $this->configManager = $config_manager;
    $this->configFactory = $config_factory;
    $this->lock = $lock;
    $this->typedConfigManager = $typed_config;
    $this->moduleHandler = $module_handler;
    $this->moduleInstaller = $module_installer;
    $this->themeHandler = $theme_handler;
    $this->moduleExtensionList = $moduleExtensionList;
  }

  /**
   * Get entity storage that used for upgrade tool.
   */
  public function getLoggerEntityStorage() {
    // TODO: Delete this, after deprecated 'logger_entity' deleting
    // and create instance in __construct.
    if ($this->loggerEntityStorage) {
      return $this->loggerEntityStorage;
    }

    $this->loggerEntityStorage = $this->entityTypeManager
      ->getStorage($this->getLoggerEntityTypeName());
    return $this->loggerEntityStorage;
  }

  /**
   * {@inheritdoc}
   */
  public function getOpenyConfigList() {
    $features_configs = $this->featuresManager->listExistingConfig(TRUE);
    // Get openy configs from features configs list.
    $openy_configs = array_filter($features_configs, function ($module, $config) {
      return strpos($module, 'openy') !== FALSE;
    }, ARRAY_FILTER_USE_BOTH);
    return array_keys($openy_configs);
  }

  /**
   * {@inheritdoc}
   */
  public function isForceMode() {
    return (bool) $this->configFactory
      ->get('openy_upgrade_tool.settings')
      ->get('force_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function saveLoggerEntity($name, array $data, $message = NULL) {
    try {
      if ($this->getLoggerEntityTypeName() !== 'logger_entity') {
        // Load OpenyUpgradeLog entity with this config name.
        $entities = $this->getLoggerEntityStorage()->loadByProperties([
          'name' => $name,
        ]);
        if (empty($entities)) {
          // Create new logger entity for this config name if not exist.
          $upgrade_log_item = $this->getLoggerEntityStorage()->create([
            'name' => $name,
          ]);
        }
        else {
          $upgrade_log_item = array_shift($entities);
        }
        $upgrade_log_item->setData($data);
        $upgrade_log_item->setNewRevision(TRUE);
        $upgrade_log_item->setStatus(FALSE);
        $upgrade_log_item->setRevisionCreationTime(time());
        $message = $message ?? 'Manual update outside Open Y.';
        $upgrade_log_item->setRevisionLogMessage($message);
        $upgrade_log_item->save();
        return $upgrade_log_item->id();
      }
    }
    catch (\Exception $e) {
      $msg = 'Failed to save logger entity. Message: %msg';
      $this->logger->error($msg, ['%msg' => $e->getMessage()]);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function createBackup($name) {
    $config_data = $this->configStorage->read($name);
    $revision_message = $this->t('Backup original config before force update.');
    $this->saveLoggerEntity($name, $config_data, $revision_message);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLoggerEntityTypeName() {
    if (!$this->moduleHandler || !$this->moduleHandler->moduleExists('logger_entity')) {
      return 'openy_upgrade_log';
    }

    $logger_entity_exist = $this->entityTypeManager
      ->getStorage('logger_entity_type')
      ->load('openy_config_upgrade_logs');
    if ($logger_entity_exist) {
      return 'logger_entity';
    }

    return 'openy_upgrade_log';
  }

  /**
   * {@inheritdoc}
   */
  public function isManuallyChanged($config_name, $check_force_mode = TRUE) {
    if ($check_force_mode && $this->isForceMode()) {
      // In force mode this function always return FALSE.
      // So Open Y can override any config customization.
      return FALSE;
    }

    if ($this->getLoggerEntityTypeName() == 'logger_entity') {
      // TODO: Delete this, 'logger_entity' is deprecated.
      $props = [
        'type' => 'openy_config_upgrade_logs',
        'name' => $config_name,
      ];
    }
    else {
      $props = ['name' => $config_name];
    }
    $configs = $this->getLoggerEntityStorage()->loadByProperties($props);

    return empty($configs) ? FALSE : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    return $this->getLoggerEntityStorage()->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByName($config_name) {
    $items = $this->getLoggerEntityStorage()->loadByProperties(['name' => $config_name]);
    if (!empty($items)) {
      return reset($items);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applyOpenyVersion($name) {
    $openy_config_data = $this->featuresManager
      ->getExtensionStorages()->read($name);
    // After config import we need to delete upgrade log entity.
    // Set $delete_log to TRUE.
    $this->updateExistingConfig($name, $openy_config_data, TRUE);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function updateExistingConfig($name, array $data, $delete_log = FALSE) {
    $messenger = \Drupal::messenger();

    if (!$this->validateConfigData($name, $data)) {
      return;
    }

    if (isset($this->configImporter) && $this->configImporter->alreadyImporting()) {
      $messenger->addMessage($this->t('Another request may be importing configuration already.'), 'error');
    }
    else {
      try {
        $sync_steps = $this->configImporter->initialize();
        $batch = [
          'operations' => [],
          'finished' => [OpenyUpgradeLogManager::class, 'finishBatch'],
          'title' => $this->t('Importing configuration'),
          'init_message' => $this->t('Starting configuration import.'),
          'progress_message' => $this->t('Completed @current step of @total.'),
          'error_message' => $this->t('Configuration import has encountered an error.'),
        ];
        foreach ($sync_steps as $sync_step) {
          $batch['operations'][] = [
            [ConfigSync::class, 'processBatch'],
            [$this->configImporter, $sync_step],
          ];
        }

        $batch['operations'][] = [
          [OpenyUpgradeLogManager::class, 'setConflictResolvedBatchProcess'],
          [$name],
        ];

        if ($delete_log) {
          $batch['operations'][] = [
            [OpenyUpgradeLogManager::class, 'deleteOpenyUpgradeLogItemBatchProcess'],
            [$name],
          ];
        }
        batch_set($batch);
        return;
      }
      catch (ConfigImporterException $e) {
        // There are validation errors.
        $messenger->addMessage($this->t('The configuration import failed for the following reasons:'), 'error');
        foreach ($this->configImporter->getErrors() as $message) {
          $messenger->addMessage($message, 'error');
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigData($name, array &$data) {
    $messenger = \Drupal::messenger();
    $config_type = $this->getConfigType($name);
    // Load original config.
    if ($config_type !== FeaturesManagerInterface::SYSTEM_SIMPLE_CONFIG) {
      $definition = $this->entityTypeManager->getDefinition($config_type);
      $id_key = $definition->getKey('id');
      $entity_storage = $this->entityTypeManager->getStorage($config_type);
      // If an entity ID was not specified, set an error.
      if (!isset($data[$id_key])) {
        $messenger->addMessage($this->t('Missing ID key "@id_key" for this @entity_type import. Please add this key to your config!', [
          '@id_key' => $id_key,
          '@entity_type' => $definition->getLabel(),
        ]), 'error');
        return FALSE;
      }
      $config_name = $definition->getConfigPrefix() . '.' . $data[$id_key];
      // If there is an existing entity, ensure matching ID and UUID.
      if ($entity = $entity_storage->load($data[$id_key])) {
        $data['uuid'] = $entity->uuid();
        $this->configExists = $entity;
      }
    }
    else {
      $config_name = $name;
      $config = $this->configFactory->get($config_name);
      $this->configExists = !$config->isNew() ? $config : FALSE;
    }

    // Use ConfigImporter validation.
    $source_storage = new StorageReplaceDataWrapper($this->configStorage);
    $source_storage->replaceData($config_name, $data);
    $storage_comparer = new StorageComparer(
      $source_storage,
      $this->configStorage,
      $this->configManager
    );

    if (!$storage_comparer->createChangelist()->hasChanges()) {
      $messenger->addMessage($this->t('There are no changes to import.'), 'status');
      return FALSE;
    }
    else {
      $config_importer = new ConfigImporter(
        $storage_comparer,
        $this->eventDispatcher,
        $this->configManager,
        $this->lock,
        $this->typedConfigManager,
        $this->moduleHandler,
        $this->moduleInstaller,
        $this->themeHandler,
        $this->getStringTranslation(),
        $this->moduleExtensionList
      );

      try {
        $config_importer->validate();
        $this->configImporter = $config_importer;
      }
      catch (ConfigImporterException $e) {
        // There are validation errors.
        $item_list = [
          '#theme' => 'item_list',
          '#items' => $config_importer->getErrors(),
          '#title' => $this->t('The configuration cannot be imported because it failed validation for the following reasons:'),
        ];
        $messenger->addMessage($this->renderer->render($item_list));
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigType($name) {
    $config_info = $this->featuresManager->getConfigType($name);
    return $config_info['type'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigDiff(Config $config) {
    $config_type = $this->getConfigType($config->getName());
    return $this->configEventIgnoreManager->validateChanges($config, $config_type);
  }

  /**
   * Set status TRUE to OpenyUpgradeLog item (Conflict resolved).
   *
   * @param string $openy_upgrade_log
   *   OpenyUpgradeLog item name.
   * @param object $context
   *   The batch context.
   */
  public static function setConflictResolvedBatchProcess($openy_upgrade_log, &$context) {
    $entity = \Drupal::service('openy_upgrade_log.manager')
      ->loadByName($openy_upgrade_log);
    $entity->setStatus(TRUE)->save();
  }

  /**
   * Delete OpenyUpgradeLog item (no need to track customizations).
   *
   * @param string $openy_upgrade_log
   *   OpenyUpgradeLog item name.
   * @param object $context
   *   The batch context.
   */
  public static function deleteOpenyUpgradeLogItemBatchProcess($openy_upgrade_log, &$context) {
    $entity = \Drupal::service('openy_upgrade_log.manager')
      ->loadByName($openy_upgrade_log);
    $entity->delete();
  }

  /**
   * Finish batch.
   *
   * This function is a static function to avoid serializing the ConfigSync
   * object unnecessarily.
   */
  public static function finishBatch($success, $results, $operations) {
    $messenger = \Drupal::messenger();

    if ($success) {
      if (!empty($results['errors'])) {
        foreach ($results['errors'] as $error) {
          $messenger->addMessage($error, 'error');
          \Drupal::logger('config_sync')->error($error);
        }
        $messenger->addMessage(t('The configuration was imported with errors.'), 'warning');
      }
      else {
        $messenger->addMessage(t('The configuration was imported successfully.'));
      }
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $message = t('An error occurred while processing %error_operation with arguments: @arguments', [
        '%error_operation' => $error_operation[0],
        '@arguments' => print_r($error_operation[1], TRUE),
      ]);
      $messenger->addMessage($message, 'error');
    }

    $redirect_url = Url::fromRoute(self::DASHBOARD)
      ->setAbsolute()
      ->toString();
    return new RedirectResponse($redirect_url);
  }

  /**
   * Get upgrade status (check the number of existing conflicts).
   *
   * @return bool
   *   TRUE if unresolved conflicts do not exist.
   */
  public function getUpgradeStatus() {
    $storage = $this->getLoggerEntityStorage();
    $not_resolved_conflicts = (int) $storage->getQuery()
      ->condition('status', 0)
      ->count()
      ->execute();

    return $not_resolved_conflicts === 0;
  }

  /**
   * Get upgrade status details for drupal status page.
   *
   * @return array
   *   Upgrade status details.
   *
   * @see /admin/reports/status
   */
  public function getUpgradeStatusDetails() {
    $storage = $this->getLoggerEntityStorage();
    $total_count = (int) $storage->getQuery()
      ->count()
      ->execute();

    if ($total_count === 0) {
      return [
        'resolved' => 0,
        'conflicts' => 0,
        'total' => 0,
      ];
    }

    $conflicts = (int) $storage->getQuery()
      ->condition('status', 0)
      ->count()
      ->execute();
    return [
      'resolved' => $total_count - $conflicts,
      'conflicts' => $conflicts,
      'total' => $total_count,
    ];
  }
}

<?php

namespace Drupal\openy_upgrade_tool;

use Drupal\Component\Utility\NestedArray;
use Drupal\config_import\ConfigParamUpdaterService;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Advanced version of ConfigParamUpdaterService.
 *
 * With checking of manual config change.
 */
class ConfigParamUpgradeTool extends ConfigParamUpdaterService {

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
   * ConfigImporterService constructor.
   *
   * @param ConfigManagerInterface $config_manager
   *   ConfigManager.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    ConfigManagerInterface $config_manager,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerEntityStorage = $this->entityTypeManager->getStorage('logger_entity');
    parent::__construct($config_manager, $logger_factory);
  }

  /**
   * {@inheritdoc}
   */
  public function update($config, $config_name, $param) {
    // Get base storage config.
    if (!file_exists($config)) {
      $this->logger->error($this->t('File @file does not exist.', ['@file' => $config]));
      return;
    }
    if ($this->isManuallyChanged($config_name)) {
      // Skip config update and log this to logger entity.
      $this->updateLoggerEntity($config, $config_name, $param);
      $dashboard_url = Url::fromRoute('view.openy_upgrade_dashboard.page_1');
      $dashboard_link = Link::fromTextAndUrl(t('OpenY upgrade dashboard'), $dashboard_url);
      $this->logger->error($this->t('Cannot update config @name. Please add those changes manually. More info here - @link.',
        [
          '@name' => $config_name,
          '@link' => $dashboard_link->toString(),
        ]
      ));
      return;
    }
    $storage_config = Yaml::decode(file_get_contents($config));
    // Retrieve a value from a nested array with variable depth.
    $update_value = NestedArray::getValue($storage_config, explode('.', $param));
    if (!$update_value) {
      $this->logger->info(
        $this->t('Param "@param" does not exist in config @name.',
        ['@name' => $config_name, '@param' => $param])
      );
      return;
    }
    // Get active storage config.
    $config_factory = $this->configManager->getConfigFactory();
    $config = $config_factory->getEditable($config_name);
    if ($config->isNew() && empty($config->getOriginal())) {
      $this->logger->error($this->t('Config @name does not exist.', ['@name' => $config_name]));
      return;
    }
    // Update value retrieved from storage config.
    $config->set($param, $update_value);
    // Add openy_upgrade param for detecting this upgrade
    // in '@openy_upgrade_tool.event_subscriber'.
    $config->set('openy_upgrade', TRUE);
    $config->save();
    $this->logger->info($this->t('Param "@param" in config @name was updated.',
      [
        '@name' => $config_name,
        '@param' => $param,
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
   * @param string $param
   *   Identifier to store value in configuration.
   *
   * @return int|bool
   *   Entity ID in case of success.
   */
  private function updateLoggerEntity($config, $config_name, $param) {
    $entities = $this->loggerEntityStorage->loadByProperties([
      'type' => 'openy_config_upgrade_logs',
      'name' => $config_name,
    ]);
    if (empty($entities)) {
      return FALSE;
    }
    $logger_entity = array_shift($entities);
    $logger_entity->set('field_config_path', $config);
    $logger_entity->set('field_config_property', $param);
    $logger_entity->save();
    return $logger_entity->id();
  }

}

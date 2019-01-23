<?php

namespace Drupal\openy_upgrade_tool;

use Drupal\Component\Utility\NestedArray;
use Drupal\config_import\ConfigParamUpdaterService;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
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
   * The OpenyUpgradeLogManager.
   *
   * @var \Drupal\openy_upgrade_tool\OpenyUpgradeLogManagerInterface
   */
  protected $upgradeLogManager;

  /**
   * Logger Entity Storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $loggerEntityStorage;

  /**
   * ConfigImporterService constructor.
   *
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   ConfigManager.
   * @param \Drupal\openy_upgrade_tool\OpenyUpgradeLogManagerInterface $upgrade_log_manager
   *   OpenyUpgradeLog Manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    ConfigManagerInterface $config_manager,
    OpenyUpgradeLogManagerInterface $upgrade_log_manager,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->upgradeLogManager = $upgrade_log_manager;
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
    $storage_config = Yaml::decode(file_get_contents($config));
    // Retrieve a value from a nested array with variable depth.
    $key_exists = FALSE;
    $update_value = NestedArray::getValue($storage_config, explode('.', $param), $key_exists);
    if (!$key_exists) {
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

    if ($this->upgradeLogManager->isManuallyChanged($config_name)) {
      $updated_config_data = $config->get();
      // Skip config update and log this to OpenyUpgradeLog.
      $message = $this->t('Failed attempt to update "@param" param in "@name" during Open Y update queue.', [
        '@param' => $param,
        '@name' => $config_name,
      ]);
      $this->upgradeLogManager->saveLoggerEntity($config_name, $updated_config_data, $message);
      $dashboard_url = Url::fromRoute(OpenyUpgradeLogManager::DASHBOARD);
      $dashboard_link = Link::fromTextAndUrl($this->t('Open Y upgrade dashboard'), $dashboard_url);
      $this->logger->error($this->t('Cannot update config @name. Please add those changes manually. More info here - @link.', [
        '@name' => $config_name,
        '@link' => $dashboard_link->toString(),
      ]));
      return;
    }

    // Notify ConfigEventSubscriber that this is update from OpenY.
    global $_openy_config_import_event;
    $_openy_config_import_event = TRUE;
    $config->save();
    $this->logger->info($this->t('Param "@param" in config @name was updated.', [
      '@name' => $config_name,
      '@param' => $param,
    ]));
    $_openy_config_import_event = FALSE;
  }

}

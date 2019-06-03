<?php

namespace Drupal\openy_upgrade_tool\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Component\Utility\DiffArray;
use Drupal\openy_upgrade_tool\OpenyUpgradeLogManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class ConfigEventSubscriber.
 *
 * @package Drupal\openy_upgrade_tool
 */
class ConfigEventSubscriber implements EventSubscriberInterface {

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
   * ConfigEventSubscriber constructor.
   *
   * @param \Drupal\openy_upgrade_tool\OpenyUpgradeLogManagerInterface $upgrade_log_manager
   *   OpenyUpgradeLog Manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $loggerChannel
   *   Logger channel.
   */
  public function __construct(
    OpenyUpgradeLogManagerInterface $upgrade_log_manager,
    LoggerChannelInterface $loggerChannel) {

    $this->logger = $loggerChannel;
    $this->upgradeLogManager = $upgrade_log_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onSavingConfig', 800];
    return $events;
  }

  /**
   * Subscriber Callback for the event.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   Configuration save event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function onSavingConfig(ConfigCrudEvent $event) {
    // TODO: Find solution without global variable.
    global $_openy_config_import_event;
    $config = $event->getConfig();
    $original = $config->getOriginal();
    if (empty($original)) {
      // Skip new config.
      return;
    }
    $updated = $config->get();

    if ($original == $updated) {
      // Skip config without updates.
      return;
    }
    $config_name = $config->getName();
    $openy_configs = $this->upgradeLogManager->getOpenyConfigList();
    if (!in_array($config_name, $openy_configs)) {
      // Skip configs not related to Open Y.
      return;
    }
    if (!$_openy_config_import_event) {
      // This config was updated outside Open Y profile.
      $ignore = $this->upgradeLogManager->validateConfigDiff($config);
      if ($ignore) {
        // Skip tracking these changes according to ignore rules.
        // @see Plugin/ConfigEventIgnore
        return;
      }

      if (!$this->hasDiffFromOpenY($updated, $config_name)) {
        // No need to track customization if result config similar is to Open Y.
        return;
      }

      $this->upgradeLogManager->saveLoggerEntity($config_name, $updated);
      $this->logger->warning($this->t('You have manual updated @name config from Open Y profile.', ['@name' => $config_name]));
    }
    else {
      // Check if exist logger entity and enabled force mode.
      if ($this->upgradeLogManager->isForceMode() && $this->upgradeLogManager->isManuallyChanged($config_name, FALSE)) {
        $this->upgradeLogManager->createBackup($config_name);
      }
      $this->logger->info($this->t('Open Y has upgraded @name config.', ['@name' => $config_name]));
    }
  }

  /**
   * Check diff with Open Y config version.
   *
   * @param array $updated
   *   Updated configuration data.
   * @param string $config_name
   *   Configuration name.
   *
   * @return bool
   *   FALSE if no difference.
   */
  public function hasDiffFromOpenY($updated, $config_name) {
    unset($updated['uuid'], $updated['_core']);
    $openy_config_data = $this->upgradeLogManager
      ->featuresManager
      ->getExtensionStorages()
      ->read($config_name);

    $diff = DiffArray::diffAssocRecursive($openy_config_data, $updated);

    return !empty($diff);
  }

}

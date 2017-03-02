<?php

namespace Drupal\openy_upgrade_tool\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\features\FeaturesManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ConfigEventSubscriber.
 *
 * @package Drupal\openy_upgrade_tool
 */
class ConfigEventSubscriber implements EventSubscriberInterface {

  /**
   * The FeaturesManager.
   *
   * @var \Drupal\features\FeaturesManagerInterface
   */
  protected $featuresManager;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * ConfigEventSubscriber constructor.
   *
   * @param \Drupal\features\FeaturesManagerInterface $features_manager
   *   Features Manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $loggerChannel
   *   Logger channel.
   */
  public function __construct(
    FeaturesManagerInterface $features_manager,
    LoggerChannelInterface $loggerChannel) {

    $this->logger = $loggerChannel;
    $this->featuresManager = $features_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = array('onSavingConfig', 800);
    return $events;
  }

  /**
   * Get OpenY features configs list.
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
   * Subscriber Callback for the event.
   *
   * @param ConfigCrudEvent $event
   *   Configuration save event.
   */
  public function onSavingConfig(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    $config_name = $config->getName();
    $openy_configs = $this->getOpenyConfigList();
    if (!in_array($config_name, $openy_configs)) {
      // Skip configs not related to openy.
      return;
    }
    if (!$config->get('openy_upgrade')) {
      // This config was updated outside openy profile.
      // TODO: Add this config to logger entity with status "manual change".
      $this->logger->info('You have manual saved a configuration of ' . $config->getName());
    }
    else {
      // Remove openy_upgrade param from config.
      $config->clear('openy_upgrade');
      $this->logger->info('You have openy upgrade a configuration of ' . $config->getName());
    }

  }

}

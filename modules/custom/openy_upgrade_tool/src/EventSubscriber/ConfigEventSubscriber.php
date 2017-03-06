<?php

namespace Drupal\openy_upgrade_tool\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\features\FeaturesManagerInterface;
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
   * The FeaturesManager.
   *
   * @var \Drupal\features\FeaturesManagerInterface
   */
  protected $featuresManager;

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
  protected $loggerEntityStorage;

  /**
   * ConfigEventSubscriber constructor.
   *
   * @param \Drupal\features\FeaturesManagerInterface $features_manager
   *   Features Manager.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $loggerChannel
   *   Logger channel.
   */
  public function __construct(
    FeaturesManagerInterface $features_manager,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelInterface $loggerChannel) {

    $this->logger = $loggerChannel;
    $this->featuresManager = $features_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerEntityStorage = $this->entityTypeManager->getStorage('logger_entity');
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
   * Creates logger entity.
   *
   * @param string $name
   *   Config name.
   *
   * @return int|bool
   *   Entity ID in case of success.
   */
  private function saveLoggerEntity($name) {
    try {
      // Load logger entity with this config name.
      $entities = $this->loggerEntityStorage->loadByProperties([
        'type' => 'openy_config_upgrade_logs',
        'name' => $name,
      ]);
      if (empty($entities)) {
        // Create new logger entity for this config name if not exist.
        $logger_entity = $this->loggerEntityStorage->create([
          'type' => 'openy_config_upgrade_logs',
        ]);
      }
      else {
        $logger_entity = array_shift($entities);
      }
      $logger_entity->setName($name);
      $logger_entity->setData([$name]);
      $logger_entity->save();
      return $logger_entity->id();
    }
    catch (\Exception $e) {
      $msg = 'Failed to save logger entity. Message: %msg';
      $this->logger->error($msg, ['%msg' => $e->getMessage()]);
      return FALSE;
    }
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
      $this->saveLoggerEntity($config_name);
      $this->logger->warning($this->t('You have manual updated @name config from OpenY profile.', ['@name' => $config_name]));
    }
    else {
      // Remove openy_upgrade param from config.
      $config->clear('openy_upgrade');
      $this->logger->info($this->t('OpenY was upgraded @name config.', ['@name' => $config_name]));
    }

  }

}

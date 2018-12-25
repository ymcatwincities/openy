<?php

/**
 * @file
 * Contains \Drupal\config_devel\EventSubscriber\ConfigDevelSubscriberBase.
 */

namespace Drupal\config_devel\EventSubscriber;


use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;

class ConfigDevelSubscriberBase {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * Constructs the ConfigDevelAutoExportSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ConfigManagerInterface $config_manager) {
    $this->configFactory = $config_factory;
    $this->configManager = $config_manager;
  }

  /**
   * @param string $entity_type_id
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected function getStorage($entity_type_id) {
    return $this->configManager->getEntityManager()->getStorage($entity_type_id);
  }

  /**
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $entity_storage
   * @param string $config_name
   *
   * @return string
   */
  protected function getEntityId(ConfigEntityStorageInterface $entity_storage, $config_name) {
    // getIDFromConfigName adds a dot but getConfigPrefix has a dot already.
    return $entity_storage::getIDFromConfigName($config_name, $entity_storage->getEntityType()->getConfigPrefix());
  }

  /**
   * @return \Drupal\Core\Config\Config
   */
  protected function getSettings() {
    return $this->configFactory->getEditable('config_devel.settings');
  }

}

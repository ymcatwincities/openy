<?php

namespace Drupal\openy_upgrade_tool;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\features\FeaturesExtensionStorages;

/**
 * Defines the file storage with search by features.
 *
 * Note: this storage used only for diff logic.
 */
class OpenYUpgradeToolFeatureStorage extends FileStorage {

  /**
   * The active storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * Feature storage.
   *
   * @var \Drupal\features\FeaturesExtensionStorages
   */
  protected $featuresStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(StorageInterface $config_storage) {
    parent::__construct('');
    $this->configStorage = $config_storage;
    $this->featuresStorage = new FeaturesExtensionStorages($this->configStorage);
    $this->featuresStorage->addStorage(InstallStorage::CONFIG_INSTALL_DIRECTORY);
    $this->featuresStorage->addStorage(InstallStorage::CONFIG_OPTIONAL_DIRECTORY);
  }

  /**
   * {@inheritdoc}
   */
  public function read($name) {
    return $this->featuresStorage->read($name);
  }

}

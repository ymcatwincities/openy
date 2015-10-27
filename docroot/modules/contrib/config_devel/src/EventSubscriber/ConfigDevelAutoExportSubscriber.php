<?php

/**
 * @file
 * Contains Drupal\config_devel\EventSubscriber\ConfigDevelAutoExportSubscriber.
 */

namespace Drupal\config_devel\EventSubscriber;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Yaml\Exception\DumpException;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigRenameEvent;
use Drupal\Core\Config\ConfigEvents;

/**
 * ConfigDevelAutoExportSubscriber subscriber for configuration CRUD events.
 */
class ConfigDevelAutoExportSubscriber extends ConfigDevelSubscriberBase implements EventSubscriberInterface {

  /**
   * The files to automatically export.
   *
   * @var array
   */
  protected $autoExportFiles;

  /**
   * React to configuration ConfigEvent::SAVE events.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   The event to process.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $this->autoExportConfig($event->getConfig());
  }

  /**
   * React to configuration ConfigEvent::RENAME events.
   *
   * @param \Drupal\Core\Config\ConfigRenameEvent $event
   *   The event to process.
   */
  public function onConfigRename(ConfigRenameEvent $event) {
    $this->autoExportConfig($event->getConfig());
  }

  /**
   * Automatically export configuration.
   *
   * @param Config $config
   *   The config object.
   */
  protected function autoExportConfig(Config $config) {
    $config_name = $config->getName();
    $file_names = array_filter($this->getSettings()->get('auto_export'), function ($file_name) use ($config_name) {
      return basename($file_name, '.' . FileStorage::getFileExtension()) == $config_name;
    });
    $this->writeBackConfig($config, $file_names);
  }

  /**
   * write configuration back to files.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The config object.
   * @param array $file_names
   *   The file names to which the configuration should be written.
   */
  public function writeBackConfig(Config $config, array $file_names) {
    if ($file_names) {
      $data = $config->get();
      $config_name = $config->getName();
      if ($entity_type_id = $this->configManager->getEntityTypeIdByName($config_name)) {
        unset($data['uuid']);
      }
      foreach ($file_names as $file_name) {
        try {
          file_put_contents($file_name, (new InstallStorage())->encode($data));
        }
        catch (DumpException $e) {
          // Do nothing. What could we do?
        }
      }
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = array('onConfigSave', 10);
    $events[ConfigEvents::RENAME][] = array('onConfigRename', 10);
    return $events;
  }

}

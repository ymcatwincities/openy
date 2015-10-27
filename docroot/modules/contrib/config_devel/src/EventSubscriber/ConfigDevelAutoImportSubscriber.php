<?php

/**
 * @file
 * Contains \Drupal\config_devel\EventSubscriber\ConfigDevelAutoImportSubscriber.
 */

namespace Drupal\config_devel\EventSubscriber;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\InstallStorage;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ConfigDevelAutoImportSubscriber extends ConfigDevelSubscriberBase implements EventSubscriberInterface {

  /**
   * Reinstall changed config files.
   */
  public function autoImportConfig() {
    $config = $this->getSettings();
    $changed = FALSE;
    foreach ($config->get('auto_import') as $key => $file) {
      $contents = @file_get_contents($file['filename']);
      if (!$contents) {
        continue;
      }
      $hash = Crypt::hashBase64($contents);
      if ($hash != $file['hash']) {
        $changed = TRUE;
        $config->set("auto_import.$key.hash", $hash);
        $data = (new InstallStorage())->decode($contents);
        $config_name = basename($file['filename'], '.yml');
        $entity_type_id = $this->configManager->getEntityTypeIdByName($config_name);
        if ($entity_type_id) {
          $entity_storage = $this->getStorage($entity_type_id);
          $entity_id = $this->getEntityId($entity_storage, $config_name);
          $entity_type = $entity_storage->getEntityType();
          $id_key = $entity_type->getKey('id');
          $data[$id_key] = $entity_id;
          $entity = $entity_storage->create($data);
          if ($existing_entity = $entity_storage->load($entity_id)) {
            $entity
              ->set('uuid', $existing_entity->uuid())
              ->enforceIsNew(FALSE);
          }
          $entity_storage->save($entity);
        }
        else {
          $this->configFactory->getEditable($config_name)->setData($data)->save();
        }
      }
    }
    if ($changed) {
      $config->save();
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('autoImportConfig', 20);
    return $events;
  }

}

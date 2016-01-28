<?php
/**
 * @file
 * Contains ConfigImporterService.
 */

namespace Drupal\config_import;

use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\ConfigManagerInterface;

class ConfigImporterService {

  /**
 * The config manager.
 *
 * @var \Drupal\Core\Config\ConfigManagerInterface
 */
  protected $configManager;

  /**
   * Constructs a new ConfigImporterService.
   *
   * @param \Drupal\Core\Form\FormValidatorInterface $form_validator
   *   The form validator.
   */
  public function __construct(ConfigManagerInterface $config_manager) {
    $this->configManager = $config_manager;
  }

  /**
   * Import config.
   *
   * @param array $filenames
   *   Array of strings, each item is a path to config file.
   */
  public function importConfigs(array $filenames) {
    foreach ($filenames as $filename) {
      $contents = @file_get_contents($filename);
      if (!$contents) {
        continue;
      }
      $data = (new InstallStorage())->decode($contents);
      $config_name = basename($filename, '.yml');
      $entity_type_id = $this->configManager->getEntityTypeIdByName($config_name);
      if ($entity_type_id) {
        $entity_storage = $this->getStorage($entity_type_id);
        $entity_id = $entity_storage::getIDFromConfigName($config_name, $entity_storage->getEntityType()->getConfigPrefix());
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

  /**
   * Return storage.
   *
   * @param string $entity_type_id
   *   Entity type id.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   */
  private function getStorage($entity_type_id) {
    return $this->configManager->getEntityManager()->getStorage($entity_type_id);
  }
}

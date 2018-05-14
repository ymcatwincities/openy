<?php

namespace Drupal\openy;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Openy modules manager.
 */
class OpenyModulesManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a OpenyModulesManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Remove Entity bundle.
   *
   * This is helper function for modules uninstall with correct bundle
   * deleting and content cleanup.
   *
   * @param string $content_entity_type
   *   Content entity type (node, block_content, paragraph, etc.)
   * @param string $config_entity_type
   *   Config entity type (node_type, block_content_type, paragraphs_type, etc.)
   * @param string $bundle
   *   Entity bundle machine name.
   * @param string $field
   *   Content entity field name that contain reference to bundle.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function removeEntityBundle($content_entity_type, $config_entity_type, $bundle, $field = 'type') {
    if ($content_entity_type) {
      // Remove existing data of content entity.
      $query = $this->entityTypeManager
        ->getStorage($content_entity_type)
        ->getQuery('AND')
        ->condition($field, $bundle);

      $ids = $query->execute();
      $storage_handler = $this->entityTypeManager->getStorage($content_entity_type);
      $entities = $storage_handler->loadMultiple($ids);
      try {
        $storage_handler->delete($entities);
      }
      catch (\Exception $e) {
        watchdog_exception('openy', $e, "Error! Can't delete content related 
        to '@entity_type' bundle '@bundle', please remove it manually (Entity ID's - @ids).<br>
        After this delete config entity @config_entity_type bundle '@bundle'.", [
          '@entity_type' => $content_entity_type,
          '@config_entity_type' => $config_entity_type,
          '@bundle' => $bundle,
          '@ids' => implode(', ', $ids),
        ], RfcLogLevel::NOTICE);
        return;
      }
      // TODO: Fix minor issue. After paragraph bundle deleting and restoring
      // we get additional empty paragraph on node edit page if try to add
      // paragraph of this type (if content of this paragraph type was in
      // node).
      // Proposed solution - cleanup entity reference field tables from
      // removed target bundles.
    }

    if ($config_entity_type) {
      // Remove bundle.
      $config_entity_type_bundle = $this->entityTypeManager
        ->getStorage($config_entity_type)
        ->load($bundle);
      if ($config_entity_type_bundle) {
        try {
          $config_entity_type_bundle->delete();
        }
        catch (\Exception $e) {
          watchdog_exception('openy', $e, "Error! Can't delete config entity 
          '@config_entity_type' bundle '@bundle', please remove it manually.", [
            '@config_entity_type' => $config_entity_type,
            '@bundle' => $bundle,
          ], RfcLogLevel::NOTICE);
        }
      }
    }
  }

}

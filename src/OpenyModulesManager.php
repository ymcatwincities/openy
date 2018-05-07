<?php

namespace Drupal\openy;

use Drupal\Core\Entity\EntityTypeManagerInterface;

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
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeEntityBundle($content_entity_type, $config_entity_type, $bundle) {
    // Remove existing data of content entity.
    $query = $this->entityTypeManager
      ->getStorage($content_entity_type)
      ->getQuery('AND')
      ->condition('type', $bundle);

    $ids = $query->execute();
    $storage_handler = $this->entityTypeManager->getStorage($content_entity_type);
    $entities = $storage_handler->loadMultiple($ids);
    $storage_handler->delete($entities);

    // Remove bundle.
    $config_entity_type_bundle = $this->entityTypeManager
      ->getStorage($config_entity_type)
      ->load($bundle);
    if ($config_entity_type_bundle) {
      $config_entity_type_bundle->delete();
    }
  }

}

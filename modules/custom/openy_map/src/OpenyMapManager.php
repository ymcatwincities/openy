<?php

namespace Drupal\openy_map;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * OpenY Map module service.
 */
class OpenyMapManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Load all node types with 'field_location_address' field.
   *
   * @return array
   *   Array of node types.
   */
  public function getLocationNodeTypes() {
    $fieldMap = $this->entityFieldManager->getFieldMap();
    $locationBundles = $fieldMap['node']['field_location_coordinates']['bundles'];
    $nodeTypes = $this->entityTypeManager->getStorage('node_type')->loadByProperties(['type' => array_keys($locationBundles)]);

    return $nodeTypes;
  }

}

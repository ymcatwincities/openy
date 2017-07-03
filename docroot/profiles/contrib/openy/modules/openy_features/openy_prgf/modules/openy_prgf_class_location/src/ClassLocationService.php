<?php

namespace Drupal\openy_prgf_class_location;

use Drupal\node\Entity\Node;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ClassLocationService.
 *
 * @package Drupal\openy_prgf_class_location
 */
class ClassLocationService implements ClassLocationServiceInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Constructs a new ClassLocationService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The current service container.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ContainerInterface $container) {
    $this->entityTypeManager = $entity_type_manager;
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocationNode($location_id) {
    $location = NULL;

    $query = \Drupal::entityQuery('node');
    $group = $query->orConditionGroup()
      ->condition('type', 'branch')
      ->condition('type', 'camp');
    $nodes = $query->condition('nid', $location_id)
      ->condition('status', 1)
      ->condition($group)
      ->execute();

    if (!empty($nodes)) {
      $location = Node::load($location_id);
    }

    return $location;
  }

}

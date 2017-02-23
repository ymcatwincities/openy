<?php

namespace Drupal\openy_prgf_camp_menu;

use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CampMenuService.
 *
 * @package Drupal\openy_prgf_camp_menu
 */
class CampMenuService implements CampMenuServiceInterface {

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
   * Constructs a new CampMenuService.
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
   * Retrieves referenced Camp node for the node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Drupal\node\NodeInterface|null
   *   Camp node or NULL.
   */
  public function getNodeCampNode(NodeInterface $node) {
    $camp = NULL;
    if ($camp_service = $this->container->get('openy_loc_camp.camp_service')) {
      $camp = $camp_service->getNodeCampNode($node);
    }

    return $camp;
  }

  /**
   * Retrieves Camp menu for any node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return array
   *   Array of menu links.
   */
  public function getNodeCampMenu(NodeInterface $node) {
    if (!($camp = $this->getNodeCampNode($node))) {
      return [];
    }

    return $this->getCampNodeCampMenu($camp);
  }

  /**
   * Retrieves Camp menu for the Camp CT node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Camp node.
   *
   * @return array
   *   Array of menu links.
   */
  private function getCampNodeCampMenu(NodeInterface $node) {
    if ($node->bundle() != 'camp') {
      return [];
    }

    $links = [];
    foreach ($node->field_camp_menu_links->getValue() as $link) {
      $links[] = $link;
    }

    return $links;
  }

}

<?php

namespace Drupal\openy_digital_signage_schedule;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for OpenY Digital Signage Schedule Item entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class OpenYScheduleItemHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    if ($collection_route = $this->getCollectionRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.collection", $collection_route);
    }

    return $collection;
  }

  /**
   * Gets the collection route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    if (!$entity_type->hasLinkTemplate('collection') || !$entity_type->hasListBuilderClass()) {
      return NULL;
    }

    $entity_type_id = $entity_type->id();
    $route = new Route($entity_type->getLinkTemplate('collection'));
    $route
      ->setDefaults([
        '_entity_list' => $entity_type_id,
        '_title' => "{$entity_type->getLabel()} list",
      ])
      ->setRequirement('_permission', 'access OpenY Digital Signage Schedule Item overview')
      ->setOption('_admin_route', TRUE);

    return $route;
  }

}

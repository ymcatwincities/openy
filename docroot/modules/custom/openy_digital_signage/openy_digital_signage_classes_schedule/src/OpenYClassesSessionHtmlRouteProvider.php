<?php

namespace Drupal\openy_digital_signage_classes_schedule;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Digital Signage Classes Session entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 *
 * @ingroup openy_digital_signage_classes_schedule
 */
class OpenYClassesSessionHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    if ($collection_route = $this->getCollectionRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.collection", $collection_route);
    }

    // Add override route.
    $schedule_route = $this->getOverrideRoute($entity_type);
    $collection->add("entity.{$entity_type_id}.override", $schedule_route);

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
        '_title' => "Classes Sessions list",
      ])
      ->setRequirement('_permission', 'access Digital Signage Classes Session overview')
      ->setOption('_admin_route', TRUE);

    return $route;
  }

  /**
   * Gets the Classes Session override route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getOverrideRoute(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    $route = new Route($entity_type->getLinkTemplate('override'));
    $route
      ->setDefaults([
        '_entity_form' => 'openy_ds_classes_session.override',
        '_title' => 'Override Session: ' . $entity_type->getLabel(),
      ])
      ->setRequirement('_permission', 'access Digital Signage Classes Session overview')
      ->setOption('parameters', [
        $entity_type_id => ['type' => 'entity:' . $entity_type_id],
      ])
      ->setOption('_admin_route', TRUE);

    // Entity types with serial IDs can specify this in their route
    // requirements, improving the matching process.
    if ($this->getEntityTypeIdKeyType($entity_type) === 'integer') {
      $route->setRequirement($entity_type_id, '\d+');
    }

    return $route;
  }

}

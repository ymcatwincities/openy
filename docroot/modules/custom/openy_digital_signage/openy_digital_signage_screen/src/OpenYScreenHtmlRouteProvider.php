<?php

namespace Drupal\openy_digital_signage_screen;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for OpenY Digital Signage Screen entities.
 *
 * @see Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class OpenYScreenHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    if ($collection_route = $this->getCollectionRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.collection", $collection_route);
    }

    if ($settings_form_route = $this->getSettingsFormRoute($entity_type)) {
      $collection->add("$entity_type_id.settings", $settings_form_route);
    }

    // Add screen schedule route.
    $schedule_route = $this->getScheduleRoute($entity_type);
    $collection->add("entity.{$entity_type_id}.schedule", $schedule_route);

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
      return;
    }

    $entity_type_id = $entity_type->id();
    $route = new Route($entity_type->getLinkTemplate('collection'));
    $route
      ->setDefaults([
        '_entity_list' => $entity_type_id,
        '_title' => "{$entity_type->getLabel()} list",
      ])
      ->setRequirement('_permission', 'access OpenY Digital Signage Screen overview')
      ->setOption('_admin_route', TRUE);

    return $route;
  }

  /**
   * Gets the Screen schedule route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getScheduleRoute(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    $route = new Route($entity_type->getLinkTemplate('schedule'));
    $route
      ->setDefaults([
        '_controller' => '\Drupal\openy_digital_signage_screen\Controller\OpenYScreenSchedule::schedulePage',
        '_title_callback' => '\Drupal\openy_digital_signage_screen\Controller\OpenYScreenSchedule::scheduleTitle',
      ])
      // TODO: adjust.
      ->setRequirement('_permission', 'access OpenY Digital Signage Screen overview')
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

  /**
   * Gets the settings form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getSettingsFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->getBundleEntityType()) {
      return;
    }

    $route = new Route("/admin/digital-signage/screens/settings");
    $route
      ->setDefaults([
        '_form' => 'Drupal\openy_digital_signage_screen\Form\OpenYScreenSettingsForm',
        '_title' => "{$entity_type->getLabel()} settings",
      ])
      ->setRequirement('_permission', $entity_type->getAdminPermission())
      ->setOption('_admin_route', TRUE);

    return $route;
  }

}

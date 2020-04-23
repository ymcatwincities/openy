<?php

namespace Drupal\openy_node_alert\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;


/**
 * Class AlertManager.
 *
 * @package Drupal\openy_node_alert\Service
 */
class AlertManager {

  /**
   * The alert builders array.
   *
   * @var \Drupal\openy_node_alert\Service\AlertBuilderInterface[]
   */
  protected $alerts = [];

  /**
   * An array with sorted alerts builders by priority, NULL otherwise.
   *
   * @var null|array
   */
  protected $alertsSorted = NULL;

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * Constructs the Alert manager.
   *
   * @param EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->nodeStorage = $entity_type_manager->getStorage('node');
  }

  /**
   * Adds alerts builders to internal service storage.
   *
   * @param \Drupal\openy_node_alert\Service\AlertBuilderInterface $alert
   *   The alert service.
   * @param int $priority
   *   The service priority.
   */
  public function addBuilder(AlertBuilderInterface $alert, $priority = 0) {
    $this->alerts[$priority][] = $alert;
    // Reset sorted status to be resorted on next call.
    $this->alertsSorted = NULL;
  }

  /**
   * Sorts alert services.
   *
   * @return \Drupal\openy_node_alert\Service\AlertBuilderInterface[]
   *   The sorted messages services.
   */
  protected function sortAlerts() {
    $sorted = [];
    krsort($this->alerts);

    foreach ($this->alerts as $alerts) {
      $sorted = array_merge($sorted, $alerts);
    }

    return $sorted;
  }

  /**
   * Gets all alerts from services.
   *
   * @param EntityInterface $node
   *  Node to retrieve referenced alerts.
   * @return array
   *   The array alert ids to display on page.
   */
  public function getAlerts(EntityInterface $node) {
    if (!$this->alertsSorted) {
      $this->alertsSorted = $this->sortAlerts();
    }
    // Get alerts without location assigned.
    $query = $this->nodeStorage->getQuery()
      ->condition('type', 'alert')
      ->condition('status', 1)
      ->notExists('field_alert_location');
    $alerts = $query->execute();

    // Get alerts from services.
    /** @var \Drupal\openy_node_alert\Service\AlertBuilderInterface $alert_service */
    foreach ($this->alertsSorted as $alert_service) {
      if (!$alert_service->applies($node)) {
        // The service does not apply, so we continue with the other services.
        continue;
      }
      $alerts = array_merge($alerts, $alert_service->build($node));
    }
    return $alerts;
  }

}

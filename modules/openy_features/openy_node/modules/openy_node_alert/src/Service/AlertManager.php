<?php

namespace Drupal\openy_node_alert\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;


/**
 * Class AlertManager.
 *
 * @package Drupal\openy_node_alert\Service
 */
class AlertManager {

  /**
   * The alerts array.
   *
   * @var \Drupal\openy_node_alert\Service\AlertBuilderInterface[]
   */
  protected $alerts = [];

  /**
   * An array with sorted alerts by priority, NULL otherwise.
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
   * Constructs the LandingPageAlertBuilder.
   *
   * @param EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->nodeStorage = $entity_type_manager->getStorage('node');
  }

  /**
   * Adds alerts to internal service storage.
   *
   * @param \Drupal\openy_node_alert\Service\AlertBuilderInterface $alert
   *   The message service.
   * @param int $priority
   *   The service priority.
   */
  public function addBuilder(AlertBuilderInterface $alert, $priority = 0) {
    $this->alerts[$priority][] = $alert;
    // Reset sorted status to be resorted on next call.
    $this->alertsSorted = NULL;
  }

  /**
   * Sorts messages services.
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
      Node to retrieve referenced alerts.
   * @return array
   *   The array alert ids to display on page.
   */
  public function getAlerts(EntityInterface $node) {
    if (!$this->alertsSorted) {
      $this->alertsSorted = $this->sortAlerts();
    }
    // Get ids of alerts without location assigned.
    $query = $this->nodeStorage->getQuery()
      ->condition('type', 'alert')
      ->condition('status', 1)
      ->notExists('field_alert_location');
    $alerts = $query->execute();
    foreach ($this->alertsSorted as $alert_service) {
      $temp =  $alerts;
      /** @var \Drupal\openy_node_alert\Service\AlertBuilderInterface $alert_service */
      if (!empty($alert_service->build($node))) {
        $alerts = array_merge($temp,$alert_service->build($node));
      }
    }

    return $alerts;
  }

}

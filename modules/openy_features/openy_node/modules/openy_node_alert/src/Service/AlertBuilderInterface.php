<?php

namespace Drupal\openy_node_alert\Service;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Interface AlertBuilderInterface.
 *
 * @package Drupal\openy_node_alert\Service
 */
interface AlertBuilderInterface {

  /**
   * Builds the alert ids array for node.
   *
   * @param EntityInterface $node
   *  Node to retrieve referenced alerts.
   * @return array|void
   *   Alerts ids.
   */
  public function build(EntityInterface $node);

}

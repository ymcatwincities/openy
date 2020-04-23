<?php

namespace Drupal\openy_node_alert\Service;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface AlertBuilderInterface.
 *
 * @package Drupal\openy_node_alert\Service
 */
interface AlertBuilderInterface {

  /**
   * Whether this alert builder should be used to build the alert list.
   *
   * @param EntityInterface $node
   *  Node to check alerts.
   *
   * @return bool
   *   TRUE if this builder should be used or FALSE to let other builders
   *   decide.
   */
  public function applies(EntityInterface $node);

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

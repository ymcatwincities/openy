<?php

namespace Drupal\openy_node_landing;

use Drupal\Core\Entity\EntityInterface;
use Drupal\openy_node_alert\Service\AlertBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides an alert builder for landing pages.
 */
class LandingPageAlertBuilder implements AlertBuilderInterface {

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
   * {@inheritdoc}
   */
  public function applies(EntityInterface $node) {
    return $node->bundle() == 'landing_page' && !$node->field_lp_location->isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function build(EntityInterface $node) {
    $location_ids = array_column($node->field_lp_location->getValue(), 'target_id');
    $alerts_entities = $this->nodeStorage->loadByProperties([
      'type' => 'alert',
      'field_alert_location' => $location_ids,
      'status' => 1,
    ]);
    return array_keys($alerts_entities);
  }

}

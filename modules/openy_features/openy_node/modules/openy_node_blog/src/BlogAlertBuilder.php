<?php

namespace Drupal\openy_node_blog;

use Drupal\Core\Entity\EntityInterface;
use Drupal\openy_node_alert\Service\AlertBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides an alert builder for blog post pages.
 */
class BlogAlertBuilder implements AlertBuilderInterface {

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * Constructs the BlogAlertBuilder.
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
    return $node->bundle() == 'blog' && !$node->field_blog_location->isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function build(EntityInterface $node) {
    $location_ids = array_column($node->field_blog_location->getValue(), 'target_id');
    $alerts_entities = $this->nodeStorage->loadByProperties([
      'type' => 'alert',
      'field_alert_location' => $location_ids,
      'status' => 1,
    ]);
    return array_keys($alerts_entities);
  }

}

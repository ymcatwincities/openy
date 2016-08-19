<?php

/**
 * @file
 * Contains \Drupal\migrate_plus\Plugin\Discovery\ConfigEntityDiscovery.
 */

namespace Drupal\migrate_plus\Plugin\Discovery;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryTrait;

/**
 * Allows configuration entities to define plugin definitions.
 */
class ConfigEntityDiscovery implements DiscoveryInterface {

  use DiscoveryTrait;

  /**
   * Entity type to query.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Construct a YamlDiscovery object.
   *
   * @param string $entity_type
   *   The entity type to query for.
   */
  function __construct($entity_type) {
    $this->entityType = $entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definition = \Drupal::entityTypeManager()->getDefinition($this->entityType);
    $prefix = $definition->getConfigPrefix() . '.';
    $storage = \Drupal::service('config.storage');
    $query = \Drupal::entityQuery($this->entityType);
    $ids = $query->execute();
    $definitions = [];
    foreach ($ids as $id) {
      $definitions[$id] = $storage->read($prefix . $id);
    }

    return $definitions;
  }

}

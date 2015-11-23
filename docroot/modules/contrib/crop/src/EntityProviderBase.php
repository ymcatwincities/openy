<?php

/**
 * @file
 * Contains \Drupal\crop\EntityProviderBase.
 */

namespace Drupal\crop;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Base implementation for entity provider plugins.
 */
abstract class EntityProviderBase extends PluginBase implements EntityProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  abstract public function uri(EntityInterface $entity);

}

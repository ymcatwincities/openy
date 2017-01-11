<?php

namespace Drupal\purge\Plugin\Purge\Queuer;

use Drupal\Core\Plugin\PluginBase;
use Drupal\purge\Plugin\Purge\Queuer\QueuerInterface;

/**
 * Provides base implementations for queuers.
 */
abstract class QueuerBase extends PluginBase implements QueuerInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->getPluginDefinition()['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->getPluginDefinition()['description'];
  }

}

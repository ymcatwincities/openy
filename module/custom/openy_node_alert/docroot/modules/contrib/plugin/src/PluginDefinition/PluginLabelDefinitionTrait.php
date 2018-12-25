<?php

namespace Drupal\plugin\PluginDefinition;

/**
 * Implements \Drupal\Component\Plugin\PluginLabelDefinitionInterface.
 *
 * @ingroup Plugin
 */
trait PluginLabelDefinitionTrait {

  /**
   * The human-readable label.
   *
   * @var string|null
   */
  protected $label;

  /**
   * Implements \Drupal\Component\Plugin\PluginLabelDefinitionInterface::setLabel().
   */
  public function setLabel($label) {
    $this->label = $label;

    return $this;
  }

  /**
   * Implements \Drupal\Component\Plugin\PluginLabelDefinitionInterface::getLabel().
   */
  public function getLabel() {
    return $this->label;
  }

}

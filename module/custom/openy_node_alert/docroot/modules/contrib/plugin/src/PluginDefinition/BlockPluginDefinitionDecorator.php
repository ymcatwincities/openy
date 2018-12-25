<?php

namespace Drupal\plugin\PluginDefinition;

/**
 * Provides a block plugin definition decorator.
 *
 * @ingroup Plugin
 */
class BlockPluginDefinitionDecorator extends ArrayPluginDefinitionDecorator {

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->arrayDefinition['admin_label'] = $label;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return isset($this->arrayDefinition['admin_label']) ? $this->arrayDefinition['admin_label'] : NULL;
  }

}

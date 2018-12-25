<?php

namespace Drupal\plugin\PluginDefinition;

/**
 * Provides a link (menu link, local action/task) plugin definition decorator.
 *
 * @ingroup Plugin
 */
class LinkPluginDefinitionDecorator extends ArrayPluginDefinitionDecorator {

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->arrayDefinition['title'] = $label;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return isset($this->arrayDefinition['title']) ? $this->arrayDefinition['title'] : NULL;
  }

}

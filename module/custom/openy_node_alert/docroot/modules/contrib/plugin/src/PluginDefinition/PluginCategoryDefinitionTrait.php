<?php

namespace Drupal\plugin\PluginDefinition;

/**
 * Implements \Drupal\plugin\PluginDefinition\PluginCategoryDefinitionInterface.
 *
 * @ingroup Plugin
 */
trait PluginCategoryDefinitionTrait {

  /**
   * The plugin category.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableString|string|null
   */
  protected $category;

  /**
   * Implements \Drupal\plugin\PluginDefinition\PluginCategoryDefinitionInterface::setCategory().
   */
  public function setCategory($category) {
    $this->category = $category;

    return $this;
  }

  /**
   * Implements \Drupal\plugin\PluginDefinition\PluginCategoryDefinitionInterface::getCategory().
   */
  public function getCategory() {
    return $this->category;
  }

}

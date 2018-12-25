<?php

namespace Drupal\plugin\Plugin\Field\FieldType;

/**
 * Provides a plugin collection field.
 *
 * @FieldType(
 *   default_widget = "plugin_selector:plugin_select_list",
 *   default_formatter = "plugin_label",
 *   id = "plugin",
 *   label = @Translation("Plugin collection"),
 *   category = @Translation("Plugin"),
 *   deriver = "\Drupal\plugin\Plugin\Field\FieldType\PluginCollectionItemDeriver",
 *   list_class = "\Drupal\plugin\Plugin\Field\FieldType\PluginCollectionItemList"
 * )
 */
class PluginCollectionItem extends PluginCollectionItemBase {

  /**
   * {@inheritdoc}
   */
  public function getPluginType() {
    /** @var \Drupal\plugin\PluginType\PluginTypeManagerInterface $plugin_type_manager */
    $plugin_type_manager = \Drupal::service('plugin.plugin_type_manager');

    return $plugin_type_manager->getPluginType($this->getPluginDefinition()['plugin_type_id']);
  }

}

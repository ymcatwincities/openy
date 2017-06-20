<?php

namespace Drupal\plugin\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\plugin\PluginDefinition\PluginLabelDefinitionInterface;

/**
 * A plugin bag field formatter.
 *
 * @FieldFormatter(
 *   id = "plugin_label",
 *   label = @Translation("Plugin label")
 * )
 *
 * @see plugin_field_formatter_info_alter()
 */
class PluginLabel extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $build = [];
    /** @var \Drupal\plugin\Plugin\Field\FieldType\PluginCollectionItemInterface $item */
    foreach ($items as $delta => $item) {
      $plugin_definition = $item->getPluginType()->ensureTypedPluginDefinition($item->getContainedPluginInstance()->getPluginDefinition());
      $build[$delta] = [
        '#markup' => $plugin_definition instanceof PluginLabelDefinitionInterface ? (string) $plugin_definition->getLabel() : $plugin_definition->getId(),
      ];
    }

    return $build;
  }

}

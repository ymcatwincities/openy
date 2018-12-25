<?php

namespace Drupal\plugin\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Formats a block plugin by building (and rendering) it.
 *
 * @FieldFormatter(
 *   field_types = {
 *     "plugin:block"
 *   },
 *   id = "plugin_block_built",
 *   label = @Translation("Rendered block")
 * )
 */
class BuiltBlock extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $build = [];
    /** @var \Drupal\plugin\Plugin\Field\FieldType\PluginCollectionItemInterface $item */
    foreach ($items as $delta => $item) {
      /** @var \Drupal\Core\Block\BlockPluginInterface $block */
      $block = $item->getContainedPluginInstance();
      $build[$delta] = $block->build();
    }

    return $build;
  }

}

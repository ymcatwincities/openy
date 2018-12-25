<?php

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * This plugin merges arrays together.
 *
 * @MigrateProcessPlugin(
 *   id = "merge"
 * )
 *
 * Use to merge several fields into one. In the following example, imagine a D7
 * node with a field_collections field and an image field that migrations were
 * written for to make paragraph entities in D8. We would like to add those
 * paragraph entities to the 'paragraphs_field'. Consider the following:
 *
 *  source:
 *    plugin: d7_node
 *  process:
 *    temp_body:
 *      plugin: iterator
 *      source: field_section
 *      process:
 *        target_id:
 *          plugin: migration
 *          migration: field_collection_field_section_to_paragraph
 *          source: value
 *    temp_images:
 *      plugin: iterator
 *      source: field_image
 *      process
 *        target_id:
 *          plugin: migration
 *          migration: image_entities_to_paragraph
 *          source: fid
 *    paragraphs_field:
 *      plugin: merge
 *      source:
 *        - @temp_body
 *        - @temp_images
 *  destination:
 *    plugin: 'entity:node'
 */
class Merge extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      throw new MigrateException('Input should be an array.');
    }
    $new_value = [];
    foreach($value as $item) {
      if (!is_array($item)) {
        throw new MigrateException('One of the items is not an array that can be merged.');
      }
      $new_value = array_merge($new_value, $item);
    }
    return $new_value;
  }

}

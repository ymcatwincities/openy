<?php

namespace Drupal\ygh_content\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Core\Database\Database;

/**
 * Migrate random paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "random_paragraphs"
 * )
 */
class RandomParagraphs extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Get all not used paragraphs.
    $db = Database::getConnection();
    $query = $db->select('paragraphs_item_field_data', 'p')
      ->fields('p')
      ->isNull('p.parent_id')
      ->execute();
    $data = [];
    while ($item = $query->fetchObject()) {
      $data[$item->type][] = $item->id;
    }

    // Loop over paragraphs type and use one random item.
    $items = [];
    foreach ($data as $type) {
      $rand_key = array_rand($type);
      $items[] = $type[$rand_key];
    }

    $result = [];
    foreach ($items as $item) {
      $result[] = [
        'entity' => [
          'target_id' => $item,
          'target_revision_id' => $item,
        ]
      ];
    }

    return $result;
  }

}

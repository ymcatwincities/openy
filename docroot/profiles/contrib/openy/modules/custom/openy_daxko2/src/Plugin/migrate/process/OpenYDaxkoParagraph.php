<?php

namespace Drupal\openy_daxko2\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Fills in link options with icon information.
 *
 * @MigrateProcessPlugin(
 *   id = "openy_daxko_paragraph"
 * )
 */
class OpenYDaxkoParagraph extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $types = explode(',', $value);
    $paragraps = [];
    foreach ($types as $type) {
      $paragraph = Paragraph::create(['type' => $type ]);
      $paragraph->isNew();
      $paragraph->save();
      $paragraps[] = [
        'target_id' => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ];
    }

    return $paragraps;
  }

  public function multiple() {
    return TRUE;
  }

}

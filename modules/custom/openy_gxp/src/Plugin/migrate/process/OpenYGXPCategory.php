<?php

namespace Drupal\openy_gxp\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Parse time and build proper properties on Session fields.
 *
 * @MigrateProcessPlugin(
 *   id = "openy_gxp_category"
 * )
 */
class OpenYGXPCategory extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = json_decode($value, TRUE);
//    {"title":"Cardio","description":"A great cardio experience...","activity":119}

    $nids = \Drupal::entityQuery('node')
      ->condition('title', $value['title'])
      ->condition('field_class_activity', $value['activity'])
      ->execute();

    if (!empty($nids)) {
      $node = Node::load(reset($nids));
    }
    else {
      $paragraps = [];
      foreach (['class_sessions', 'branches_popup_class'] as $type) {
        $paragraph = Paragraph::create(['type' => $type ]);
        $paragraph->isNew();
        $paragraph->save();
        $paragraps[] = [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ];
      }
      $node = Node::create([
        'uid' => 1,
        'lang' => 'und',
        'type' => 'class',
        'title' => $value['title'],
        'field_class_description' => [[
          'value' => $value['description'],
          'format' => 'full_html'
        ]],
        'field_class_activity' => [['target_id' => $value['activity']]],
        'field_content' => $paragraps,
      ]);
      $node->save();
    }

    return [
      'target_id' => $node->id(),
      'target_revision_id' => $node->getRevisionId(),
    ];
  }

}

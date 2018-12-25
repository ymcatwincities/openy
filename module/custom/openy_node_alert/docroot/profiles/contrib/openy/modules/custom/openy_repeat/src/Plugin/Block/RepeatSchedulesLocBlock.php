<?php

namespace Drupal\openy_repeat\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Repeat Schedules Locations' block.
 *
 * @Block(
 *   id = "repeat_schedules_loc_block",
 *   admin_label = @Translation("Repeat Schedules Locations Block"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class RepeatSchedulesLocBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $sql = "SELECT DISTINCT nd.title as location 
            FROM {node} n
            INNER JOIN node__field_session_location l ON n.nid = l.entity_id AND l.bundle = 'session'
            INNER JOIN node_field_data nd ON l.field_session_location_target_id = nd.nid
            WHERE n.type = 'session'";
    $connection = \Drupal::database();
    $query = $connection->query($sql);
    $locations = $query->fetchCol();

    return [
      '#theme' => 'openy_repeat_schedule_locations',
      '#locations' => $locations,
    ];
  }

}

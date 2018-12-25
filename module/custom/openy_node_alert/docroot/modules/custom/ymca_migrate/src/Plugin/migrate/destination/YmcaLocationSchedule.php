<?php

namespace Drupal\ymca_migrate\Plugin\migrate\destination;

use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;

/**
 * Defines destination plugin.
 *
 * @MigrateDestination(
 *   id = "ymca_location_schedule"
 * )
 */
class YmcaLocationSchedule extends DestinationBase {

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $nid = $row->getSourceProperty('nid');

    /* @var Node $node */
    $node = Node::load($nid);
    $node->set('field_schedule_content', [
      'value' => $row->getSourceProperty('schedule_content'),
      'format' => 'full_html',
    ]);
    $node->set('field_schedule_documents', $row->getSourceProperty('schedule_documents'));
    $node->save();

    return ['nid' => $nid];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'nid' => $this->t('The node ID'),
      'schedule_content' => $this->t('The schedule content.'),
      'schedule_documents' => $this->t('The schedule documents.'),
    ];
  }

}

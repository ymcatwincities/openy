<?php

/**
 * @file
 * Contains destination plugin for Draggable Views database table.
 */

namespace Drupal\draggableviews\Plugin\migrate\destination;

use Drupal\Core\Database\Database;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Row;

/**
 * Defines destination plugin for Draggableviews.
 *
 * @MigrateDestination(
 *   id = "draggableviews"
 * )
 */
class DraggableViews extends DestinationBase {

  /**
   * Constructs an entity destination plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param MigrationInterface $migration
   *   The migration.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $record = [
      'view_name' => $row->getDestinationProperty('view_name'),
      'view_display' => $row->getDestinationProperty('view_display'),
      'args' => $row->getDestinationProperty('args'),
      'entity_id' => $row->getDestinationProperty('entity_id'),
      'weight' => $row->getDestinationProperty('weight'),
      'parent' => $row->getDestinationProperty('parent'),
    ];
    $result = Database::getConnection()->insert('draggableviews_structure')->fields($record)->execute();
    return array($result);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'dvid' => [
        'type' => 'integer',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'dvid' => $this->t('The primarty identifier'),
      'view_name' => $this->t('The view name.'),
      'view_display' => $this->t('The view display.'),
      'args' => $this->t('The arguments.'),
      'entity_id' => $this->t('The entity id.'),
      'weight' => $this->t('The order weight.'),
      'parent' => $this->t('The parent entity id.'),
    ];
  }

}

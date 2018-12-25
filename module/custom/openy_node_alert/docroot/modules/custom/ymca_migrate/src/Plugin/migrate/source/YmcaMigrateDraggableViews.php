<?php

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for Draggable Views data.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_draggableviews"
 * )
 */
class YmcaMigrateDraggableviews extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('legacy__node_article', 'b')
      ->fields('b', ['id', 'parent', 'weight']);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Article id'),
      'view_name' => $this->t('The view name'),
      'view_display' => $this->t('The view display'),
      'args' => $this->t('The arguments'),
      'entity_id' => $this->t('The entity id'),
      'weight' => $this->t('The weight'),
      'parent' => $this->t('The parent id'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
        'alias' => 'b',
      ],
    ];
  }

}

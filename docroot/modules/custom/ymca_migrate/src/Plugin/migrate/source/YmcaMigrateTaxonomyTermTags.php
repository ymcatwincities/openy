<?php

/**
 * @file
 * Contains \Drupal\ymca_migrate\Plugin\migrate\source\YmcaMigrateTaxonomyTermTags.
 */

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for taxonomy_term:tags content.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_taxonomy_term_tags"
 * )
 */
class YmcaMigrateTaxonomyTermTags extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('shared_tags', 'tags')
      ->fields('tags', ['tag_id', 'tag_name', 'top_raw_tag_name']);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'tag_id' => $this->t('Tag id'),
      'tag_name' => $this->t('Tag name'),
      'top_raw_tag_name' => $this->t('Raw tag name'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // All the data blow we could fetch by making additional SQL requests.
    $row->setSourceProperty(
      'raw_tag_name',
      trim($row->getSourceProperty('top_raw_tag_name'), ',')
    );

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'tag_id' => [
        'type' => 'integer',
        'alias' => 'tags',
      ],
    ];
  }

}

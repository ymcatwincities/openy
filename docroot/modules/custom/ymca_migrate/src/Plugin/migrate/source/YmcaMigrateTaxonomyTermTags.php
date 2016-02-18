<?php

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
    $query = $this->select('catalog_label', 'cpl')
      ->fields('cpl', ['label_id', 'value'])->isNotNull('category_id');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'label_id' => $this->t('Tag id'),
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
      $row->getSourceProperty('value')
    );
    $row->setSourceProperty(
      'top_raw_tag_name',
      $row->getSourceProperty('value')
    );

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'label_id' => [
        'type' => 'integer',
        'alias' => 'cpl',
      ],
    ];
  }

}

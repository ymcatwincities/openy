<?php

/**
 * @file
 * Contains \Drupal\ymca_migrate\Plugin\migrate\source\YmcaMigrateNodeArticle.
 */

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for node:article content.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_node_article"
 * )
 */
class YmcaMigrateNodeArticle extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('amm_site_page', 'p')
      ->fields(
        'p',
        [
          'site_page_id',
          'page_title',
        ]
      )
      ->condition(
        'site_page_id',
        [
          8652,
        ],
        'IN'
      );
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'site_page_id' => $this->t('Page ID'),
      'page_title' => $this->t('Page title'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'site_page_id' => [
        'type' => 'integer',
        'alias' => 'p',
      ],
    ];
  }

}

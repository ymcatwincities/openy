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
    $query = $this->select('legacy__node_article', 'b')
      ->fields('b', ['id', 'title', 'header_image']);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Article id'),
      'title' => $this->t('Article title'),
      'page_description' => $this->t('Page description'),
      'lead_description' => $this->t('Lead description'),
      'sidebar_navigation' => $this->t('Sidebar navigation'),
      'content' => $this->t('Content'),
      'sidebar' => $this->t('Sidebar'),
      'header_image' => $this->t('Header image id'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // All the data below we could fetch by making additional SQL requests.
    $row->setSourceProperty('page_description', 'Here page description...');
    $row->setSourceProperty('lead_description', 'Here lead description...');
    $row->setSourceProperty('sidebar_navigation', 1);
    $row->setSourceProperty('content', 'Here content...');
    $row->setSourceProperty('sidebar', 'Here sidebar...');

    return parent::prepareRow($row);
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

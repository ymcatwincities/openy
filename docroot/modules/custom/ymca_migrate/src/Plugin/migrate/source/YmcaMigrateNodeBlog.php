<?php

/**
 * @file
 * Contains \Drupal\ymca_migrate\Plugin\migrate\source\YmcaMigrateNodeArticle.
 */

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for node:blog content.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_node_blog"
 * )
 */
class YmcaMigrateNodeBlog extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('abe_blog_post', 'b')
      ->fields(
        'b',
        [
          'blog_post_id',
          'title',
          'created_on',
          'modified_on',
        ]
      )
      ->condition('blog_post_id', 856);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'blog_post_id' => $this->t('Blog post ID'),
      'title' => $this->t('Blog title'),
      'created_on' => $this->t('Creation time'),
      'modified_on' => $this->t('Modification time'),
      'content' => $this->t('Content'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Get all component data.
    $components = $this->select('abe_blog_post_component', 'c')
      ->fields('c')
      ->condition('blog_post_id', $row->getSourceProperty('blog_post_id'))
      ->execute()
      ->fetchAll();

    // The code below is temporary. Just for the test.
    foreach ($components as $item) {
      if ($item['component_type'] == 'rich_text' && $item['content_area_index'] == 2) {
        $row->setSourceProperty('content', $item['body']);
      }
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'blog_post_id' => [
        'type' => 'integer',
        'alias' => 'b',
      ],
    ];
  }

}

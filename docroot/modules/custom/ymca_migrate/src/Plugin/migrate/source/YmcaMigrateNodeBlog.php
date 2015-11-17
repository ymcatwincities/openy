<?php

/**
 * @file
 * Contains \Drupal\ymca_migrate\Plugin\migrate\source\YmcaMigrateNodeArticle.
 */

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\migrate\Entity\MigrationInterface;
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
      ->condition(
        'blog_post_id',
        [
          856,
          833,
          828,
          822,
          821,
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
      'blog_post_id' => $this->t('Blog post ID'),
      'title' => $this->t('Blog title'),
      'created_on' => $this->t('Creation time'),
      'modified_on' => $this->t('Modification time'),
      'content' => $this->t('Content'),
      'image' => $this->t('Teaser image'),
      'image_alt' => $this->t('Teaser image alt'),
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

    // Get components tree, where each component has its children.
    $components_tree = [];
    foreach ($components as $item) {
      if (is_null($item['parent_component_id'])) {
        $components_tree[$item['blog_post_component_id']] = $item;
      }
      else {
        $components_tree[$item['parent_component_id']]['children'][$item['blog_post_component_id']] = $item;
      }
    }

    // @todo Sort components withing the same area by weight.

    // Foreach each parent component and check if there is a mapping.
    foreach ($components_tree as $id => $item) {
      if ($property = self::getMap()[$item['content_area_index']][$item['component_type']]) {
        // Set appropriate source properties.
        $properties = $this->transform($property, $item);
        if (is_array($properties) && count($properties)) {
          foreach ($properties as $property_name => $property_value) {
            $row->setSourceProperty($property_name, $row->getSourceProperty($property_name) . $property_value);
          }
        }
      }
      else {
        // There is no item in our map. Skip row and throw an error.
        $this->idMap->saveMessage($this->getCurrentIds(), $this->t('Undefined component: @component', array('@component' => $id)), MigrationInterface::MESSAGE_ERROR);
        return FALSE;
      }
    }

    return parent::prepareRow($row);
  }

  /**
   * Transform component to property value.
   *
   * @param string $property
   *   Property name (field name).
   * @param array $component
   *   Component with children.
   *
   * @return array
   *   Array of source fields.
   */
  protected function transform($property, array $component) {
    // Here I'll just use switch statement.
    // As we have a lot of components and their logic is sophisticated I propose to use plugins.
    // Plugins could be reused within different migrations.
    $value = [];
    switch ($component['component_type']) {

      case 'image':
        // Set target image ID.
        $value['image'] = $component['body'];

        // Set alt.
        foreach ($component['children'] as $item) {
          if ($item['body'] == 'alt_text') {
            $value['image_alt'] = $item['extra_data_1'];
          }
        }
        break;

      default:
        $value = [$property => $component['body']];
    }

    return $value;
  }

  /**
   * Get area mappings.
   *
   * @return array
   *   Map of areas, component types and source fields.
   */
  public static function getMap() {
    return [
      1 => [
        'rich_text' => 'summary',
      ],
      2 => [
        'rich_text' => 'content',
      ],
      3 => [
        'image' => 'image',
      ]
    ];
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

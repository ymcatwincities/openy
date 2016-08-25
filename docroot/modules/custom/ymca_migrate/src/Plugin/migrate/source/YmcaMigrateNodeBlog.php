<?php

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\Core\State\StateInterface;
use Drupal\migrate_plus\Entity\MigrationInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;
use Drupal\ymca_migrate\Plugin\migrate\YmcaBlogComponentsTree;
use Drupal\ymca_migrate\Plugin\migrate\YmcaBlogsQuery;

/**
 * Source plugin for node:blog content.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_node_blog"
 * )
 */
class YmcaMigrateNodeBlog extends SqlBase {

  /**
   * Migration to be passed to child object.
   *
   * @var MigrationInterface
   */
  protected $migration;

  /**
   * Sign In menu item.
   */
  const DRUPAL_SIGN_IN_MENU_ITEM = 148;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state) {
    $this->migration = &$migration;
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $state
    );
    $this->state = $state;

  }

  /**
   * {@inheritdoc}
   */
  public function query() {

    // @todo push logger only to the child class.
    $ymca_blogs_query = YmcaBlogsQuery::init($this, $this->migration);

    return $ymca_blogs_query->getQuery();
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
      'blog_id' => $this->t('ID for blog'),
      'author' => $this->t('Author for the blog'),
      'term_id' => $this->t('Category Term Name')
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    $components_tree = YmcaBlogComponentsTree::init(array(), $this, $row)
      ->getTree();

    // Foreach each parent component and check if there is a mapping.
    foreach ($components_tree as $id => $item) {
      if (isset(self::getMap()[$item['content_area_index']])
        && isset(self::getMap()[$item['content_area_index']][$item['component_type']])
        && $property = self::getMap()[$item['content_area_index']][$item['component_type']]) {
        // Set appropriate source properties.
        $properties = $this->transform($property, $item);
        if (is_array($properties) && count($properties)) {
          foreach ($properties as $property_name => $property_value) {
            $row->setSourceProperty(
              $property_name,
              $row->getSourceProperty($property_name) . $property_value
            );
          }
        }
      }
      else {
        // There is no item in our map. Skip row and throw an error.
        $this->idMap->saveMessage(
          $this->getCurrentIds(),
          $this->t(
            'Undefined component in blog_post #@post: @component',
            array(
              '@component' => $id,
              '@post' => $row->getSourceProperty('blog_post_id')
            )
          ),
          MigrationInterface::MESSAGE_ERROR
        );
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
    // Here we'll just use switch statement.
    // As we have a lot of components and their logic is sophisticated I propose to use plugins.
    // Plugins could be reused within different migrations.
    $value = [];

    /* @var \Drupal\ymca_migrate\Plugin\migrate\YmcaReplaceTokens $replace_tokens */
    $replace_tokens = \Drupal::service('ymcareplacetokens.service');
    if (isset($component['body'])) {
      try {
        $component['body'] = $replace_tokens->processText($component['body']);
      }
      catch (\Exception $e) {
        $this->idMap->saveMessage(
          $this->getCurrentIds(),
          $this->t(
            'A problem with token replacements: blog_post_id: @blog, message: @message',
            array(
              '@blog' => $component['blog_post_id'],
              '@message' => $e->getMessage(),
            )
          ),
          MigrationInterface::MESSAGE_ERROR
        );
      }
    }

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
        break;
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

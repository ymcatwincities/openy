<?php

/**
 * @file
 * Contains \Drupal\ymca_migrate\Plugin\migrate\source\YmcaMigrateNodePage.
 */

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\Component\Utility\Html;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Entity\MigrationInterface;
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

  /*
   * \Drupal\ymca_migrate\AmmComponentsTree
   */
  protected $blogCtTree;

  /**
   * Migration to be passed to child object.
   *
   * @var MigrationInterface.
   */
  protected $migration;

  /**
   * Sign In menu item.
   */
  const DRUPAL_SIGN_IN_MENU_ITEM = 148;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration,
    StateInterface $state
  ) {
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
      if ($property = self::getMap(
      )[$item['content_area_index']][$item['component_type']]
      ) {
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
        preg_match_all(
          "/<a.*href=\"{{internal_page_link_[0-9][0-9]*}}\">.*<\/a>/mU",
          $component['body'],
          $test
        );
        if (empty($test) || empty($test[0])) {
          $value = [$property => $component['body']];
          break;
        }
        $html = Html::load($component['body']);
        foreach ($test as $id => $matched) {
          if (empty($matched)) {
            continue;
          }
          foreach ($matched as $mid => $match) {
            preg_match('/\>(.*?)\</mU', $match, $link_label);
            if (!empty($link_label)) {
              $link_label = $link_label[1];
            }
            else {
              $link_label = '';
            }

            preg_match_all(
              "/\{{internal_page_link_(.*?)\}}/",
              $match,
              $source_page_ids
            );
            $source_page_id = $source_page_ids[1][0];

            $p = $html->createElement('drupal-entity');
            $p->setAttribute('data-align', 'none');
            $p->setAttribute('data-embed-button', 'menu_link');
            $p->setAttribute(
              'data-entity-embed-display',
              'entity_reference:entity_reference_label_url'
            );
            $p->setAttribute(
              'data-entity-embed-settings',
              htmlspecialchars_decode(
                '{&quot;route_link&quot;:1,&quot;route_title&quot;:&quot;' . $link_label . '&quot;}'
              )
            );
            $p->setAttribute('data-entity-type', 'menu_link_content');
            /* @var \Drupal\ymca_migrate\Plugin\migrate\YmcaTokensMap $ymca_tokens_map */
            $ymca_tokens_map = \Drupal::service('ymcatokensmap.service');
            $menu_id = $ymca_tokens_map->getMenuId($source_page_id);
            if ($menu_id === FALSE) {
              $p->setAttribute(
                'data-entity-uuid',
                '6b6c92d5-abc0-4384-8800-cfaed6750738'
              );
              $p->setAttribute(
                'data-entity-id', self::DRUPAL_SIGN_IN_MENU_ITEM
              );
              $p->setAttribute(
                'data-entity-label', t(
                  'Lost link to internal Page'
                )
              );
              $this->idMap->saveMessage(
                $this->getCurrentIds(),
                $this->t(
                  'Lost tokenizer link to internal Page for blog_post #@post: @component',
                  array(
                    '@component' => $component['rich_text'],
                    '@post' => $component['blog_post_id']
                  )
                ),
                MigrationInterface::MESSAGE_ERROR
              );
            }
            else {
              $p->setAttribute('data-entity-id', $menu_id);
              $p->setAttribute('data-entity-label', $link_label);
              $menu_link_entity = $menu_link_uuid = \Drupal::entityManager()->getStorage('menu_link_content')->load($menu_id);
              $menu_link_uuid = $menu_link_entity->uuid();
              $p->setAttribute(
                'data-entity-uuid',
                $menu_link_uuid
              );
            }

            $html->appendChild($p);
            $entity_embed_widget = $p->C14N();
            $component['body'] = str_replace(
              $match,
              $entity_embed_widget,
              $component['body']
            );
          }
        }
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

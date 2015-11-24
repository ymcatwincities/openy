<?php

/**
 * @file
 * Contains \Drupal\ymca_migrate\Plugin\migrate\source\YmcaMigrateNodePage.
 */

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\block_content\Entity\BlockContent;
use Drupal\file_entity\Entity\FileEntity;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;
use Drupal\ymca_migrate\Plugin\migrate\YmcaPageTree;

/**
 * Source plugin for node:article content.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_node_article"
 * )
 */
class YmcaMigrateNodePage extends SqlBase {

  // @codingStandardsIgnoreStart
  const THEME_INTERNAL_CATEGORY_AND_DETAIL = 22;
  // @codingStandardsIgnoreEnd

  /**
   * {@inheritdoc}
   */
  public function query() {
    // @codingStandardsIgnoreStart
    $query = $this->select('amm_site_page', 'p')
      ->fields(
        'p',
        [
          'site_page_id',
          'page_title',
          'theme_id',
        ]
      )
      ->condition(
        'site_page_id',
        [
          // Pages with single component type. Theme THEME_INTERNAL_CATEGORY_AND_DETAIL.
          5264,
          5234,
          22703,
          4803,
          5266,
          15462,
          5098,
          5267,
          5295,
          18074,
          18081,
          5297,
          15752,
          5298,
          5245,
          5284,
          5300,
          5285,
          6871,
          5286,
          5304,
          6130,
          6872,
          5250,
          5287,
          5305,
          6136,
          5254,
          6874,
          13767,
          16870,
          19147,
          5290,
          6876,
          6828,
          6877,
          // Pages with 2 component type. Theme THEME_INTERNAL_CATEGORY_AND_DETAIL.
          4811,
          5105,
          13828,
          15843,
          23217,
          4670,
          4812,
          6873,
          13830,
          17304,
          18891,
          23439,
          24946,
          4813,
          5185,
          5204,
          13832,
          15853,
          17305,
          15855,
          17307,
          4815,
          5152,
          6827,
          13836,
          17308,
          21306,
          22699,
          5232,
          17309,
          21311,
          22700,
          5133,
          5172,
          5210,
          6714,
          17310,
          5096,
          5134,
          5191,
          5265,
          17323,
          19440,
          25185,
          4941,
          5097,
          5237,
          15862,
          17064,
          17324,
          24462,
          4942,
          5159,
          5238,
          6735,
          22438,
          4805,
          4943,
          5099,
          5115,
          5239,
          6853,
          15872,
          22463,
          25247,
          5217,
          5241,
          15873,
          18145,
          5139,
          5179,
          5198,
          5242,
          24732,
          4808,
          12856,
          14283,
          15840,
          22728,
          4809,
          5145,
          5164,
          20068,
          24941,
          4810,
          5124,
          5201,
          5222,
          24055,
          // Pages for menu migration.
          '4802',
          '4804',
          '4805',
          '4806',
          '4807',
          '4747',
          '20256',
          '8601',
          '4748',
          '4750',
          '15737',
          '15840',
          '15841',
          '15842',
          '15739',
          '22710',
          '22712',
          '22713',
          '23010',
          '22714',
          '23694',
          '23692',
          '24048',
          '23691',
          '23695',
          '5303',
          '5304',
          '5305',
          '5283',
          '5284'
        ],
        'IN'
      );
    // @codingStandardsIgnoreEnd
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'site_page_id' => $this->t('Page ID'),
      'page_title' => $this->t('Page title'),
      'theme_id' => $this->t('Theme ID'),
      'field_content' => $this->t('Content'),
      'field_lead_description' => $this->t('Content'),
      'field_header_button' => $this->t('Header button'),
      'field_header_variant' => $this->t('Header variant'),
      'field_sidebar' => $this->t('Sidebar'),
      'field_secondary_sidebar' => $this->t('Secondary sidebar'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    // Get components tree, where each component has its children.
    $components_tree = YmcaPageTree::init(array(), $this->getDatabase(), $row)
      ->getTree();

    // Foreach each parent component and check if there is a mapping.
    foreach ($components_tree as $id => $item) {
      if ($property = $this->checkMap(
        $row->getSourceProperty('theme_id'),
        $item['content_area_index'],
        $item['component_type']
      )
      ) {
        // Set appropriate source properties.
        $properties = $this->transform($property, $item);
        if (is_array($properties) && count($properties)) {
          foreach ($properties as $property_name => $property_value) {
            // Some components may go to single field in Drupal, so take care of them.
            if ($old_value = $row->getSourceProperty($property_name)) {
              // Currently we are merging only properties that have 'value' key. Otherwise log message.
              if (!array_key_exists('value', $old_value)) {
                $this->idMap->saveMessage(
                  $this->getCurrentIds(),
                  $this->t(
                    '[DEV] Possible problem with merging multiple components on the page. (Page ID: @page, Field Name: @field).',
                    [
                      '@page' => $item['site_page_id'],
                      '@field' => $property,
                    ]
                  ),
                  MigrationInterface::MESSAGE_WARNING
                );
              }
              // Do our merge here.
              $new_value = $old_value;
              $new_value['value'] .= $property_value['value'];
            }
            else {
              // Here only one component for a field. Write it.
              $new_value = $property_value;
            }
            // Finally, set our property.
            $row->setSourceProperty($property_name, $new_value);
          }
        }
      }
      else {
        // Check for recursion. Probably it should be done in other migrations, like expander block, subcontent.
        // @todo Recheck this.
        if (!isset($item['content_area_index']) || !isset($item['component_type'])) {
          $this->idMap->saveMessage(
            $this->getCurrentIds(),
            $this->t(
              '[DEV] It seems to be a recursion on the page #@page.',
              ['@page' => $row->getSourceProperty('site_page_id')]
            ),
            MigrationInterface::MESSAGE_ERROR
          );
        }
        else {
          // There is no item in our map. Set the message.
          $this->idMap->saveMessage(
            $this->getCurrentIds(),
            $this->t(
              '[DEV] Undefined component in the page #@page: @component (@map)',
              [
                '@component' => $id,
                '@page' => $row->getSourceProperty('site_page_id'),
                '@map' => $this->getThemeName(
                    $row->getSourceProperty('theme_id')
                  ) . ':' . $item['content_area_index'] . ':' . $item['component_type'],
              ]
            ),
            MigrationInterface::MESSAGE_ERROR
          );
        }
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
    $value = [];
    switch ($component['component_type']) {
      case 'link':
        $value['field_header_variant'] = 'button';
        $value['field_header_button'] = [
          'uri' => $this->getAttributeData('url', $component),
          'title' => $this->getAttributeData('text', $component),
        ];
        break;

      case 'rich_text':
        $value[$property] = [
          'value' => $component['body'],
          'format' => 'full_html',
        ];
        break;

      case 'text':
        $value[$property] = [
          'value' => $component['body'],
          'format' => 'full_html',
        ];
        break;

      case 'content_block_join':
        // Check for the children for the component. If more then 1 let's log a message.
        if (count($component['children']) > 1) {
          $this->idMap->saveMessage(
            $this->getCurrentIds(),
            $this->t(
              '[DEV] Component content_block_join (id: @component) has more than 1 child on page: #@page',
              [
                '@component' => $component['site_page_component_id'],
                '@page' => $component['site_page_id']
              ]
            ),
            MigrationInterface::MESSAGE_NOTICE
          );
        }
        // Get joined component id.
        $joined_id = $this->getAttributeData(
          'joined_content_block_component_id',
          $component
        );
        $parent = $this->getComponentByParent($joined_id);
        // If parent is missing log it.
        if (!$parent) {
          $this->idMap->saveMessage(
            $this->getCurrentIds(),
            $this->t(
              '[DEV] Component content_block_join (id: @component) has empty join on page: #@page',
              [
                '@component' => $component['site_page_component_id'],
                '@page' => $component['site_page_id']
              ]
            ),
            MigrationInterface::MESSAGE_NOTICE
          );
          return NULL;
        }

        // List of known components to join.
        $available = [
          'rich_text',
          'image',
          'html_code',
        ];

        // For now just take care of available components. If anything else log a message.
        // @todo There are definitely another types like html_code, etc... Do it.

        if (!in_array($parent['component_type'], $available)) {
          $this->idMap->saveMessage(
            $this->getCurrentIds(),
            $this->t(
              '[DEV] Component content_block_join (id: @component) has unknown join (@type) on page: #@page',
              [
                '@component' => $component['site_page_component_id'],
                '@type' => $parent['component_type'],
                '@page' => $component['site_page_id']
              ]
            ),
            MigrationInterface::MESSAGE_ERROR
          );
          return NULL;
        }

        // Finally, return body.
        $value[$property] = [
          'value' => $parent['body'],
          'format' => 'full_html',
        ];
        break;

      case 'image':
        $alt = $this->getAttributeData('alt_text', $component);
        $asset_id = $this->getAttributeData('asset_id', $component);
        // For speed up the process use specific migrated asset id.
        // @todo Set proper asset id.
        $asset_id = 11712;
        // Get file.
        $destination = $this->getDestinationId(
          $asset_id,
          'ymca_migrate_file_image'
        );

        // For field_header_image we should upload image as a field.
        if ($property == 'field_header_image') {
          $value[$property] = [
            'target_id' => $destination,
          ];
          $value['field_header_variant'] = 'image';
        }
        else {
          // Here we use just inline image.
          /** @var FileEntity $file */
          $file = \Drupal::entityManager()->getStorage('file')->load(
            $destination
          );
          $url = parse_url(file_create_url($file->getFileUri()));
          $string = '<p><img alt="%s" data-entity-type="file" data-entity-uuid="%s" src="%s" /></p>';
          $value[$property] = [
            'value' => sprintf($string, $alt, $file->uuid(), $url['path']),
            'format' => 'full_html',
          ];
        }
        break;

      case 'code_block':
        $id = $this->getAttributeData('code_block_id', $component);
        $destination = $this->getDestinationId(
          $id,
          'ymca_migrate_block_content_code_block'
        );
        /** @var BlockContent $block */
        $block = \Drupal::entityManager()->getStorage('block_content')->load(
          $destination
        );
        $string = '<drupal-entity data-align="none" data-embed-button="block" data-entity-embed-display="entity_reference:entity_reference_entity_view" data-entity-embed-settings="{&quot;view_mode&quot;:&quot;full&quot;}" data-entity-id="%u" data-entity-label="Block" data-entity-type="block_content" data-entity-uuid="%s"></drupal-entity>';
        $value[$property] = [
          'value' => sprintf($string, $block->id(), $block->uuid()),
          'format' => 'full_html',
        ];
        break;

      case 'headline';
        $tag = $component['extra_data_1'];
        $string = '<%s>%s</%s>';
        $value[$property] = [
          'value' => sprintf($string, $tag, $component['body'], $tag),
          'format' => 'full_html',
        ];
        break;

      case 'html_code':
        $value[$property] = [
          'value' => $component['body'],
          'format' => 'full_html',
        ];
        break;

      case 'line_break':
        $breaks = '';
        for ($i = 0; $i < $component['body']; $i++) {
          $breaks .= '<br />';
        }
        $value[$property] = [
          'value' => $breaks,
          'format' => 'full_html',
        ];
        break;

      case 'textpander':
        $string = '<article class="panel panel-default textpander"><div class="panel-heading"><div class="panel-title">%s</div></div><div class="panel-collapse-in"><div class="panel-body">%s</div></div></article>';
        $value[$property] = [
          'value' => sprintf(
            $string,
            $this->getAttributeData('headline', $component),
            $component['body']
          ),
          'format' => 'full_html',
        ];
        break;

      case 'blockquote':
        $string = '<blockquote class="blockquote"><p>%s</p><small>%s</small></blockquote>';
        $value[$property] = [
          'value' => sprintf($string, $component['body'], $component['href']),
          'format' => 'full_html',
        ];
        break;

      default:
        $value[$property] = $component['body'];
    }

    return $value;
  }

  /**
   * Get destination ID by the source ID for a migration.
   *
   * This method is a quick and dirty one, but for now it's doing the job.
   * Should be rewritten by using Migrate API.
   *
   * @param mixed $source_id
   *   Source ID.
   * @param string $migration_id
   *   Migration ID.
   *
   * @return mixed
   *   Destination ID of FALSE.
   *
   * @todo Rewrite the method using Migrate API.
   */
  protected function getDestinationId($source_id, $migration_id) {
    $table = 'migrate_map_' . $migration_id;
    return db_select($table, 'm')
      ->fields('m', ['destid1'])
      ->condition('m.sourceid1', $source_id)
      ->execute()
      ->fetchField();
  }

  /**
   * Get extra data from components child.
   *
   * @param string $attribute
   *   Attribute name.
   * @param array $component
   *   Component.
   *
   * @return mixed
   *   Extra data.
   */
  protected function getAttributeData($attribute, array $component) {
    foreach ($component['children'] as $item) {
      if ($item['body'] == $attribute) {
        return $item['extra_data_1'];
      }
    }
    return NULL;
  }

  /**
   * Get component by parent ID.
   *
   * @param int $id
   *   Component ID.
   *
   * @return mixed
   *   Component array or FALSE.
   */
  protected function getComponentByParent($id) {
    $result = $this->select('amm_site_page_component', 'c')
      ->fields('c')
      ->condition('parent_component_id', $id)
      ->execute()
      ->fetch();
    return $result;
  }

  /**
   * Get area mappings.
   *
   * @return array
   *   Map of areas, component types and source fields. Meaning:
   *   - first key: theme_id
   *   - second key: content_area_index
   *   - third key: component_type
   *   - third value: source field (should have the same name with destination)
   */
  public static function getMap() {
    return [
      self::THEME_INTERNAL_CATEGORY_AND_DETAIL => [
        1 => [
          'rich_text' => 'field_lead_description',
          'content_block_join' => 'field_lead_description',
          'headline' => 'field_lead_description',
        ],
        2 => [
          'rich_text' => 'field_secondary_sidebar',
          'content_block_join' => 'field_secondary_sidebar',
        ],
        3 => [
          'rich_text' => 'field_content',
          'text' => 'field_content',
          'content_block_join' => 'field_content',
          'code_block' => 'field_content',
          'headline' => 'field_content',
          'html_code' => 'field_content',
          'line_break' => 'field_content',
          'textpander' => 'field_content',
          'blockquote' => 'field_content',
        ],
        4 => [
          'content_block_join' => 'field_sidebar',
          'rich_text' => 'field_sidebar',
          'image' => 'field_sidebar',
          'code_block' => 'field_sidebar',
          'html_code' => 'field_sidebar',
          'line_break' => 'field_sidebar',
          'blockquote' => 'field_sidebar',
        ],
        100 => [
          'link' => 'field_header_button',
          'image' => 'field_header_image',
        ],
      ],
    ];
  }

  /**
   * Get theme name.
   *
   * @param int $theme_id
   *   Theme ID.
   *
   * @return mixed
   *   Theme name or FALSE.
   */
  protected function getThemeName($theme_id) {
    return $this->select('amm_theme', 't')
      ->fields('t', ['theme_name'])
      ->condition('t.theme_id', $theme_id)
      ->execute()
      ->fetchField();
  }

  /**
   * Checks the map.
   *
   * @param int $theme_id
   *   Theme id.
   * @param int $content_area_index
   *   Content area index.
   * @param string $component_type
   *   Component type.
   *
   * @return mixed
   *   Get mapped field or FALSE.
   */
  protected function checkMap($theme_id, $content_area_index, $component_type) {
    $map = self::getMap();

    // Check theme_id.
    if (!array_key_exists($theme_id, $map)) {
      return FALSE;
    }

    // Check content_area_index.
    if (!array_key_exists($content_area_index, $map[$theme_id])) {
      return FALSE;
    }

    // Finally get the result.
    if (array_key_exists(
      $component_type,
      $map[$theme_id][$content_area_index]
    )) {
      return $map[$theme_id][$content_area_index][$component_type];
    }

    return FALSE;
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

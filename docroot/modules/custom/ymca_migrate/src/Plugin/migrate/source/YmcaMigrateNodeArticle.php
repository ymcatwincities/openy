<?php

/**
 * @file
 * Contains \Drupal\ymca_migrate\Plugin\migrate\source\YmcaMigrateNodeArticle.
 */

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\block_content\Entity\BlockContent;
use Drupal\file_entity\Entity\FileEntity;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;
use Drupal\migrate\Entity\MigrationInterface;

/**
 * Source plugin for node:article content.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_node_article"
 * )
 */
class YmcaMigrateNodeArticle extends SqlBase {

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
    // Get all component data.
    $components = $this->select('amm_site_page_component', 'c')
      ->fields('c')
      ->condition('site_page_id', $row->getSourceProperty('site_page_id'))
      ->orderby('content_area_index', 'ASC')
      ->orderby('sequence_index', 'ASC')
      ->execute()
      ->fetchAll();

    // Get components tree, where each component has its children.
    $components_tree = [];

    // Write parents.
    foreach ($components as $item) {
      if (is_null($item['parent_component_id'])) {
        $components_tree[$item['site_page_component_id']] = $item;
      }
    }

    // Write children.
    foreach ($components as $item) {
      if (!is_null($item['parent_component_id'])) {
        $components_tree[$item['parent_component_id']]['children'][$item['site_page_component_id']] = $item;
      }
    }

    // Foreach each parent component and check if there is a mapping.
    foreach ($components_tree as $id => $item) {
      if ($property = $this->checkMap($row->getSourceProperty('theme_id'), $item['content_area_index'], $item['component_type'])) {
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
                    'Possible problem with merging multiple components on the page. (Page ID: @page, Field Name: @field).',
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
        // There is no item in our map. Set the message.
        $this->idMap->saveMessage(
          $this->getCurrentIds(),
          $this->t(
            'Undefined component in the page #@page: @component (@map)',
            [
              '@component' => $id,
              '@page' => $row->getSourceProperty('site_page_id'),
              '@map' => $row->getSourceProperty('theme_id') . ':' . $item['content_area_index'] . ':' . $item['component_type'],
            ]
          ),
          MigrationInterface::MESSAGE_ERROR
        );
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
              'Component content_block_join (id: @component) has more than 1 child on page: #@page',
              [
                '@component' => $component['site_page_component_id'],
                '@page' => $component['site_page_id']
              ]
            ),
            MigrationInterface::MESSAGE_NOTICE
          );
        }
        // Get joined component id.
        $joined_id = $this->getAttributeData('joined_content_block_component_id', $component);
        $parent = $this->getComponentByParent($joined_id);
        // If parent is missing log it.
        if (!$parent) {
          $this->idMap->saveMessage(
            $this->getCurrentIds(),
            $this->t(
              'Component content_block_join (id: @component) has empty join on page: #@page',
              [
                '@component' => $component['site_page_component_id'],
                '@page' => $component['site_page_id']
              ]
            ),
            MigrationInterface::MESSAGE_NOTICE
          );
          return NULL;
        }
        // For now just take care of rich_text. If anything else log a message.
        // @todo There are definitely another types like html_code, etc... Do it.
        if ($parent['component_type'] != 'rich_text') {
          $this->idMap->saveMessage(
            $this->getCurrentIds(),
            $this->t(
              'Component content_block_join (id: @component) has unknown join (@type) on page: #@page',
              [
                '@component' => $component['site_page_component_id'],
                '@type' => $parent['component_type'],
                '@page' => $component['site_page_id']
              ]
            ),
            MigrationInterface::MESSAGE_NOTICE
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
        $destination = $this->getDestinationId($asset_id, 'ymca_migrate_file_image');
        /** @var FileEntity $file */
        $file = \Drupal::entityManager()->getStorage('file')->load($destination);
        $url = parse_url(file_create_url($file->getFileUri()));
        $string = '<p><img alt="%s" data-entity-type="file" data-entity-uuid="%s" src="%s" /></p>';
        $value[$property] = [
          'value' => sprintf($string, $alt, $file->uuid(), $url['path']),
          'format' => 'full_html',
        ];
        break;

      case 'code_block':
        $id = $this->getAttributeData('code_block_id', $component);
        $destination = $this->getDestinationId($id, 'ymca_migrate_block_content_code_block');
        /** @var BlockContent $block */
        $block = \Drupal::entityManager()->getStorage('block_content')->load($destination);
        $string = '<drupal-entity data-align="none" data-embed-button="block" data-entity-embed-display="entity_reference:entity_reference_entity_view" data-entity-embed-settings="{&quot;view_mode&quot;:&quot;full&quot;}" data-entity-id="%u" data-entity-label="Block" data-entity-type="block_content" data-entity-uuid="%s"></drupal-entity>';
        $value[$property] = [
          'value' => sprintf($string, $block->id(), $block->uuid()),
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
        ],
        4 => [
          'content_block_join' => 'field_sidebar',
          'rich_text' => 'field_sidebar',
          'image' => 'field_sidebar',
        ],
        100 => [
          'link' => 'field_header_button',
        ],
      ],
    ];
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
    if (array_key_exists($component_type, $map[$theme_id][$content_area_index])) {
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

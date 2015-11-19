<?php

/**
 * @file
 * Contains \Drupal\ymca_migrate\Plugin\migrate\source\YmcaMigrateNodeArticle.
 */

namespace Drupal\ymca_migrate\Plugin\migrate\source;

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
          8652,
          4563,
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
      'theme_id' => $this->t('Theme ID'),
      'field_content' => $this->t('Content'),
      'field_lead_description' => $this->t('Content'),
      'field_header_button' => $this->t('Header button'),
      'field_header_variant' => $this->t('Header variant'),
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
      ->execute()
      ->fetchAll();

    // Get components tree, where each component has its children.
    $components_tree = [];
    foreach ($components as $item) {
      if (is_null($item['parent_component_id'])) {
        $components_tree[$item['site_page_component_id']] = $item;
      }
      else {
        $components_tree[$item['parent_component_id']]['children'][$item['site_page_component_id']] = $item;
      }
    }

    // @todo Sort components withing the same area by weight if more than one item.

    // Foreach each parent component and check if there is a mapping.
    foreach ($components_tree as $id => $item) {
      if ($property = $this->checkMap($row->getSourceProperty('theme_id'), $item['content_area_index'], $item['component_type'])) {
        // Set appropriate source properties.
        $properties = $this->transform($property, $item);
        if (is_array($properties) && count($properties)) {
          foreach ($properties as $property_name => $property_value) {
            // @todo Add value to previous one if there are multiple components.
            $row->setSourceProperty($property_name, $property_value);
          }
        }
      }
      else {
        // There is no item in our map. Set the message.
        $this->idMap->saveMessage(
          $this->getCurrentIds(),
          $this->t(
            'Undefined component in the page #@page: @component',
            ['@component' => $id, '@page' => $row->getSourceProperty('site_page_id')]
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
        if ($property == 'field_header_button') {
          $value['field_header_variant'] = 'button';
          $value['field_header_button'] = [
            'uri' => $this->getAttributeData('url', $component),
            'title' => $this->getAttributeData('text', $component),
          ];
        }
        break;

      case 'rich_text':
        $value[$property] = [
          'value' => $component['body'],
          'format' => 'full_html',
        ];
        break;

      case 'content_block_join':
        if ($property == 'field_sidebar') {
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
        }
        break;

      default:
        $value[$property] = $component['body'];
    }

    return $value;
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
        1 => ['rich_text' => 'field_lead_description'],
        3 => ['rich_text' => 'field_content'],
        4 => ['content_block_join' => 'field_sidebar'],
        100 => ['link' => 'field_header_button'],
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

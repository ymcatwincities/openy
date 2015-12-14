<?php

/**
 * @file
 * Contains base plugin for node migrations.
 */

namespace Drupal\ymca_migrate\Plugin\migrate;

use Drupal\block_content\Entity\BlockContent;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Base source plugin for nodes.
 */
abstract class YmcaMigrateNodeBase extends SqlBase {

  use YmcaMigrateTrait;

  // @codingStandardsIgnoreStart
  const THEME_INTERNAL_CATEGORY_AND_DETAIL = 22;
  const YMCA_2013_LOCATIONS_CAMPS = 18;
  const YMCA_2013_LOCATION_HOME = 24;
  // @codingStandardsIgnoreEnd

  /**
   * Tokens replacement service.
   *
   * @var \Drupal\ymca_migrate\Plugin\migrate\YmcaReplaceTokens.
   */
  public $replaceTokens;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $migration, $state) {
    $this->replaceTokens = \Drupal::service('ymcareplacetokens.service');
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $page_id = $row->getSourceProperty('site_page_id');
    $theme_id = $row->getSourceProperty('theme_id');

    // Get components tree for the page, where each component has its children.
    $tree_builder = new YmcaComponentTreeBuilder($page_id, $this->getDatabase());
    $components_tree = $tree_builder->getTree();

    // Foreach each parent component and check if there is a mapping.
    foreach ($components_tree as $id => $item) {
      if ($property = $this->checkMap($theme_id, $item['content_area_index'], $item['component_type'])) {
        // Set appropriate source properties.
        $properties = $this->transform($property, $item);
        if (is_array($properties) && count($properties)) {
          foreach ($properties as $property_name => $property_value) {
            // Some components may go to multiple fields in Drupal, so take care of them.
            if ($old_value = $row->getSourceProperty($property_name)) {
              // Currently we are merging only properties that are array of arrays or have 'value' key. Otherwise log message.
              if (!array_key_exists('value', $old_value) || !is_array($old_value[0])) {
                $this->idMap->saveMessage(
                  $this->getCurrentIds(),
                  $this->t(
                    '[DEV] Possible problem with merging multiple components. (Page ID: @page, Field Name: @field).',
                    [
                      '@page' => $page_id,
                      '@field' => $property,
                    ]
                  ),
                  MigrationInterface::MESSAGE_WARNING
                );
              }
              // Do our merge here.
              if (array_key_exists('value', $old_value)) {
                // Here for simple values.
                $new_value = $old_value;
                $new_value['value'] .= $property_value['value'];
              }
              else {
                // Here for arrays.
                $new_value = array_merge($old_value, $property_value);
              }

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
            '[DEV] Undefined component in the page #@page: @component (@map)',
            [
              '@component' => $id,
              '@page' => $page_id,
              '@map' => sprintf(
                '%s [id:%d]:%d:%s',
                $this->getThemeName($theme_id),
                $theme_id,
                $item['content_area_index'],
                $item['component_type']
              ),
            ]
          ),
          MigrationInterface::MESSAGE_ERROR
        );
      }
    }

    // Some pages have NULL title, so use page name.
    if (!$row->getSourceProperty('page_title')) {
      $row->setSourceProperty('page_title', $row->getSourceProperty('page_name'));
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
        // Add specific behaviour for field_main_promos.
        if ($property == 'field_main_promos') {
          // Here we should parse HTML of body field, create a promo block and insert a reference to it.
          /** @var BlockContent $block */
          $block = $this->createPromoBlock($this->parsePromoBlock($component['body']));
          $value[$property][]['target_id'] = $block->id();
        }
        else {
          $value[$property] = [
            'value' => $component['body'],
            'format' => 'full_html',
          ];
        }

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
          $asset_id = $this->getAttributeData('asset_id', $component);
          $img = '<img src="{{internal_asset_link_' . $asset_id . '}}"/>';
          $value[$property] = [
            'value' => $this->replaceTokens->processText($img),
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

      case 'date_conditional_content':
        if ($property == 'field_main_promos') {
          /** @var BlockContent $block */
          $block_data = $this->extractDateComponentData($component);
          $block = $this->createDateBlock($block_data);
          $value[$property][]['target_id'] = $block->id();
        }
        break;

      default:
        $value[$property] = $component['body'];
    }

    return $value;
  }

  /**
   * Extract data for Date block.
   *
   * @param array $component
   *   Component from the old DB.
   *
   * @return array
   *   Ready to use data to create Date Block.
   */
  protected function extractDateComponentData(array $component) {
    // @todo: Get real data.
    // Fow now we'll use mock data.
    $data = [];

    // Get dates.
    $data['date_start'] = $this->convertDate($this->getAttributeData('start_date_time', $component));
    $data['date_end'] = $this->convertDate($this->getAttributeData('end_date_time', $component));

    // Get content.
    $data['content_before'] = 'Content before...';
    $data['content_between'] = 'Content between...';
    $data['content_end'] = 'Content end...';

    return $data;
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
   * @param string $field
   *   Field to get. Default is 'extra_data_1'.
   *
   * @return mixed
   *   Extra data.
   */
  protected function getAttributeData($attribute, array $component, $field = 'extra_data_1') {
    foreach ($component['children'] as $item) {
      if ($item['body'] == $attribute) {
        if (array_key_exists($field, $item)) {
          return $item[$field];
        }
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
      self::YMCA_2013_LOCATIONS_CAMPS => [
        1 => [
          'rich_text' => 'field_content',
        ],
      ],
      self::YMCA_2013_LOCATION_HOME => [
        1 => [
          'rich_text' => 'field_content',
        ],
        3 => [
          'rich_text' => 'field_main_promos',
          'date_conditional_content' => 'field_main_promos',
        ]
      ],
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

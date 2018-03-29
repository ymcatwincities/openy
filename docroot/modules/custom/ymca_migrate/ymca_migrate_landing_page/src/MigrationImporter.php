<?php

namespace Drupal\ymca_migrate_landing_page;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Entity\EntityInterface;
use Drupal\file\Entity\File;
use Drupal\media_entity\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\pathauto\PathautoState;

/**
 * Class MigrationImporter.
 */
class MigrationImporter implements MigrationImporterInterface {

  /**
   * {@inheritdoc}
   */
  public static function migrate(EntityInterface $node) {
    // Skip homepage.
    if ($node->id() == 3) {
      return;
    }
    $node = Node::load(733);

    $template = $node->get('field_template')->value;
    // Do not migrate pages which have this field and it's not empty.
    if (!empty($template)) {
      return;
    }
    $node_path_alias = $node->path->alias;
    $node_title = $node->getTitle();

    // Rename OLD node and unpublish it.
    $node->setTitle('[OLD] ' . $node_title);
    $node->setUnpublished();
    $node->set('field_state', 'workflow_unpublished');
    $node->path->alias = '/old' . $node->path->alias;
    $node->save();

    // @todo
    // search blocks before creating new by text / by data-entity-id
    // menu block

    // Create landing page node object.
    $lp_node = Node::create([
      'type' => 'landing_page',
      'language' => 'en',
      'uid' => $node->get('uid'),
      'moderation_state' => 'published',
      'published' => Node::PUBLISHED,
      'promote' => Node::NOT_PROMOTED,
      'sticky' => Node::NOT_STICKY,
      'title' => $node_title,
      'path' => [
        'pathauto' => PathautoState::SKIP,
        'alias' => $node_path_alias,
      ],
    ]);
    // Set default layout.
    $lp_node->set('field_lp_layout', 'one_column');

    // Set related branch or camp.
    $related = $node->get('field_related')->target_id;
    if (!empty($related)) {
      $lp_node->set('field_ygtc_related', $related);
    }
    $lp_node->save();

    self::migrateHeaderArea($lp_node, $node);

    // Content Area
    self::migrateContentArea($lp_node, $node);

    // Sidebar area.
    self::migrateSidebarArea($lp_node, $node);

    // And finally save the new node.
    $lp_node->save();

    // Change menu link to a new landing page.
    $menu_link = \Drupal::entityTypeManager()->getStorage('menu_link_content')
      ->loadByProperties(['link__uri' => 'entity:node/' . $node->id()]);
    if (!empty($menu_link)) {
      $menu_link = reset($menu_link);
      $menu_link->set('link', ['uri' => 'entity:node/' . $lp_node->id()]);
      $menu_link->save();
    }
  }

  /**
   * Migrate data to the Header Area field.
   *
   * @param \Drupal\node\Entity\Node $lp_node
   *   New Landing page node.
   * @param \Drupal\node\Entity\Node $node
   *   Old Page node.
   */
  final public static function migrateHeaderArea(Node $lp_node, Node $node) {
    $header_variant = $node->get('field_header_variant')->value;
    $title_description = $node->get('field_title_description')->value;
    $color = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->loadByProperties(['name' => 'Grey']);
    $color = reset($color);

    $fields = [];
    switch ($header_variant) {
      case 'none':
        if (!empty($title_description)) {
          $fields['field_prgf_description'] = [
            'value' => $title_description,
            'format' => $node->get('field_title_description')->format,
          ];
        }
        break;

      case 'button':
        if (!empty($title_description)) {
          $fields['field_prgf_description'] = [
            'value' => $title_description,
            'format' => $node->get('field_title_description')->format,
          ];
        }
        // Add a button as a simple paragraph to the content area.
        $button_title = $node->get('field_header_button')->title;
        $button_url = $node->get('field_header_button')->uri;
        if (!empty($button_title) && !empty($button_url)) {
          // Create Simple content paragraph.
          $button_html = '<a class="btn" href="' . $button_url . '">' . $button_title . '</a>';
          $paragraph = Paragraph::create([
            'type' => 'simple_content',
            'field_prgf_description' => [
              'value' => $button_html,
              'format' => 'full_html',
            ],
          ]);
          $paragraph->save();

          // Set paragraph to the field.
          self::addParagraphToField($lp_node, 'field_content', $paragraph);
        }
        break;

      case 'image':
        // Small banner.
        $header_image = $node->get('field_header_image')->target_id;
        if (!empty($title_description) || !empty($header_image)) {
          // Add banner description.
          if (!empty($title_description)) {
            $fields['field_prgf_description'] = [
              'value' => $title_description,
              'format' => $node->get('field_title_description')->format,
            ];
          }
          // Add banner image.
          if (!empty($header_image)) {
            $file = File::load($header_image);
            // Try reuse existing media entity.
            $media_entity = \Drupal::entityTypeManager()->getStorage('media')
              ->loadByProperties(['name' => $file->getFilename()]);
            $media_entity = !empty($media_entity) ? reset($media_entity) : FALSE;
            if (empty($media_entity)) {
              // Create New Media Entity.
              $media_entity = Media::create([
                'bundle' => 'image',
                'name' => $file->getFilename(),
                'status' => Media::PUBLISHED,
                'field_media_image' => [
                  'target_id' => $header_image,
                ],
              ]);
              $media_entity->save();
            }
            $fields['field_prgf_image'] = [
              'target_id' => $media_entity->id(),
            ];
          }
        }
        break;

      case 'slideshow':
        // No pages with this option.
        break;
    }

    if (!empty($fields)) {
      // Create paragraph.
      $fields['type'] = 'small_banner';
      $fields['field_prgf_color'] = [
        'target_id' => $color->id(),
      ];
      $fields['field_prgf_headline'] = [
        'value' => $lp_node->getTitle(),
      ];
      $paragraph = Paragraph::create($fields);
      $paragraph->save();

      // Set paragraph to the field.
      self::addParagraphToField($lp_node, 'field_header_content', $paragraph);
    }

    // Secondary description and sidebar.
    $secondary_description = $node->get('field_lead_description')->value;
    $secondary_sidebar = $node->get('field_secondary_sidebar')->value;
    if (!empty($secondary_description) || !empty($secondary_sidebar)) {
      // Create Secondary Description paragraph.
      $fields = [
        'type' => 'secondary_description_sidebar',
        'field_prgf_right_column_block' => [
          'value' => $secondary_sidebar,
          'format' => $node->get('field_secondary_sidebar')->format,
        ],
      ];

      // Secondary Description.
      if (!empty($secondary_description)) {
        // Create block for the left column.
        $block_left = BlockContent::create([
          'type' => 'basic_block',
          'info' => '[secondary_description_sidebar_left] ' . $lp_node->getTitle(),
          'field_block_content' => [
            'value' => $secondary_description,
            'format' => $node->get('field_lead_description')->format,
          ],
        ]);
        $block_left->save();
        $fields['field_prgf_left_column_block'] = [
          'target_id' => $block_left->id(),
        ];
      }

      // Secondary Sidebar
      if (!empty($secondary_sidebar)) {
        // Create block for the right column.
        $block_right = BlockContent::create([
          'type' => 'basic_block',
          'info' => '[secondary_description_sidebar_right] ' . $lp_node->getTitle(),
          'field_block_content' => [
            'value' => $secondary_sidebar,
            'format' => $node->get('field_secondary_sidebar')->format,
          ],
        ]);
        $block_right->save();
        $fields['field_prgf_right_column_block'] = [
          'target_id' => $block_right->id(),
        ];
      }
      $paragraph = Paragraph::create($fields);
      $paragraph->save();

      // Set paragraph to the field.
      self::addParagraphToField($lp_node, 'field_header_content', $paragraph);
    }
  }

  /**
   * Migrate data to the Content Area field.
   *
   * @param \Drupal\node\Entity\Node $lp_node
   *   New Landing page node.
   * @param \Drupal\node\Entity\Node $node
   *   Old Page node.
   */
  final public static function migrateContentArea(Node $lp_node, Node $node) {
    // Check is there content in the field_ygtc_content.
    $content = $node->get('field_ygtc_content')->value;
    if (empty($content)) {
      return;
    }

    // Create Simple content paragraph.
    $paragraph = Paragraph::create([
      'type' => 'simple_content',
      'field_prgf_description' => [
        'value' => $content,
        'format' => $node->get('field_ygtc_content')->format,
      ],
    ]);
    $paragraph->save();

    // Set paragraph to the field.
    self::addParagraphToField($lp_node, 'field_content', $paragraph);
  }

  /**
   * Migrate data to the Sidebar Area field.
   *
   * @param \Drupal\node\Entity\Node $lp_node
   *   New Landing page node.
   * @param \Drupal\node\Entity\Node $node
   *   Old Page node.
   */
  final public static function migrateSidebarArea(Node $lp_node, Node $node) {
    // Check is there content in the field_sidebar.
    $sidebar_content = $node->get('field_sidebar')->value;
    if (!empty($sidebar_content)) {
      // Change layout.
      $lp_node->set('field_lp_layout', 'two_column');

      // Create Simple content paragraph.
      $paragraph = Paragraph::create([
        'type' => 'simple_content',
        'field_prgf_description' => [
          'value' => $sidebar_content,
          'format' => $node->get('field_sidebar')->format,
        ],
      ]);
      $paragraph->save();

      // Set paragraph to the field.
      self::addParagraphToField($lp_node, 'field_sidebar_content', $paragraph);
    }

    $sidebar_navigation = $node->get('field_sidebar_navigation')->value;
    if ($sidebar_navigation) {
//      @todo left column.
//      $lp_node->set('field_lp_layout', 'two_column');
      if (!empty($sidebar_content)) {
        // @todo 3 columns
//        $lp_node->set('field_lp_layout', 'two_column');
      }
//      // Create block wrapper paragraph.
//      $paragraph = Paragraph::create([
//        'type' => 'simple_content',
//        'field_prgf_description' => [
//          'value' => $sidebar_content,
//          'format' => $node->get('field_sidebar')->format,
//        ],
//      ]);
//      $paragraph->save();
      // Set paragraph to the field.
//      self::addParagraphToField($lp_node, 'field_sidebar_content', $paragraph);
    }
  }

  /**
   * Add paragraph to the node field.
   *
   * @param \Drupal\node\Entity\Node $lp_node
   *   New landing page.
   * @param string $field
   *   Field name.
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   New paragraph.
   */
  final public static function addParagraphToField(Node $lp_node, $field, Paragraph $paragraph) {
    $array = [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];
    $lp_node->get($field)->appendItem($array);
  }

  /**
   * Processes the migration.
   *
   * @param array $context
   *   The batch context.
   */
  public static function processBatch(&$context) {
    if (empty($context['results']['nids'])) {
      $query = \Drupal::database()
        ->select('node', 'n')
        ->fields('n', ['nid'])
        ->condition('type', 'article');
      $result = $query->execute();
      $context['results']['nids'] = $result->fetchAll(\PDO::FETCH_ASSOC);
      $context['sandbox']['max'] = count($context['results']['nids']);
      $context['sandbox']['progress'] = 0;
    }
    $part = array_splice($context['results']['nids'], 0, 1);
    $nids = array_map(function ($item) {
      return $item['nid'];
    }, $part);
    $nodes = \Drupal::entityTypeManager()->getStorage('node')
      ->loadMultiple($nids);
    foreach ($nodes as $node) {
      self::migrate($node);
    }

    $context['message'] = \Drupal::translation()
      ->translate('Migrating items: @progress out of @total', [
        '@progress' => $context['sandbox']['progress'],
        '@total' => $context['sandbox']['max'],
      ]);
    if ($context['sandbox']['progress'] < $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
    else {
      $context['finished'] = 1;
    }
  }

  /**
   * Finish batch.
   *
   * @param bool $success
   *   Status.
   * @param array $results
   *   Results.
   * @param array $operations
   *   List of performed operations.
   */
  public static function finishBatch($success, $results, $operations) {
    if ($success) {
      if (!empty($results['errors'])) {
        foreach ($results['errors'] as $error) {
          drupal_set_message($error, 'error');
        }
        drupal_set_message(\Drupal::translation()
          ->translate('Migration was completed with errors.'), 'warning');
      }
      else {
        drupal_set_message(\Drupal::translation()
          ->translate('Migration has been completed successfully.'));
      }
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $message = \Drupal::translation()
        ->translate('An error occurred while processing %error_operation with arguments: @arguments', [
          '%error_operation' => $error_operation[0],
          '@arguments' => print_r($error_operation[1], TRUE),
        ]);
      drupal_set_message($message, 'error');
    }
  }

}

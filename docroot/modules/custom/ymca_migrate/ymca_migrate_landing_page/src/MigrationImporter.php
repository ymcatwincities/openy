<?php

namespace Drupal\ymca_migrate_landing_page;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Entity\EntityInterface;
use Drupal\file\Entity\File;
use Drupal\media_entity\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Class MigrationImporter.
 */
class MigrationImporter implements MigrationImporterInterface {

  /**
   * {@inheritdoc}
   */
  public static function migrate(EntityInterface $node) {
    $user = \Drupal::currentUser();
    // Skip homepage.
    if ($node->id() == 3) {
      return;
    }
    // @todo
    // Show sidebar navigation - skip.
    // menu link
    // path alias
    // Field related camp/branch.

    // Create node object.
    $lp_node = Node::create([
      'type' => 'landing_page',
      'language' => 'en',
      'uid' => $user->id(),
      'moderation_state' => 'unpublished',
      'status' => Node::NOT_PUBLISHED,
      'promote' => Node::NOT_PROMOTED,
      'sticky' => Node::NOT_STICKY,
      'title' => '[MIGRATED] ' . $node->getTitle(),
      'path' => [
        'alias' => 'migration-test',
      ]
    ]);
    // Set default layout.
    $lp_node->set('field_lp_layout', 'one_column');

    self::migrateHeaderArea($lp_node, $node);

    // Content Area
    self::migrateContentArea($lp_node, $node);

    // Sidebar area.
    self::migrateSidebarArea($lp_node, $node);

    $sidebar_navigation = $node->get('field_sidebar_navigation')->value;
    if ($sidebar_navigation) {
      // @todo create paragraph with sidebar menu.
    }

    // And finally save the new node.
    $lp_node->save();
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
      case 'button':
        if (!empty($title_description)) {
          $fields['field_prgf_description'] = [
            'value' => $title_description,
            'format' => $node->get('field_title_description')->format,
          ];
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
        'value' => $node->getTitle(),
      ];
      $paragraph = Paragraph::create($fields);
      $paragraph->save();

      // Set paragraph to the field.
      $lp_node->set('field_header_content', [
        'target_id' => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ]);
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
          'info' => '[secondary_description_sidebar_left] ' . $node->getTitle(),
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
          'info' => '[secondary_description_sidebar_right] ' . $node->getTitle(),
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
      $lp_node->set('field_header_content', [
        'target_id' => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ]);
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
    $lp_node->set('field_content', [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ]);
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
    if (empty($sidebar_content)) {
      return;
    }
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
    $lp_node->set('field_sidebar_content', [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ]);
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
    $part = array_splice($context['results']['nids'], 0, 5);
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

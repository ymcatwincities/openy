<?php

/**
 * @file
 * Contains Drupal\ymca_migrate\Plugin\migrate\YmcaMigrateTrait.
 */

namespace Drupal\ymca_migrate\Plugin\migrate;

use Drupal\block_content\Entity\BlockContent;

/**
 * Helper functions for Ymca Migrate plugins.
 */
trait YmcaMigrateTrait {

  /**
   * Create and get Promo Block.
   *
   * @param array $data
   *   Required list of items:
   *    - header: Block header,
   *    - image_id: Image ID,
   *    - image_alt: Image alt,
   *    - link_uri: Link URI,
   *    - link_title: Link title,
   *    - content: Content.
   *
   * @return BlockContent
   *   Saved entity.
   */
  public function getPromoBlock(array $data) {
    $block = BlockContent::create([
      'langcode' => 'en',
      'info' => t('Test Promo Block'),
      'field_block_header' => $data['header'],
      'field_image' => [
        'target_id' => $data['image_id'],
        'alt' => $data['image_alt'],
      ],
      'field_link' => [
        'uri' => $data['link_uri'],
        'title' => $data['link_title']
      ],
      'field_block_content' => [
        'value' => $data['content'],
        'format' => 'full_html',
      ],
      'type' => 'promo_block'
    ])
      ->enforceIsNew();
    $block->save();
    return $block;
  }

  /**
   * Get block data for creating Promo Block.
   *
   * @param string $text
   *   Original text with tokens to parse.
   *
   * @return array
   *   Block data.
   */
  public function parsePromoBlock($text) {
    // @todo: Implement this method. @podarok, help if you have time.
    // Currently we'll use mock data.
    $block_data = [
      'header' => 'Header here...',
      'image_id' => 9,
      'image_alt' => 'Image alt',
      'link_uri' => 'http://www.google.com',
      'link_title' => 'Link title',
      'content' => 'Content here...',
    ];

    return $block_data;
  }

}

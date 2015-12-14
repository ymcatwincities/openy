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
   *    - header: Block header
   *    - image_id: Image ID
   *    - image_alt: Image alt
   *    - link_uri: Link URI
   *    - link_title: Link title
   *    - content: Content
   *
   * @return BlockContent
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

}

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
  public function createPromoBlock(array $data) {
    $block = BlockContent::create([
      'type' => 'promo_block',
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
    ])
      ->enforceIsNew();
    $block->save();
    return $block;
  }

  /**
   * Create and get Date Block.
   *
   * @param array $data
   *   Required list of items:
   *    - date_start: Start date,
   *    - date_end: End date,
   *    - content_before: Content before,
   *    - content_between: Content between,
   *    - content_end: Content end.
   *
   * @return BlockContent
   *   Saved entity.
   */
  public function createDateBlock($data) {
    $block = BlockContent::create([
      'type' => 'date_block',
      'langcode' => 'en',
      'info' => t('Test Date Based Block'),
      'field_start_date' => $data['date_start'],
      'field_end_date' => $data['date_end'],
      'field_content_date_before' => [
        'value' => $data['content_before'],
        'format' => 'full_html',
      ],
      'field_content_date_between' => [
        'value' => $data['content_between'],
        'format' => 'full_html',
      ],
      'field_content_date_end' => [
        'value' => $data['content_end'],
        'format' => 'full_html',
      ],
    ])
      ->enforceIsNew();
    $block->save();
    return $block;
  }

  public function convertDate($input) {
    // @todo: Make real conversion.
    // Fow now using just mock data.
    $output = '2004-02-12T15:19:21';
    return $output;
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
  public function parsePromoBlock($text = '') {
    $block_data = [];
    if ($text == '') {
      \Drupal::logger('YmcaMigrateTrait')->error(
        t('[DEV]: parsePromoBlock would use demo data, because text is empty')
      );
      $text = '<p><img src="{{internal_asset_link_9568}}" alt="Group Exercise" width="600" height="340" /></p>
<h2>Group Exercise </h2>
<p>Free drop-in classes for members.</p>
<p><a href="{{internal_page_link_7842}}">Group Exercise</a></p>';
    }
    preg_match_all(
      '/<p><img.*{{internal_asset_link_(.*)}}.*alt=\"(.*)\".*<\/p>.*[\n]<h2>(.*)<\/h2>.*[\n]<p>(.*)<\/p>.*[\n]<p><a.*{{internal_page_link_(.*)}}.*>(.*)<.*<\/p>/ixmU',
      $text,
      $match
    );
    if (count($match) != 7) {
      // Block(s) not detected.
      \Drupal::logger('YmcaMigrateTrait')->info(t('Block is not detected'));
      return FALSE;
    }
    /* @var \Drupal\ymca_migrate\Plugin\migrate\YmcaAssetsTokensMap $ymca_asset_tokens_map */
    $ymca_asset_tokens_map = \Drupal::service('ymcaassetstokensmap.service');

    /* @var \Drupal\ymca_migrate\Plugin\migrate\YmcaTokensMap $ymca_tokens_map */
    $ymca_tokens_map = \Drupal::service('ymcatokensmap.service');

    foreach ($match[0] as $block_id => $block) {

      $file_id = $ymca_asset_tokens_map->getAssetId($match[1][$block_id]);
      if ($file_id == FALSE) {
        \Drupal::logger('YmcaMigrateTrait')->error(
          t(
            '[DEV]: parsePromoBlock fileid for assetID: @id is not found',
            array('@id' => $match[1][$block_id])
          )
        );
        return FALSE;
      }

      $menu_id = $ymca_tokens_map->getMenuId($match[5][$block_id]);
      if ($menu_id === FALSE) {
        \Drupal::logger('YmcaMigrateTrait')->error(
          t(
            '[DEV]: parsePromoBlock menuid for pageID: @id is not found',
            array('@id' => $menu_id)
          )
        );
        return FALSE;
      }
      /* @var \Drupal\menu_link_content\Entity\MenuLinkContent $menu_link_entity */
      $menu_link_entity = \Drupal::entityManager()->getStorage(
        'menu_link_content'
      )->load($menu_id);
      // @todo check this if url is not relevant - generate proper url to menu item.
      $menu_link_url = $menu_link_entity->url();

      $block_data[$block_id] = [
        'header' => $match[3][$block_id],
        'image_id' => $file_id,
        'image_alt' => $match[2][$block_id],
        'link_uri' => $menu_link_url,
        'link_title' => $match[6][$block_id],
        'content' => $match[4][$block_id],
      ];
    }

    return $block_data;
  }

}

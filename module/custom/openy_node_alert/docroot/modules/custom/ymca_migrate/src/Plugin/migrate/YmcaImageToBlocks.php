<?php

namespace Drupal\ymca_migrate\Plugin\migrate;

use Drupal\block_content\Entity\BlockContent;
use Drupal\migrate\MigrateException;

/**
 * Class YmcaImageToBlocks.
 *
 * @package Drupal\ymca_migrate\Plugin\migrate
 */
class YmcaImageToBlocks {

  /**
   * YmcaImageToBlocks constructor.
   */
  public function __construct() {

  }

  /**
   * Image Block getter.
   *
   * @param int $asset_id
   *   Asset ID from AMM to be used as Image Block.
   *
   * @return \Drupal\block_content\Entity\BlockContent
   *   Returns block_content entity object.
   *
   * @throws \Drupal\migrate\MigrateException
   *   If block can't be obtained.
   */
  public function getBlock($asset_id = 0) {
    if ($asset_id == NULL) {
      throw new MigrateException(sprintf('Can\'t obtain asset for zero asset iD'));
    }
    /* @var \Drupal\ymca_migrate\Plugin\migrate\YmcaAssetsTokensMap $ymca_asset_tokens_map */
    $ymca_asset_tokens_map = \Drupal::service('ymcaassetstokensmap.service');
    $file_id = $ymca_asset_tokens_map->getAssetId($asset_id);
    if ($file_id == FALSE) {
      // @todo We should write message and continue migration, but not drop it.
      throw new MigrateException(t('Can\'t obtain local file for asset ID: @id', array('@id' => $asset_id)));
    }

    $b_type = 'block_content';
    $query = \Drupal::entityQuery($b_type)
      ->condition('type', 'image_block')
      ->condition('field_image', $file_id)
      ->execute();

    if (empty($query)) {
      $block = BlockContent::create([
          'langcode' => 'en',
          'field_image' => $file_id,
          'info' => t('Image block for AMM asset: @id', array('@id' => $asset_id)),
          'type' => 'image_block'
        ])
        ->enforceIsNew();
      $block->save();
      // @todo create new block with already migrated asset ID.
      return $block;
    }
    else {
      // Use already created first block.
      $block_id = array_shift($query);
      $block = \Drupal::entityManager()->getStorage($b_type)->load($block_id);
      return $block;
    }

  }

}

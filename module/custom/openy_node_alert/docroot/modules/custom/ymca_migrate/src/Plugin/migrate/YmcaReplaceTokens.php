<?php

namespace Drupal\ymca_migrate\Plugin\migrate;

use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Component\Utility\Html;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Class YmcaReplaceTokens.
 *
 * @package Drupal\ymca_migrate\Plugin\migrate
 */
class YmcaReplaceTokens {

  use YmcaMigrateTrait;

  /**
   * Processed string.
   *
   * @var string
   */
  protected $string;

  /**
   * Document to work with.
   *
   * @var \DOMDocument
   */
  protected $html;

  /**
   * TokensMap service.
   *
   * @var YmcaTokensMap
   */
  protected $ymcaTokensMap;

  /**
   * AssetsTokensMap service.
   *
   * @var YmcaAssetsTokensMap
   */
  protected $ymcaAssetTokensMap;

  /**
   * ImgToBlocks service.
   *
   * @var YmcaImageToBlocks
   */
  protected $ymcaImgToBlocks;

  /**
   * YmcaReplaceTokens constructor.
   */
  public function __construct() {
    $this->ymcaTokensMap = \Drupal::service('ymcatokensmap.service');
    $this->ymcaAssetTokensMap = \Drupal::service('ymcaassetstokensmap.service');
    $this->ymcaImgToBlocks = \Drupal::service('ymcaimgtoblocks.service');
  }

  /**
   * Method that is going to be process a string.
   *
   * @param string $string
   *   String been parsed.
   *
   * @return string
   *   String with replacements.
   */
  public function processText($string = '') {
    $this->string = $string;
    $this->html = Html::load('');
    $this->replacePageTokens();
    $this->replaceAssetLinksTokens();
    $this->replaceImageLinksTokens();
    return $this->string;
  }

  /**
   * Extract links from the text.
   *
   * @return bool|array
   *   Extraction results.
   */
  private function getLinks() {
    $regex = "/<a.*href=\"([^\"]*)\".*>(.*)<\/a>/miU";
    preg_match_all($regex, $this->string, $test);
    if (empty($test[0])) {
      return FALSE;
    }
    return $test;
  }

  /**
   * Generate embed code from attributes.
   *
   * @param array $attributes
   *   Attributes.
   *
   * @return string
   *   Embed code.
   */
  private function getEmbedCode($attributes) {
    $embed = $this->html->createElement('drupal-entity');
    foreach ($attributes as $attr_name => $attr_value) {
      $embed->setAttribute($attr_name, $attr_value);
    }

    $this->html->appendChild($embed);
    return $embed->C14N();
  }

  /**
   * Parse for Page Tokens replacements.
   */
  private function replacePageTokens() {
    if (!$test = $this->getLinks()) {
      return;
    }

    // Search for {{internal_page_link_\d+}}.
    foreach ($test[1] as $key => $item) {
      if (strpos($item, '{{internal_page_link_') === FALSE) {
        continue;
      }

      // Get link label.
      $link_label = $test[2][$key];

      // Get source page id.
      preg_match('/{{internal_page_link_(\d+)}}/miU', $item, $test_page_id);
      if (empty($test_page_id[1])) {
        return;
      }

      $source_page_id = $test_page_id[1];
      $menu_id = $this->ymcaTokensMap->getMenuId($source_page_id);
      if ($menu_id === FALSE) {
        $message = '[CLIENT]: A token for non migrated page was found. Page ID: @id';
        \Drupal::logger('ymca_migrate')->info($message, ['@id' => $source_page_id]);
        return;
      }

      /** @var MenuLinkContent $menu_link_entity */
      $menu_link_entity = \Drupal::getContainer()
        ->get('entity.manager')
        ->getStorage('menu_link_content')
        ->load($menu_id);

      $attributes = [
        'data-align' => 'none',
        'data-embed-button' => 'menu_link',
        'data-entity-embed-display' => 'entity_reference:entity_reference_label_url',
        'data-entity-embed-settings' => json_encode([
          'route_link' => 1,
          'route_title' => $link_label
        ]),
        'data-entity-type' => 'menu_link_content',
        'data-entity-id' => $menu_link_entity->id(),
        'data-entity-label' => $link_label,
        'data-entity-uuid' => $menu_link_entity->uuid(),
      ];

      $embed_code = $this->getEmbedCode($attributes);
      $this->string = str_replace($test[0][$key], $embed_code, $this->string);
    }
  }

  /**
   * Replace tokens links to assets(non images).
   */
  private function replaceAssetLinksTokens() {
    if (!$test = $this->getLinks()) {
      return;
    }

    // Search for {{internal_asset_link_\d+}}.
    foreach ($test[1] as $key => $item) {
      if (strpos($item, '{{internal_asset_link_') === FALSE) {
        continue;
      }

      // Get link label.
      $link_label = $test[2][$key];

      // Get source asset id.
      preg_match('/{{internal_asset_link_(\d+)}}/miU', $item, $test_asset_id);
      if (empty($test_asset_id[1])) {
        return;
      }

      $source_asset_id = $test_asset_id[1];
      $file_id = $this->ymcaAssetTokensMap->getAssetId($source_asset_id);
      if ($file_id === FALSE) {
        $message = '[CLIENT]: A token with non migrated asset found. Asset ID: @id';
        \Drupal::logger('ymca_migrate')->info($message, ['@id' => $source_asset_id]);
        return;
      }

      $file_entity = \Drupal::getContainer()
        ->get('entity.manager')
        ->getStorage('file')
        ->load($file_id);

      $attributes = [
        'data-align' => 'none',
        'data-embed-button' => 'file',
        'data-entity-embed-display' => 'entity_reference:file_entity_reference_label_url',
        'data-entity-embed-settings' => json_encode([
          'file_link' => 1,
          'file_title' => $link_label
        ]),
        'data-entity-type' => 'file',
        'data-entity-id' => $file_entity->id(),
        'data-entity-label' => $link_label,
        'data-entity-uuid' => $file_entity->uuid(),
      ];

      $embed_code = $this->getEmbedCode($attributes);
      $this->string = str_replace($test[0][$key], $embed_code, $this->string);
    }
  }

  /**
   * Replace tokens links to  images.
   */
  private function replaceImageLinksTokens() {
    $regex = "/<img.*src=\"([^\"]*)\".*\/>/miU";
    preg_match_all($regex, $this->string, $test);
    if (empty($test[0])) {
      return;
    }

    foreach ($test[1] as $key => $item) {
      if (strpos($item, '{{internal_asset_link_') === FALSE) {
        continue;
      }

      // Get source asset id.
      preg_match("/{{internal_asset_link_(\d+)(?:_\w+)?}}/miU", $item, $test_asset_id);
      if (empty($test_asset_id[1])) {
        return;
      }

      $source_asset_id = $test_asset_id[1];

      try {
        $block = $this->ymcaImgToBlocks->getBlock($source_asset_id);
      }
      catch (\Exception $e) {
        $message = '[CLIENT]: Failed to create image block. @message';
        \Drupal::logger('ymca_migrate')->info($message, ['@message' => $e->getMessage()]);
        return;
      }

      if (!$block) {
        return;
      }

      $attributes = [
        'data-align' => 'right',
        'data-embed-button' => 'block',
        'data-entity-embed-display' => 'entity_reference:entity_reference_entity_view',
        'data-entity-embed-settings' => json_encode([
          'view_mode' => 'default',
        ]),
        'data-entity-type' => 'block_content',
        'data-entity-id' => $block->id(),
        'data-entity-label' => $block->get('info')->getValue()[0]['value'],
        'data-entity-uuid' => $block->uuid(),
      ];

      $embed_code = $this->getEmbedCode($attributes);
      $this->string = str_replace($test[0][$key], $embed_code, $this->string);
    }
  }

}

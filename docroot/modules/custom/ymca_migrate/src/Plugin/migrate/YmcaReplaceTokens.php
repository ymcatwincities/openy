<?php
/**
 * @file
 * Service for parsing AMM tokenized text string and replace it with needed .
 */

namespace Drupal\ymca_migrate\Plugin\migrate;

use Drupal\Component\Utility\Html;

/**
 * Class YmcaReplaceTokens.
 *
 * @package Drupal\ymca_migrate\Plugin\migrate
 */
class YmcaReplaceTokens {

  /**
   * Processed string.
   *
   * @var string.
   */
  protected $string;

  /**
   * Document to work with.
   *
   * @var \DOMDocument
   */
  protected $html;


  const DRUPAL_DEFAULT_FILE_ITEM = 0;

  /**
   * YmcaReplaceTokens constructor.
   */
  public function __construct() {
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
    $this->html = Html::load($this->string);
    $this->replacePageTokens();
    $this->replaceAssetLinksTokens();
    $this->replaceImageLinksTokens();
    return $this->string;
  }

  /**
   * Pasre for Page Tokens replacements.
   */
  private function replacePageTokens() {
    preg_match_all(
      "/<a.*href=\"{{internal_page_link_[0-9][0-9]*}}\">.*<\/a>/mU",
      $this->string,
      $test
    );
    if (empty($test) || empty($test[0])) {
      return;
    }
    foreach ($test as $id => $matched) {
      if (empty($matched)) {
        continue;
      }
      foreach ($matched as $mid => $match) {
        preg_match('/\>(.*?)\</mU', $match, $link_label);
        if (!empty($link_label)) {
          $link_label = $link_label[1];
        }
        else {
          $link_label = '';
        }

        preg_match_all(
          "/\{{internal_page_link_(.*?)\}}/",
          $match,
          $source_page_ids
        );
        $source_page_id = $source_page_ids[1][0];

        $p = $this->html->createElement('drupal-entity');
        $p->setAttribute('data-align', 'none');
        $p->setAttribute('data-embed-button', 'menu_link');
        $p->setAttribute(
          'data-entity-embed-display',
          'entity_reference:entity_reference_label_url'
        );
        $p->setAttribute(
          'data-entity-embed-settings',
          htmlspecialchars_decode(
            '{&quot;route_link&quot;:1,&quot;route_title&quot;:&quot;' . $link_label . '&quot;}'
          )
        );
        $p->setAttribute('data-entity-type', 'menu_link_content');
        /* @var \Drupal\ymca_migrate\Plugin\migrate\YmcaTokensMap $ymca_tokens_map */
        $ymca_tokens_map = \Drupal::service('ymcatokensmap.service');
        $menu_id = $ymca_tokens_map->getMenuId($source_page_id);
        if ($menu_id === FALSE) {
          // @todo log.
          return;
        }
        else {
          $p->setAttribute('data-entity-id', $menu_id);
          $p->setAttribute('data-entity-label', $link_label);
          $menu_link_entity = \Drupal::entityManager()->getStorage(
            'menu_link_content'
          )->load($menu_id);
          $menu_link_uuid = $menu_link_entity->uuid();
          $p->setAttribute(
            'data-entity-uuid',
            $menu_link_uuid
          );
        }

        $this->html->appendChild($p);
        $entity_embed_widget = $p->C14N();
        $this->string = str_replace(
          $match,
          $entity_embed_widget,
          $this->string
        );
      }
    }
  }

  /**
   * Replace tokens links to assets(non images).
   */
  private function replaceAssetLinksTokens() {
    preg_match_all(
      "/<a.*href=\"{{internal_asset_link_[0-9][0-9]*}}\">.*<\/a>/mU",
      $this->string,
      $test
    );
    if (empty($test) || empty($test[0])) {
      return;
    }
    foreach ($test as $id => $matched) {
      if (empty($matched)) {
        continue;
      }
      foreach ($matched as $mid => $match) {
        preg_match('/\>(.*?)\</mU', $match, $link_label);
        if (!empty($link_label)) {
          $link_label = $link_label[1];
        }
        else {
          $link_label = '';
        }

        preg_match_all(
          "/\{{internal_asset_link_(.*?)\}}/",
          $match,
          $source_assets_ids
        );
        $source_asset_id = $source_assets_ids[1][0];

        /*
         * <drupal-entity data-align="none" data-embed-button="file" data-entity-embed-display="entity_reference:file_entity_reference_label_url" data-entity-embed-settings="{&quot;file_link&quot;:1,&quot;file_title&quot;:&quot;Custom file title&quot;}" data-entity-id="11" data-entity-label="File" data-entity-type="file" data-entity-uuid="15600458-5a9f-41a2-ac08-8bd7e0aa0cf2"></drupal-entity>
         */
        $p = $this->html->createElement('drupal-entity');
        $p->setAttribute('data-align', 'none');
        $p->setAttribute('data-embed-button', 'file');
        $p->setAttribute('data-entity-type', 'file');
        $p->setAttribute(
          'data-entity-embed-display',
          'entity_reference:file_entity_reference_label_url'
        );
        $p->setAttribute(
          'data-entity-embed-settings',
          htmlspecialchars_decode(
            '{&quot;file_link&quot;:1,&quot;file_title&quot;:&quot;' . $link_label . '&quot;}'
          )
        );
        $p->setAttribute('data-entity-type', 'file');
        /* @var \Drupal\ymca_migrate\Plugin\migrate\YmcaAssetsTokensMap $ymca_asset_tokens_map */
        $ymca_asset_tokens_map = \Drupal::service('ymcaassetstokensmap.service');
        $file_id = $ymca_asset_tokens_map->getAssetId($source_asset_id);
        if ($file_id === FALSE) {
          // @todo log.
          return;
        }
        else {
          $p->setAttribute('data-entity-id', $file_id);
          $p->setAttribute('data-entity-label', $link_label);
          $file_entity = \Drupal::entityManager()->getStorage(
            'file'
          )->load($file_id);
          $file_uuid = $file_entity->uuid();
          $p->setAttribute(
            'data-entity-uuid',
            $file_uuid
          );
        }

        $this->html->appendChild($p);
        $entity_embed_widget = $p->C14N();
        $this->string = str_replace(
          $match,
          $entity_embed_widget,
          $this->string
        );
      }
    }
  }

  /**
   * Replace tokens links to  images.
   */
  private function replaceImageLinksTokens() {
    // <img class="alignright" style="margin:0 0 15px 15px;" src="{{internal_asset_link_11714}}" alt="Gentle Yoga—what’s it all about?" width="230" height="166" />
    preg_match_all(
      "/<img.*src=\"{{internal_asset_link_[0-9][0-9]*}}\".*\/>/mU",
      $this->string,
      $test
    );
    if (empty($test) || empty($test[0])) {
      return;
    }
    foreach ($test as $id => $matched) {
      if (empty($matched)) {
        continue;
      }
      foreach ($matched as $mid => $match) {
        preg_match('/\>(.*?)\</mU', $match, $link_label);
        if (!empty($link_label)) {
          $link_label = $link_label[1];
        }
        else {
          $link_label = '';
        }

        preg_match_all(
          "/\{{internal_asset_link_(.*?)\}}/",
          $match,
          $source_assets_ids
        );
        $source_asset_id = $source_assets_ids[1][0];

        /*
         * <drupal-entity data-align="none" data-embed-button="file" data-entity-embed-display="entity_reference:file_entity_reference_label_url" data-entity-embed-settings="{&quot;file_link&quot;:1,&quot;file_title&quot;:&quot;Custom file title&quot;}" data-entity-id="11" data-entity-label="File" data-entity-type="file" data-entity-uuid="15600458-5a9f-41a2-ac08-8bd7e0aa0cf2"></drupal-entity>
         */
        $p = $this->html->createElement('drupal-entity');
        $p->setAttribute('data-align', 'none');
        $p->setAttribute('data-embed-button', 'file');
        $p->setAttribute('data-entity-type', 'file');
        $p->setAttribute(
          'data-entity-embed-display',
          'entity_reference:file_entity_reference_label_url'
        );
        $p->setAttribute(
          'data-entity-embed-settings',
          htmlspecialchars_decode(
            '{&quot;file_link&quot;:1,&quot;file_title&quot;:&quot;' . $link_label . '&quot;}'
          )
        );
        $p->setAttribute('data-entity-type', 'file');
        /* @var \Drupal\ymca_migrate\Plugin\migrate\YmcaAssetsTokensMap $ymca_asset_tokens_map */
        $ymca_asset_tokens_map = \Drupal::service('ymcaassetstokensmap.service');
        $file_id = $ymca_asset_tokens_map->getAssetId($source_asset_id);
        if ($file_id === FALSE) {
          // @todo log.
          return;
        }
        else {
          $p->setAttribute('data-entity-id', $file_id);
          $p->setAttribute('data-entity-label', $link_label);
          $file_entity = \Drupal::entityManager()->getStorage(
            'file'
          )->load($file_id);
          $file_uuid = $file_entity->uuid();
          $p->setAttribute(
            'data-entity-uuid',
            $file_uuid
          );
        }

        $this->html->appendChild($p);
        $entity_embed_widget = $p->C14N();
        $this->string = str_replace(
          $match,
          $entity_embed_widget,
          $this->string
        );
      }
    }
  }

}

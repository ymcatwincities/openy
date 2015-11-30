<?php
/**
 * @file
 * Service for parsing AMM tokenized text string and replace it with needed .
 */

namespace Drupal\ymca_migrate\Plugin\migrate;

use Drupal\Component\Utility\Html;
use Drupal\migrate\Entity\MigrationInterface;

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
    $this->replacePageTokens();
    $this->replaceAssetLinksTokens();
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
    $html = Html::load($this->string);
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

        $p = $html->createElement('drupal-entity');
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
          $p->setAttribute(
            'data-entity-uuid',
            '6b6c92d5-abc0-4384-8800-cfaed6750738'
          );
          $p->setAttribute(
            'data-entity-id',
            self::DRUPAL_SIGN_IN_MENU_ITEM
          );
          $p->setAttribute(
            'data-entity-label',
            t(
              'Lost link to internal Page'
            )
          );
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

        $html->appendChild($p);
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

  }
  
}

<?php

/**
 * @file
 * Contains source plugin for migration menu links.
 */

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\ymca_migrate\Plugin\migrate\YmcaQueryBuilder;

/**
 * Source plugin for menu_link_content items.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_menu_link_content_redirect"
 * )
 */
class YmcaMigrateMenuLinkContentRedirect extends YmcaMigrateMenuLinkContent {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('amm_site_page', 'p')
      ->fields('p',
        [
          'site_page_id',
          'page_subdirectory',
          'redirect_target',
          'redirect_type',
          'redirect_url',
          'redirect_page_id',
        ])
      ->condition('is_redirect', 1);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'parent_id' => $this->t('Parent ID'),
      'title' => $this->t('Link Title'),
      'sequence_index' => $this->t('Weight'),
      'menu_name' => $this->t('Menu'),
      'enabled' => $this->t('Enabled'),
    ];

    return $fields;
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

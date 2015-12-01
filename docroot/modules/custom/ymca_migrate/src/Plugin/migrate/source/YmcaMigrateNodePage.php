<?php

/**
 * @file
 * Contains \Drupal\ymca_migrate\Plugin\migrate\source\YmcaMigrateNodePage.
 */

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\ymca_migrate\Plugin\migrate\YmcaMigrateNodeBase;
use Drupal\ymca_migrate\Plugin\migrate\YmcaQueryBuilder;

/**
 * Source plugin for node:article content.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_node_page"
 * )
 */
class YmcaMigrateNodePage extends YmcaMigrateNodeBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query_builder = new YmcaQueryBuilder($this->getDatabase());
    // @todo Import all content.
    $query_builder->getAllChildren(4693);
    return $query_builder->build();
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'site_page_id' => $this->t('Page ID'),
      'page_title' => $this->t('Page title'),
      'field_content' => $this->t('Content'),
      'field_lead_description' => $this->t('Content'),
      'field_header_button' => $this->t('Header button'),
      'field_header_variant' => $this->t('Header variant'),
      'field_sidebar' => $this->t('Sidebar'),
      'field_secondary_sidebar' => $this->t('Secondary sidebar'),
    ];
    return $fields;
  }

}

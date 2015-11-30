<?php

/**
 * @file
 * Contains source plugin for migration menu links.
 */

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;
use Drupal\ymca_migrate\Plugin\migrate\YmcaQueryBuilder;

/**
 * Source plugin for menu_link_content items.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_menu_link_content"
 * )
 */
class YmcaMigrateMenuLinkContent extends SqlBase {

  /**
   * Get menu name.
   *
   * @return string
   *   Machine menu name.
   */
  public function getMenu() {
    return $this->migration->get('source')['constants']['menu_name'];
  }

  /**
   * Get parent id item for the menu items.
   *
   * @return int
   *   Parent ID.
   */
  public function getParentId() {
    $constants = $this->migration->get('source')['constants'];
    if (isset($constants['menu_parent_id'])) {
      return $this->migration->get('source')['constants']['menu_parent_id'];
    }
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query_builder = new YmcaQueryBuilder($this->getDatabase());
    $query = $query_builder->getAllChildren($this->getParentId());
    return $query;
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
  public function prepareRow(Row $row) {
    // Page title could be empty, use page_name instead.
    if ($title = $row->getSourceProperty('page_title')) {
      $row->setSourceProperty('title', $title);
    }
    else {
      $row->setSourceProperty('title', $row->getSourceProperty('page_name'));
    }

    $row->setSourceProperty('menu_name', $this->getMenu());
    $row->setSourceProperty('link', ['uri' => 'internal:/']);

    $row->setSourceProperty('enabled', TRUE);
    if ($row->getSourceProperty('exclude_from_nav')) {
      $row->setSourceProperty('enabled', FALSE);
    }

    $parent = $row->getSourceProperty('parent_id');
    if ($parent == $this->getParentId()) {
      $row->setSourceProperty('parent_id', 0);
    }
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

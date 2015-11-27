<?php

/**
 * @file
 * Contains source plugin for migration menu links.
 */

namespace Drupal\ymca_migrate\Plugin\migrate;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source source plugin for menu_link_content items.
 */
abstract class YmcaMigrateMenuLinkContentBase extends SqlBase {

  /**
   * Get menu.
   *
   * @return string
   *   Menu name.
   */
  abstract public function getMenu();

  /**
   * Get parent ID.
   *
   * @return int
   *   Parent ID.
   */
  abstract public function getParentId();

  /**
   * {@inheritdoc}
   */
  public function query() {
    $ymca_page_query = YmcaPagesQuery::init([], [], $this->getDatabase());
    $query = $ymca_page_query->getQueryByParent($this->getParentId());
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'site_page_id' => $this->t('Page ID'),
      'parent_id' => $this->t('Parent ID'),
      'page_name' => $this->t('Page Name'),
      'page_title' => $this->t('Page Title'),
      'title' => $this->t('Link Title'),
      'sequence_index' => $this->t('Weight'),
      'menu_name' => $this->t('Menu'),
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

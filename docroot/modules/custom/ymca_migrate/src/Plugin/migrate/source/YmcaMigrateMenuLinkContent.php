<?php

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;
use Drupal\ymca_migrate\Plugin\migrate\YmcaMigrateTrait;
use Drupal\ymca_migrate\Plugin\migrate\YmcaQueryBuilder;

/**
 * Source plugin for menu_link_content items.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_menu_link_content"
 * )
 */
class YmcaMigrateMenuLinkContent extends SqlBase {

  use YmcaMigrateTrait;

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
   * @return array
   *   Parent IDs array.
   */
  public function getParents() {
    $constants = $this->migration->get('source')['constants'];
    if (isset($constants['menu_parents'])) {
      return $constants['menu_parents'];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    /* @var YmcaQueryBuilder $query_builder */
    $query_builder = new YmcaQueryBuilder($this->getDatabase());
    foreach ($this->getParents() as $item) {
      $query_builder->getAllChildren($item);
    }
    $query = $query_builder->build();
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
    $row->setSourceProperty('title', $row->getSourceProperty('page_name'));
    $row->setSourceProperty('menu_name', $this->getMenu());

    if ($this->isDev()) {
      $row->setSourceProperty('link', ['uri' => 'internal:/']);
    }
    else {
      $row->setSourceProperty('link', [
        'uri' => sprintf('internal:%s', rtrim($row->getSourceProperty('page_subdirectory'), '/'))
      ]);
    }

    $row->setSourceProperty('enabled', TRUE);
    if ($row->getSourceProperty('exclude_from_nav')) {
      $row->setSourceProperty('enabled', FALSE);
    }

    // Remove parents from root items within the menu.
    if (in_array($row->getSourceProperty('site_page_id'), $this->getParents())) {
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

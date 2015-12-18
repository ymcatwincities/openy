<?php

/**
 * @file
 * Contains \Drupal\ymca_migrate_status\Controller\YmcaMigrateStatus.
 */

namespace Drupal\ymca_migrate_status\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\ymca_migrate\Plugin\migrate\YmcaMigrateTrait;

/**
 * YmcaMigrateStatus controller.
 */
class YmcaMigrateStatus extends ControllerBase {

  use YmcaMigrateTrait;

  /**
   * Legacy DB connection.
   *
   * @var Connection
   */
  private $dbLegacy = NULL;

  /**
   * Indicates debugging or not.
   *
   * @var bool
   */
  private $isDebug = FALSE;

  /**
   * List of components.
   *
   * @var array
   */
  private $components = [
    'complex' => [
      'content_block_join',
      'date_conditional_content',
    ],
  ];

  /**
   * List of pages.
   *
   * @var array
   */
  private $pages = [];

  private function calcPages($level) {
    switch ($level) {
      case 0:
        $query = $this->dbLegacy->select('amm_site_page', 'p');
        $query->fields('p', ['site_page_id']);
        $query->addJoin('left', 'amm_site_page_component', 'c', 'p.site_page_id = c.site_page_id');
        $query->condition('c.component_type', $this->getSkippedPages(), 'NOT IN');
        $this->pages['all'] = $query->execute()->fetchAllAssoc('site_page_id');
        $this->pages[0] = array_diff_key($this->pages['all'], $this->pages[1]);
        break;

      case 1:
        $query = $this->dbLegacy->select('amm_site_page', 'p');
        $query->fields('p', ['site_page_id']);
        $query->addJoin('left', 'amm_site_page_component', 'c', 'p.site_page_id = c.site_page_id');
        $query->condition('c.component_type', $this->components['complex'], 'IN');
        $query->condition('c.component_type', $this->getSkippedPages(), 'NOT IN');
        $query->distinct();
        $this->pages[1] = $query->execute()->fetchAllAssoc('site_page_id');
        break;
    }
  }

  /**
   * Get pages with nested components.
   *
   * @param array $pages
   *   List of pages to search.
   *
   * @param int $level
   *   Level of nesting.
   */
  private function calcNested(array $pages, $level) {
    foreach ($pages as $page) {
      $components = $this->getComponentsByPage($page->site_page_id);
      foreach ($components as $component) {
        // Here examine only complex components.
        if (in_array($component->component_type, $this->components['complex'])) {
          // Get children components of the component.
          $children = $this->getChildrenByComponent($component);
          // Check if among children there are complex ones.
          foreach ($children as $child) {
            if (in_array($child->component_type, $this->components['complex'])) {
              $this->pages[$level][$page->site_page_id] = $page;
            }
          }
        }
      }
    }

    // Update previous level.
    $prev = $level - 1;
    $this->pages[$prev] = array_diff_key($this->pages[$prev], $this->pages[$level]);
  }

  /**
   * Show the page.
   */
  public function pageView() {
    // Setup.
    $this->dbLegacy = Database::getConnection('default', 'legacy');

    $this->calcPages(1);
    $this->calcPages(0);
    $this->calcNested($this->pages[1], 2);

    // Prepare table.
    $data = [
      0 => array_values($this->pages[0]),
      1 => array_values($this->pages[1]),
      2 => array_values($this->pages[2]),
    ];

    $header = [];
    $counters = [];
    foreach ($data as $item => $value) {
      $num = count($value);
      $counters[] = $num;
      $header[] = sprintf(
        'Level #%d [%d], %d%%',
        $item,
        $num,
        $num * 100 / count($this->pages['all'])
      );
    }

    $count = TRUE;
    $i = 0;
    $rows = [];
    while ($count === TRUE) {
      foreach ($data as $key => $value) {
        $rows[$i][$key] = $value[$i]->site_page_id;
        $rows[$i][$key] = $value[$i]->site_page_id;
      }
      $i++;

      $max = 10;
      if (!$this->isDebug) {
        $max = max($counters);
      }
      if ($max == $i) {
        $count = FALSE;
      }
    }

    return array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );
  }

  /**
   * Get children of the component.
   *
   * @param $component
   *   Component object.
   *
   * @return array
   *   A list of components.
   */
  private function getChildrenByComponent($component) {
    $children = [];
    switch ($component->component_type) {
      case 'content_block_join':
        $children = $this->getComponentsByParent($component->extra_data_1);
        break;
      case 'date_conditional_content':
        // These block has 3 fields.
        $types = ['during_parent_id', 'after_parent_id', 'before_parent_id'];
        // Get all attributes and children to get real children.
        $ancestors = $this->getComponentsByParent($component->site_page_component_id);
        foreach ($ancestors as $ancestor) {
          if ($ancestor->component_type == 'subcontent') {
            foreach ($types as $type) {
              if ($ancestor->body == $type) {
                $res = $this->getComponentsByParent($ancestor->site_page_component_id);
                foreach ($res as $res_item) {
                  $children[$res_item->site_page_component_id] = $res_item;
                }
              }
            }
          }
        }

        break;
    }
    return $children;
  }

  /**
   * Get components by page ID.
   *
   * @param $id
   *   Page ID.
   *
   * @return array
   *   A list of components.
   */
  private function getComponentsByPage($id) {
    $query = $this->dbLegacy->select('amm_site_page_component', 'c')
      ->fields('c')
      ->condition('site_page_id', $id);
    return $query->execute()->fetchAllAssoc('site_page_component_id');
  }

  /**
   * Get components by Parent ID.
   *
   * @param $id
   *   Component ID.
   *
   * @return array
   *   A list of components.
   */
  private function getComponentsByParent($id) {
    $query = $this->dbLegacy->select('amm_site_page_component', 'c')
      ->fields('c')
      ->condition('parent_component_id', $id);
    return $query->execute()->fetchAllAssoc('site_page_component_id');
  }

}

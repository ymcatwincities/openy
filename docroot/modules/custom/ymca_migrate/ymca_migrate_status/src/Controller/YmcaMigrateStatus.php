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
//      'subcontent',
//      'date_conditional_content',
//      'content_expander',
//      'content_wrapper',
    ],
  ];

  /**
   * List of pages.
   *
   * @var array
   */
  private $pages = [];

  /**
   * Get list of complex [1] pages.
   *
   * @return array
   *   List of complex [1] pages.
   */
  private function getComplexPages() {
    if (!array_key_exists(1, $this->pages)) {
      $query = $this->dbLegacy->select('amm_site_page', 'p');
      $query->fields('p', ['site_page_id']);
      $query->addJoin('left', 'amm_site_page_component', 'c', 'p.site_page_id = c.site_page_id');
      $query->condition('c.component_type', $this->components['complex'], 'IN');
      $query->condition('c.component_type', $this->getSkippedPages(), 'NOT IN');
      $query->distinct();
      $this->pages[1] = $query->execute()->fetchAllAssoc('site_page_id');
    }
    return $this->pages[1];
  }

  /**
   * Get list of simple [0] pages.
   *
   * @return array
   *   List of simple [0] pages.
   */
  private function getSimplePages() {
    if (!array_key_exists(0, $this->pages)) {
      $query = $this->dbLegacy->select('amm_site_page', 'p');
      $query->fields('p', ['site_page_id']);
      $query->addJoin('left', 'amm_site_page_component', 'c', 'p.site_page_id = c.site_page_id');
      $query->condition('c.component_type', $this->getSkippedPages(), 'NOT IN');
      $all = $query->execute()->fetchAllAssoc('site_page_id');
      $this->pages[0] = array_diff_key($all, $this->getComplexPages());
    }
    return $this->pages[0];
  }

  /**
   * Show the page.
   */
  public function pageView() {
    // Setup.
    $this->dbLegacy = Database::getConnection('default', 'legacy');

    // Get more complex pages.
    // @todo Make as function if needed.
    $more = [];
    foreach ($this->getComplexPages() as $page) {
      $components = $this->getComponentsByPage($page->site_page_id);
      foreach ($components as $component) {
        // Here examine only complex components.
        if (in_array($component->component_type, $this->components['complex'])) {
          // Get children components of the component.
          $children = $this->getChildrenByComponent($component);
          // Check if among children there are complex ones.
          foreach ($children as $child) {
            if (in_array($child->component_type, $this->components['complex'])) {
              $more[$page->site_page_id] = $page;
            }
          }
        }
      }
    }

    // Prepare table.
    $data = [
      0 => array_values($this->getSimplePages()),
      1 => array_values($this->getComplexPages()),
    ];

    $header = [
      sprintf('Simple [%d]', count($this->getSimplePages())),
      sprintf('Complex [%d]', count($this->getComplexPages())),
    ];

    $counters = [];
    foreach ($data as $item => $value) {
      $counters[] = count($value);
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

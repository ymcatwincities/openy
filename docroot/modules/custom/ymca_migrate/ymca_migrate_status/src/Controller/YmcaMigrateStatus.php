<?php

namespace Drupal\ymca_migrate_status\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
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

  /**
   * List of migrations.
   *
   * @var array
   */
  private $migrations = [];

  /**
   * Get pages for level 0 and 1.
   *
   * @param int $level
   *   Level.
   */
  private function calcPages($level) {
    switch ($level) {
      case 0:
        $query = $this->dbLegacy->select('amm_site_page', 'p');
        $query->fields('p', ['site_page_id', 'page_subdirectory', 'page_title']);
        $query->addJoin('left', 'amm_site_page_component', 'c', 'p.site_page_id = c.site_page_id');
        $query->condition('c.component_type', $this->getSkippedPages(), 'NOT IN');
        $query->isNull('p.backup_of_site_page_id');
        $this->pages['all'] = $query->execute()->fetchAllAssoc('site_page_id');
        $this->pages[0] = array_diff_key($this->pages['all'], $this->pages[1]);
        break;

      case 1:
        $query = $this->dbLegacy->select('amm_site_page', 'p');
        $query->fields('p', ['site_page_id']);
        $query->addJoin('left', 'amm_site_page_component', 'c', 'p.site_page_id = c.site_page_id');
        $query->condition('c.component_type', $this->components['complex'], 'IN');
        $query->condition('c.component_type', $this->getSkippedPages(), 'NOT IN');
        $query->isNull('p.backup_of_site_page_id');
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
    $this->dbLegacy = Database::getConnection('default', 'amm_source');
    $this->prepopulateMigrations();

    $this->calcPages(1);
    $this->calcPages(0);
    $this->calcNested($this->pages[1], 2);

    // Prepare table.
    $rows = [];
    foreach ($this->pages[2] as $id => $itm) {
      // ID.
      $rows[$id][] = $id;

      // Title.
      $rows[$id][] = $this->pages['all'][$id]->page_title;

      // Legacy Link.
      $url = Url::fromUri($this->getLegacyUrl($id));
      $rows[$id][] = \Drupal::l('Old site', $url);

      // Node Link.
      if ($nid = YmcaMigrateTrait::getDestinationId(['site_page_id' => $id], $this->migrations)) {
        $rows[$id][] = \Drupal::l('New site', Url::fromRoute('entity.node.canonical', ['node' => $nid]));
      }
    }

    $num = count($this->pages[2]);
    return array(
      'info' => [
        '#markup' => sprintf(
          'Pages count: %s (%d%%)',
          $num,
          $num * 100 / count($this->pages['all'])
        )
      ],
      'table' => [
        '#theme' => 'table',
        '#header' => ['ID', 'title', 'Old link', 'New link'],
        '#rows' => $rows,
      ],
    );
  }

  /**
   * Get children of the component.
   *
   * @param \stdClass $component
   *   Component object.
   *
   * @return array
   *   A list of components.
   */
  private function getChildrenByComponent(\stdClass $component) {
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
   * @param int $id
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
   * @param int $id
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

  /**
   * Get legacy Url to the page.
   *
   * @param int $id
   *   Page ID.
   *
   * @return string
   *   Url to the page.
   */
  private function getLegacyUrl($id) {
    $path = $this->pages['all'][$id]->page_subdirectory;
    return sprintf(
      'http://ymcatwincities.org%s',
      rtrim($path, '/')
    );
  }

  /**
   * Prepopulate migrations.
   */
  private function prepopulateMigrations() {
    $migrations = [
      'ymca_migrate_node_page',
      'ymca_migrate_node_camp',
      'ymca_migrate_node_location',
    ];
    $this->migrations = \Drupal::getContainer()
      ->get('entity.manager')
      ->getStorage('migration')
      ->loadMultiple($migrations);
  }

}

<?php

/**
 * @file
 * Contains \Drupal\ymca_migrate_status\Controller\YmcaMigrateStatus.
 */

namespace Drupal\ymca_migrate_status\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;

class YmcaMigrateStatus extends ControllerBase {

  private $db_legacy = NULL;

  private $components = [
    'complex' => [
      'content_block_join',
      'subcontent',
      'date_conditional_content',
      'content_expander',
      'content_wrapper',
    ],
  ];

  private $pages = [];


  private function getComplexPages() {
    if (!array_key_exists(1, $this->pages)) {
      $query = $this->db_legacy->select('amm_site_page', 'p');
      $query->fields('p', ['site_page_id']);
      $query->addJoin('left', 'amm_site_page_component', 'c', 'p.site_page_id = c.site_page_id');
      $query->condition('c.component_type', $this->components['complex'], 'IN');
      $query->distinct();
      $this->pages[1] = $query->execute()->fetchAllAssoc('site_page_id');
    }
    return $this->pages[1];
  }

  private function getSimplePages() {
    if (!array_key_exists(0, $this->pages)) {
      $query = $this->db_legacy->select('amm_site_page', 'p');
      $query->fields('p', ['site_page_id']);
      $all = $query->execute()->fetchAllAssoc('site_page_id');
      $this->pages[0] = array_diff_key($all, $this->getComplexPages());
    }
    return $this->pages[0];
  }

  public function pageView() {
    // Setup.
    $this->db_legacy = Database::getConnection('default', 'legacy');

    $this->getComplexPages();
    $this->getSimplePages();

    // Get pages with complex components.

    $header = [
      sprintf('Simple [%d]', count($this->getSimplePages())),
      sprintf('Complex [%d]', count($this->getComplexPages())),
    ];

    $rows = [];
    $count = TRUE;
    $i = 0;
    $data = [
      0 => array_values($this->getSimplePages()),
      1 => array_values($this->getComplexPages()),
    ];

    while ($count === TRUE) {
      foreach ($data as $key => $value) {
        $rows[$i][$key] = $value[$i]->site_page_id;
        $rows[$i][$key] = $value[$i]->site_page_id;
      }
      $i++;
//      if (max([count($this->pages[0], $this->pages[1])]) == $i) {
      if ($i == 10) {
        $count = FALSE;
      }
    }

    return array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );
  }

}

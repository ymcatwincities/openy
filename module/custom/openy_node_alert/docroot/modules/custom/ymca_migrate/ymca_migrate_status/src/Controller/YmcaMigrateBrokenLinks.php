<?php

namespace Drupal\ymca_migrate_status\Controller;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;

/**
 * YmcaMigrateBrokenLinks controller.
 */
class YmcaMigrateBrokenLinks extends ControllerBase {

  /**
   * Legacy DB connection.
   *
   * @var Connection
   */
  private $dbLegacy = NULL;

  /**
   * DB connection.
   *
   * @var Connection
   */
  private $db = NULL;

  /**
   * Show the page.
   */
  public function pageView() {
    $this->dbLegacy = Database::getConnection('default', 'amm_source');
    $this->db = Database::getConnection();

    $data = $this->getData();

    $tokens = [];
    foreach ($data as $item) {
      foreach ($item['links'] as $token) {
        if (!isset($tokens[$token])) {
          $tokens[$token]['token'] = $token;
          $tokens[$token]['page'] = $this->getPageAddress($token);
          $tokens[$token]['count'] = 1;
        }
        else {
          $tokens[$token]['count']++;
        }

        $places = $item['bundle'] . ':' . $item['entity_id'] . ' ';
        if (!isset($tokens[$token]['places'])) {
          $tokens[$token]['places'] = $places;
        }
        else {
          $tokens[$token]['places'] .= $places;
        }
      }
    }

    // Sort.
    usort($tokens, function ($a, $b) {
      return SortArray::sortByKeyInt($b, $a, 'count');
    });

    return array(
      'info' => [
        '#markup' => t('Total number of non-replaced tokens: @number', ['@number' => count($data)]),
      ],
      'table' => [
        '#header' => ['Token ID', 'Address', 'Count', 'Places'],
        '#theme' => 'table',
        '#rows' => $tokens,
      ],
    );
  }

  /**
   * Get page address by id.
   *
   * @param int $id
   *   Page ID.
   *
   * @return mixed
   *   Page address.
   */
  private function getPageAddress($id) {
    $field = $this->dbLegacy->select('amm_site_page', 'p')
      ->fields('p', ['page_subdirectory'])
      ->condition('p.site_page_id', $id)
      ->execute()
      ->fetchField();

    return $field;
  }

  /**
   * Get broken links data.
   *
   * @return array
   *   Broken links data.
   */
  private function getData() {
    $result = [];

    $tables = [
      'block_content__field_block_content',
      'node__field_camp_links',
      'node__field_content',
      'node__field_secondary_sidebar',
      'node__field_sidebar',
      'node__field_summary',
    ];

    foreach ($tables as $table) {
      $parts = explode('__', $table);
      $field = $parts[1] . '_value';
      $stmt = $this->db->select($table, 't')
        ->fields('t')
        ->condition('t.' . $field, '%' . $this->db->escapeLike('{{internal_page_link_') . '%', 'LIKE')
        ->execute();

      while ($data = $stmt->fetchObject()) {
        preg_match_all('/{{internal_page_link_(\d+)}}/miU', $data->$field, $test);
        $result[] = [
          'bundle' => $data->bundle,
          'entity_id' => $data->entity_id,
          'links' => !empty($test[1]) ? $test[1] : 0,
          'field' => $field,
        ];
      }
    }

    return $result;
  }

}

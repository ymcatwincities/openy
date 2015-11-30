<?php

/**
 * @file
 * Helper class to build queries for migrations.
 */

namespace Drupal\ymca_migrate\Plugin\migrate;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Query;

/**
 * Class YmcaQueryBuilder.
 */
class YmcaQueryBuilder {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection;
   */
  protected $database;

  /**
   * Query.
   *
   * @var Query
   */
  protected $query;

  /**
   * Required Ids to retrieve.
   *
   * @var array
   */
  protected $requiredIds = [];

  /**
   * YmcaQueryBuilder constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
    $this->initQuery();
  }

  /**
   * Get required IDs.
   *
   * @return array
   *   Required IDs.
   */
  protected function getRequiredIds() {
    return $this->requiredIds;
  }

  /**
   * Add IDs to be required.
   *
   * @param array $ids
   *   Array of IDs to add.
   */
  protected function addRequiredIds(array $ids) {
    $this->requiredIds = array_merge($this->getRequiredIds(), $ids);
  }

  /**
   * Initialize a query.
   */
  protected function initQuery() {
    $options['fetch'] = \PDO::FETCH_ASSOC;
    $this->query = $this->database->select('amm_site_page', 'p', $options);
    $this->query->fields(
      'p',
      [
        'site_page_id',
        'page_title',
        'page_name',
        'theme_id',
        'parent_id',
        'nav_level',
        'sequence_index',
        'exclude_from_nav',
      ]
    );
    $this->query->condition('is_redirect', 0);
    $this->query->condition('do_not_publish', 0);
    $this->query->orderBy('nav_level', 'ASC');
    $this->query->orderBy('sequence_index', 'ASC');
  }

  /**
   * Grab a list of all children for specific page ID.
   *
   * @param int $id
   *   ID to process.
   *
   * @return array|bool
   *   Array of IDs. FALSE if no.
   */
  public function getAllChildren($id = 0) {
    // Do not get children for zero ID.
    if ($id == 0) {
      return FALSE;
    }

    // Skip if ID is already in required IDs.
    if (in_array($id, $this->getRequiredIds())) {
      return FALSE;
    }

    $this->addRequiredIds([$id]);
    $this->query->condition('parent_id', $this->getRequiredIds(), 'IN');
    $all_ids = $this->query->execute()->fetchAll();

    $this->initQuery();
    if (!empty($all_ids)) {
      foreach ($all_ids as $sub_id_key => $sub_id_data) {
        $this->getAllChildren((int) $sub_id_data['site_page_id']);
      }
    }

    $this->initQuery();
    $this->query->condition('site_page_id', $this->getRequiredIds(), 'IN');

    return $this->query;
  }

  /**
   * Build query by flat list of IDs.
   *
   * @param array $ids
   *   An array of IDs.
   *
   * @return \Drupal\Core\Database\Query\Query
   *   Query.
   */
  public function getList(array $ids) {
    $this->addRequiredIds($ids);
    $this->query->condition('site_page_id', $this->getRequiredIds(), 'IN');
    return $this->query;
  }

}

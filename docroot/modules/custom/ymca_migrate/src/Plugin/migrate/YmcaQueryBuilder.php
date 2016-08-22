<?php

namespace Drupal\ymca_migrate\Plugin\migrate;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Query;

/**
 * Class YmcaQueryBuilder.
 */
class YmcaQueryBuilder {

  use YmcaMigrateTrait;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
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

    // We've got a global list of IDs to skip.
    $this->skipIds($this->getSkippedPages());
  }

  /**
   * Build a query.
   *
   * @return \Drupal\Core\Database\Query\Query
   *   A query ready to use.
   */
  public function build() {
    return $this->query;
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
        'page_subdirectory',
      ]
    );
    $this->query->condition('is_redirect', 0);
    $this->query->condition('do_not_publish', 0);
    $this->query->isNull('p.backup_of_site_page_id');
    $this->query->orderBy('nav_level', 'ASC');
    $this->query->orderBy('sequence_index', 'ASC');
  }

  /**
   * Grab a list of all children for specific page ID.
   *
   * @param int $id
   *   ID to process.
   */
  public function getAllChildren($id = 0) {
    // Do not get children for zero ID.
    if ($id == 0) {
      return;
    }

    // Skip if ID is already in required IDs.
    if (in_array($id, $this->getRequiredIds())) {
      return;
    }

    $this->initQuery();
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
  }

  /**
   * Build query by flat list of IDs.
   *
   * @param array $ids
   *   An array of IDs.
   */
  public function getList(array $ids) {
    $this->addRequiredIds($ids);
    $this->query->condition('site_page_id', $this->getRequiredIds(), 'IN');
  }

  /**
   * Build query by selecting specific bundle.
   *
   * @param string $bundle
   *   Bundle name.
   */
  public function getByBundle($bundle) {
    $this->query->condition('theme_id', $this->getThemesByBundle($bundle), 'IN');
  }

  /**
   * Get theme IDs by bundle.
   *
   * @param string $bundle
   *   Bundle name.
   *
   * @return array
   *   Array of theme IDs.
   */
  private function getThemesByBundle($bundle) {
    // @todo: Deal with pages for themes 23, 29, 17, 19
    $data = [
      'page' => [22, 23, 29, 17, 19],
      'location' => [24],
      'camp' => [18],
    ];

    if (!array_key_exists($bundle, $data)) {
      return [];
    }

    return $data[$bundle];
  }

  /**
   * Add IDs to skip.
   *
   * @param array $ids
   *   An array of IDs.
   */
  public function skipIds(array $ids) {
    $this->query->condition('site_page_id', $ids, 'NOT IN');
  }

  /**
   * Add range to the query.
   *
   * @param int $start
   *   Start.
   * @param int $length
   *   Length.
   */
  public function setRange($start, $length) {
    $this->query->range($start, $length);
  }

}

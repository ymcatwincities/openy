<?php
/**
 * @file
 * Class for getting page's children tree by top menu item.
 */

namespace Drupal\ymca_migrate\Plugin\migrate;


use Drupal\Core\Database\Connection;

/**
 * Class YmcaPagesQuery.
 *
 * @package Drupal\ymca_migrate\Plugin\migrate
 */
class YmcaPagesQuery extends AmmPagesQuery {

  /**
   * Database to be used as active.
   *
   * @var \Drupal\migrate\Plugin\migrate\source\SqlBase
   */
  protected $database;

  /**
   * Tree of pages.
   *
   * $var array.
   */
  protected $tree;

  /**
   * Singleton instance.
   *
   * @var \Drupal\ymca_migrate\Plugin\migrate\YmcaPagesQuery
   */
  static private $instance;

  /**
   * If the ID has children.
   *
   * @var bool
   */
  private $hasChildren;

  protected $query;

  /**
   * YmcaBlogComponentsTree constructor.
   *
   * @param array $skip_ids
   *   Array of IDs to be skipped.
   * @param array $needed_ids
   *   Array of IDs to be added to tree creation.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   */
  protected function __construct($skip_ids, $needed_ids, Connection $database) {
    // @todo Rethink if we can get rid of skip and needed IDs within constructor.
    $this->database = &$database;
    $this->tree = [];
    // Let's by default have no children.
    $this->hasChildren = FALSE;

    $options['fetch'] = \PDO::FETCH_ASSOC;
    $this->query = $this->database->select('amm_site_page', 'p', $options);
    $this->query->fields(
      'p',
      [
        'site_page_id',
        'page_title',
        'theme_id',
        'parent_id',
      ]
    );
    parent::__construct('page', $skip_ids, $needed_ids);
  }

  /**
   * Method for init query with select parameters after fetch*() methods.
   */
  private function initQuery() {
    // Initialize query single time.
    $options['fetch'] = \PDO::FETCH_ASSOC;
    $this->query = $this->database->select('amm_site_page', 'p', $options);
    $this->query->fields('p',
      [
        'site_page_id',
        'page_title',
        'page_name',
        'theme_id',
        'parent_id',
        'nav_level',
        'sequence_index',
        'exclude_from_nav',
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryByParent($id = NULL) {
    if ($id) {
      return $this->getAllChildren($id);
    }

    // Pages for menu migration.
    $this->setNeededIds(
      [
        4802,
        4804,
        4805,
        4806,
        4807,
        4747,
        20256,
        8601,
        4748,
        4750,
        15737,
        15840,
        15841,
        15842,
        15739,
        22710,
        22712,
        22713,
        23010,
        22714,
        23694,
        23692,
        24048,
        23691,
        23695,
        5303,
        5304,
        5305,
        5283,
        5284
      ]
    );

    // Page dependencies for blog.
    $this->setNeededIds(
      [
        4633,
        8140,
        6708,
        8056,
        4664,
        4595,
        7185,
        4562,
        18408,
        4684,
      ]
    );

    $this->query->condition('site_page_id', $this->getNeededIds(), 'IN');
    $skipped_ids = $this->getSkippedIds();
    if (!empty($skipped_ids)) {
      $this->query->condition(
        'site_page_id',
        $skipped_ids,
        'NOT IN'
      );
    }
    return $this->query;
  }

  /**
   * {@inheritdoc}
   */
  static public function init($skip_ids, $needed_ids, Connection $database) {
    if (isset(self::$instance)) {
      return self::$instance;
    }
    self::$instance = new self($skip_ids, $needed_ids, $database);
    return self::$instance;
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

    // @todo optimize this for /admin/structure/migrate/manage/ymca/migrations
    if ($id == 0) {
      $this->hasChildren = FALSE;
      return FALSE;
    }
    if ($this->isInNeeded($id)) {
      return FALSE;
    }
    $is_updated = $this->setNeededIds(array((int) $id));
    if ($is_updated === FALSE) {
      return FALSE;
    }
    // If no new addition, return.

    $this->query->condition('parent_id', $this->getNeededIds(), 'IN');
    $all_ids = $this->query->execute()->fetchAll();

    $this->initQuery();
    if (!empty($all_ids)) {
      foreach ($all_ids as $sub_id_key => $sub_id_data) {
        $this->getAllChildren((int) $sub_id_data['site_page_id']);
      }
    }
    // @todo @danylevskyi check for abandoned pages within source DB.
    // $this->initQuery();
    // $this->query->condition('site_page_id', array_merge($this->getNeededIds(), $this->getSkippedIds()), 'NOT IN');
    // $abandoned_ids = $this->query->execute()->fetchAll();
    // log this.

    $this->initQuery();
    $this->query->condition('site_page_id', $this->getNeededIds(), 'IN');
    $this->query->condition('theme_id', $this->getThemesIds('page'), 'IN');
    $skipped_ids = $this->getSkippedIds();

    if (!empty($skipped_ids)) {
      $this->query->condition(
        'site_page_id',
        $skipped_ids,
        'NOT IN'
      );
    }

    // Add order.
    $this->query->orderBy('nav_level', 'ASC');
    $this->query->orderBy('sequence_index', 'ASC');

    return $this->query;
  }

  /**
   * Get theme ids by bundle.
   *
   * @param string $bundle
   *   Content type.
   *
   * @return array
   *   A list of theme ids.
   */
  public function getThemesIds($bundle) {
    $data = [
      'page' => [22, 23, 29, 31, 17, 19],
      'location' => [24],
      'camp' => [18],
    ];

    if (!array_key_exists($bundle, $data)) {
      return [];
    }

    return $data[$bundle];
  }

  /**
   * Check if ID is already in needed array.
   *
   * @param int $id
   *   ID.
   *
   * @return bool
   *   TRUE if already in, FALSE otherwise.
   */
  private function isInNeeded($id) {
    if (array_search($id, $this->getNeededIds())) {
      return TRUE;
    }
    return FALSE;
  }

}

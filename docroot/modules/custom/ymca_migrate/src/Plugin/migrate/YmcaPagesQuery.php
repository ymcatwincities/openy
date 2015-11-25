<?php
/**
 * @file
 * Class for getting page's children tree by top menu item.
 */

namespace Drupal\ymca_migrate\Plugin\migrate;


use Drupal\migrate\Plugin\migrate\source\SqlBase;

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
   * @param \Drupal\migrate\Plugin\migrate\source\SqlBase $database
   *   SqlBase plugin for dealing with DB.
   */
  protected function __construct($skip_ids, $needed_ids, SqlBase &$database) {
    // @todo Rethink if we can get rid of skip and needed IDs within constructor.
    $this->database = &$database;
    $this->tree = [];
    // Let's by default have no children.
    $this->hasChildren = FALSE;

    $options['fetch'] = \PDO::FETCH_ASSOC;
    $this->query = &$this->database->getDatabase()->select('amm_site_page', 'p', $options);
    $this->query->fields('p',
      [
        'site_page_id',
        'page_title',
        'theme_id',
        'parent_id',
      ]);
    parent::__construct('page', $skip_ids, $needed_ids);
  }

  /**
   * Method for init query with select parameters after fetch*() methods.
   */
  private function initQuery() {
    $ymca_blogs_query = YmcaBlogsQuery::init($this->database, $this->query);
    // Initialize query single time.
    $options['fetch'] = \PDO::FETCH_ASSOC;
    $this->query = &$this->database->getDatabase()->select('amm_site_page', 'p', $options);
    $this->query->fields('p',
      [
        'site_page_id',
        'page_title',
        'theme_id',
        'parent_id',
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryByParent($id = NULL) {
    if ($id) {
      return $this->getAllChildren($id);
    }

    // Pages with single component type. Theme THEME_INTERNAL_CATEGORY_AND_DETAIL.
    $this->setNeededIds(
      [5264,
        5234,
        22703,
        4803,
        5266,
        15462,
        5098,
        5267,
        5295,
        18074,
        18081,
        5297,
        15752,
        5298,
        5245,
        5284,
        5300,
        5285,
        6871,
        5286,
        5304,
        6130,
        6872,
        5250,
        5287,
        5305,
        6136,
        5254,
        6874,
        13767,
        16870,
        19147,
        5290,
        6876,
        6828,
        6877
      ]
    );
    // Pages with 2 component type. Theme THEME_INTERNAL_CATEGORY_AND_DETAIL.
    $this->setNeededIds(
      [4811,
        5105,
        13828,
        15843,
        23217,
        4670,
        4812,
        6873,
        13830,
        17304,
        18891,
        23439,
        24946,
        4813,
        5185,
        5204,
        13832,
        15853,
        17305,
        15855,
        17307,
        4815,
        5152,
        6827,
        13836,
        17308,
        21306,
        22699,
        5232,
        17309,
        21311,
        22700,
        5133,
        5172,
        5210,
        6714,
        17310,
        5096,
        5134,
        5191,
        5265,
        17323,
        19440,
        25185,
        4941,
        5097,
        5237,
        15862,
        17064,
        17324,
        24462,
        4942,
        5159,
        5238,
        6735,
        22438,
        4805,
        4943,
        5099,
        5115,
        5239,
        6853,
        15872,
        22463,
        25247,
        5217,
        5241,
        15873,
        18145,
        5139,
        5179,
        5198,
        5242,
        24732,
        4808,
        12856,
        14283,
        15840,
        22728,
        4809,
        5145,
        5164,
        20068,
        24941,
        4810,
        5124,
        5201,
        5222
      ]
    );
    // Pages for menu migration.
    $this->setNeededIds(
      [
        '4802',
        '4804',
        '4805',
        '4806',
        '4807',
        '4747',
        '20256',
        '8601',
        '4748',
        '4750',
        '15737',
        '15840',
        '15841',
        '15842',
        '15739',
        '22710',
        '22712',
        '22713',
        '23010',
        '22714',
        '23694',
        '23692',
        '24048',
        '23691',
        '23695',
        '5303',
        '5304',
        '5305',
        '5283',
        '5284'
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
   * Setter for local SqlBase.
   *
   * @param \Drupal\migrate\Plugin\migrate\source\SqlBase $migrate_database
   *   Reference to the plugin that is used current object.
   *
   * @return $this
   *   Self.
   */
  public function setSqlBase(SqlBase &$migrate_database) {
    $this->database = $migrate_database;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  static public function init(
    $skip_ids,
    $needed_ids,
    SqlBase &$migrate_database
  ) {
    if (isset(self::$instance)) {
      return self::$instance;
    }
    self::$instance = new self($skip_ids, $needed_ids, $migrate_database);
    self::$instance->setSqlBase($migrate_database);
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
    // @todo @danylevsky check for abandoned pages within source DB.
    // $this->initQuery();
    // $this->query->condition('site_page_id', array_merge($this->getNeededIds(), $this->getSkippedIds()), 'NOT IN');
    // $abandoned_ids = $this->query->execute()->fetchAll();
    // log this.

    $this->initQuery();
    $this->query->condition('site_page_id', $this->getNeededIds(), 'IN');
    // @todo Add Pages only condition.
    // $this->query->condition('theme_id', $this->getThemesIds('page'), 'IN');
    // AmmPagesQuery should contain getThemesIds() method.
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

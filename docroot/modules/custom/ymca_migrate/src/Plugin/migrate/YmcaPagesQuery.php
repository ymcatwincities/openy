<?php
/**
 * @file
 * Class for getting children tree by top menu item.
 */

namespace Drupal\ymca_migrate\Plugin\migrate;


use Drupal\Core\Database\Connection;

/**
 * Class YmcaPagesQuery
 *
 * @package Drupal\ymca_migrate\Plugin\migrate
 */
class YmcaPagesQuery extends AmmPagesQuery {

  /**
   * Database to be used as active.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Tree of pages.
   *
   * $var array
   */
  protected $tree;

  /**
   * Singleton instance.
   *
   * @var \Drupal\ymca_migrate\Plugin\migrate\YmcaPagesQuery
   */
  static private $instance;

  /**
   * YmcaBlogComponentsTree constructor.
   *
   * @param array $skip_ids
   *   Array of IDs to be skipped.
   * @param array $needed_ids
   *   Array of IDs to be added to tree creation.
   * @param \Drupal\Core\Database\Connection $database
   *   SqlBase plugin for dealing with DB.
   * @param \Drupal\migrate\Row $row
   *   Row that is processed within a Tree
   *
   * @return \Drupal\ymca_migrate\Plugin\migrate\YmcaPagesQuery $this
   *   Returns itself.
   */
  protected function __construct($skip_ids, $needed_ids, Connection $database) {
    $this->database = $database;
    $this->tree = [];
    parent::__construct('page', $skip_ids, $needed_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery() {
//    SELECT *
//    FROM `abe_blog_post`
//    WHERE (`blog_post_id` = '5' OR `blog_post_id` = '9') AND `blog_post_id` != '5'
    // @codingStandardsIgnoreStart
    $query = $this->database->select('amm_site_page', 'p');
    $query->fields(
        'p',
        [
          'site_page_id',
          'page_title',
          'theme_id',
        ]
      );
      $query->condition(
        'site_page_id',
        [
          // Pages with single component type. Theme THEME_INTERNAL_CATEGORY_AND_DETAIL.
          5264,
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
          6877,
          // Pages with 2 component type. Theme THEME_INTERNAL_CATEGORY_AND_DETAIL.
          4811,
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
          5222,
          24055,
          // Pages for menu migration.
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
        ],
        'IN'
      );
    // @codingStandardsIgnoreEnd
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  static public function init(
    $skip_ids,
    $needed_ids,
    Connection $database
  ) {
    if (isset(self::$instance)) {
      return self::$instance;
    }
    return new self($skip_ids, $needed_ids, $database);
  }
}
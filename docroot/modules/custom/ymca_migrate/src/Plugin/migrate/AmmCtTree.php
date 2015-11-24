<?php
/**
 * Created by PhpStorm.
 * User: podarok
 * Date: 24.11.15
 * Time: 13:52
 */

namespace Drupal\ymca_migrate\Plugin\migrate;

/**
 * Class AmmCtTree
 *
 * @package Drupal\ymca_migrate
 */
abstract class AmmCtTree implements AmmCtTreeInterface {

  /**
   * CT type to process on within an object.
   *
   * @var string
   */
  protected $ctType;

  /**
   * Array of IDs to be skipped from tree creation.
   *
   * @var array
   */
  protected $skipIds;

  /**
   * AmmCtTree constructor.
   *
   * @param string $ctType
   *   String of Drupal CT machine name.
   * @param array $skipIds
   *   Array of IDs to be skipped from source DB.
   */
  protected function __construct($ctType, $skipIds = array()) {

    $this->ctType = $ctType;
    // By default we are working with all IDs.
    // Setting internal protected variable with an array of IDs to be skipped from a tree.
    $this->skipIds = $skipIds;
    // Should we use this in PHP?
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSkipIds(array $ids = array()) {
    // Updating array of IDs.
    $this->skipIds = array_merge($this->skipIds, $ids);
  }

  /**
   * This method should be implemented within child class.
   *
   * @return $this
   */
  abstract public function getTree();

}
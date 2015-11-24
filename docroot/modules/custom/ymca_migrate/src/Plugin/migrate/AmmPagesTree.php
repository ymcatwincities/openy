<?php
/**
 * @file
 * Pages tree.
 */

namespace Drupal\ymca_migrate\Plugin\migrate;

/**
 * Class AmmPagesTree
 *
 * @package Drupal\ymca_migrate
 */
abstract class AmmPagesTree implements AmmPagesTreeInterface {

  /**
   * CT type to process on within an object.
   *
   * @var string
   */
  protected $ct_type;

  /**
   * Array of IDs to be skipped from tree creation.
   *
   * @var array
   */
  protected $skip_ids;

  /**
   * Array of IDs to be added to tree creation.
   *
   * @var array
   */
  protected $needed_ids;

  /**
   * AmmPagesTree constructor.
   *
   * @param string $ct_type
   *   String of Drupal CT machine name.
   * @param array $skip_ids
   *   Array of IDs to be skipped from source DB.
   * @param array $needed_ids
   *   Array of IDs to be obtained from source DB.
   */
  protected function __construct($ct_type, $skip_ids = array(), $needed_ids = array()) {

    $this->ctType = $ct_type;
    // By default we are working with all IDs.
    // Setting internal protected variable with an array of IDs to be skipped from a tree.
    $this->skip_ids = $skip_ids;
    // Setting internal protected variable with an array of IDs to be added to a tree.
    $this->needed_ids = $needed_ids;
    // @todo Should we use this in PHP?
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setskip_ids(array $ids = array()) {
    // Updating array of IDs.
    $this->skip_ids = array_merge($this->skip_ids, $ids);
  }

  /**
   * {@inheritdoc}
   */
  public function setneeded_ids(array $ids = array()) {
    // Updating array of IDs.
    $this->needed_ids = array_merge($this->needed_ids, $ids);
  }

  /**
   * This method should be implemented within child class.
   *
   * @return $this
   */
  abstract public function getTree();

}

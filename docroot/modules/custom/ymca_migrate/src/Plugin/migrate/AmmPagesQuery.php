<?php
/**
 * @file
 * Pages tree.
 */

namespace Drupal\ymca_migrate\Plugin\migrate;

/**
 * Class AmmPagesQuery
 *
 * @package Drupal\ymca_migrate
 */
abstract class AmmPagesQuery implements AmmPagesQueryInterface {

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
   * AmmPagesQuery constructor.
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
    $this->setSkipIds($skip_ids);
    // Setting internal protected variable with an array of IDs to be added to a tree.
    $this->setNeededIds($needed_ids);
    // @todo Should we use this in PHP?
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSkipIds(array $ids = array()) {
    // Updating array of IDs.
    if (empty($this->skip_ids) && empty($ids)) {
      $this->skip_ids = array();
      return $this;
    }

    if (empty($this->skip_ids) && !empty($ids)) {
      $this->skip_ids = $ids;
      return $this;
    }
    $this->skip_ids = array_merge($this->skip_ids, $ids);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setNeededIds(array $ids = array()) {
    // Updating array of IDs.
    $this->needed_ids = array_merge($this->needed_ids, $ids);
    return $this;
  }

  /**
   * Get list of IDs to be processed.
   */
  protected function getSkippedIds() {
    // Get list of skipped IDs cleared from needed ones.
    return array_diff_key($this->skip_ids, $this->needed_ids);
  }

  /**
   * Getter for needed IDs.
   *
   * @return array
   *   Return needed IDs.
   */
  protected function getNeededIds() {
    return $this->needed_ids;
  }


  /**
   * This method should be implemented within child class.
   *
   * @return $this
   */
  abstract public function getQuery();

}

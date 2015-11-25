<?php
/**
 * @file
 * Pages tree.
 */

namespace Drupal\ymca_migrate\Plugin\migrate;

/**
 * Class AmmPagesQuery.
 *
 * @package Drupal\ymca_migrate
 */
abstract class AmmPagesQuery implements AmmPagesQueryInterface {

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
   * Array of IDs to be added to tree creation.
   *
   * @var array
   */
  protected $neededIds;

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
    if (empty($this->skipIds) && empty($ids)) {
      $this->skipIds = array();
      return $this;
    }

    if (empty($this->skipIds) && !empty($ids)) {
      $this->skipIds = $ids;
      return $this;
    }
    $this->skipIds = array_merge($this->skipIds, $ids);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setNeededIds(array $ids = array()) {

    // Emptying needed IDs array.
    if ($ids === array()) {
      $this->neededIds = array();
      return TRUE;
    }
    $old = $this->getNeededIds();
    if (empty($old) && !empty($ids)) {
      $this->neededIds = array_values($ids);
      return TRUE;
    }
    $this->neededIds = array_merge($this->neededIds, $ids);
    return TRUE;
  }

  /**
   * Get list of IDs to be processed.
   *
   * @return array|bool
   *   Returns array or FALSE if not set.
   */
  public function getSkippedIds() {
    // Get list of skipped IDs cleared from needed ones.
    if (!empty($this->skipIds)) {
      if (!empty($this->neededIds)) {
        return array_diff_key($this->skipIds, $this->neededIds);
      }
      return $this->skipIds;
    }
    return FALSE;
  }

  /**
   * Getter for needed IDs.
   *
   * @return array
   *   Return needed IDs.
   */
  public function getNeededIds() {
    if (isset($this->neededIds)) {
      return $this->neededIds;
    }
    $this->neededIds = array();
    return $this->neededIds;
  }


  /**
   * This method should be implemented within child class.
   *
   * @return $this
   */
  abstract public function getQueryByParent($id);

}

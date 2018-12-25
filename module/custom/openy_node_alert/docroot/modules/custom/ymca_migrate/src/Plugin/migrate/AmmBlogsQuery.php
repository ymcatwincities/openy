<?php

namespace Drupal\ymca_migrate\Plugin\migrate;

/**
 * Class AmmBlogsQuery.
 *
 * @package Drupal\ymca_migrate
 */
abstract class AmmBlogsQuery implements AmmBlogsQueryInterface {

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
   * AmmBlogsQuery constructor.
   *
   * @param string $ct_type
   *   String of Drupal CT machine name.
   */
  protected function __construct($ct_type) {
    $this->ctType = $ct_type;
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
  abstract public function getQuery();

}

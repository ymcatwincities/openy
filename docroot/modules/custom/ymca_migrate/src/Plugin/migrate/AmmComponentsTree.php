<?php
/**
 * @file
 * Components tree.
 */

namespace Drupal\ymca_migrate\Plugin\migrate;

/**
 * Class AmmComponentsTree
 *
 * @package Drupal\ymca_migrate
 */
abstract class AmmComponentsTree implements AmmComponentsTreeInterface {

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
   * AmmComponentsTree constructor.
   *
   * @param string $ct_type
   *   String of Drupal CT machine name.
   * @param array $skip_ids
   *   Array of IDs to be skipped from source DB.
   */
  protected function __construct($ct_type, $skip_ids = array()) {

    $this->ctType = $ct_type;
    // By default we are working with all IDs.
    // Setting internal protected variable with an array of IDs to be skipped from a tree.
    $this->skip_ids = $skip_ids;
    // Should we use this in PHP?
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSkipIds(array $ids = array()) {
    // Updating array of IDs.
    $this->skip_ids = array_merge($this->skip_ids, $ids);
    return $this;
  }

  /**
   * This method should be implemented within child class.
   *
   * @return $this
   */
  abstract public function getTree();

}

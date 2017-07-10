<?php

namespace Drupal\stage_file_proxy\EventDispatcher;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class AlterExcludedPathsEvent.
 *
 * @package Drupal\stage_file_proxy\EventDispatcher
 */
class AlterExcludedPathsEvent extends Event {

  /**
   * The array with paths to exclude.
   *
   * @var array
   */
  protected $excludedPaths;

  /**
   * Constructor.
   *
   * @param array $excluded_paths
   *   The excluded paths array.
   */
  public function __construct(array $excluded_paths) {
    $this->setExcludedPaths($excluded_paths);
  }

  /**
   * Getter for the excluded paths array.
   *
   * @return array
   *   The excluded paths array.
   */
  public function getExcludedPaths() {
    return $this->excludedPaths;
  }

  /**
   * Setter for the excluded paths array.
   *
   * @param array $excluded_paths
   *   The excluded paths array to set.
   */
  public function setExcludedPaths(array $excluded_paths) {
    $this->excludedPaths = $excluded_paths;
  }

  /**
   * Adds an excluded path to the excluded paths array.
   *
   * @param string $excluded_path
   *   The excluded path string to add.
   */
  public function addExcludedPath($excluded_path) {
    $this->excludedPath[] = $excluded_path;
  }

  /**
   * Adds an excluded path to the excluded paths array.
   *
   * @param string $excluded_path
   *   The excluded path string to add.
   */
  public function removeExcludedPath($excluded_path) {
    foreach (array_keys($this->excludedPaths, $excluded_path) as $key) {
      unset($this->excludedPaths[$key]);
    }
  }

}

<?php

namespace Drupal\file_entity;

use Drupal\file\FileInterface;

/**
 * File entity interface.
 */
interface FileEntityInterface extends FileInterface {

  /**
   * Gets the metadata property value.
   *
   * @param string $property
   *   A metadata property key.
   *
   * @return int|null
   *   A metadata property value.
   */
  public function getMetadata($property);

  /**
   * Determines whether or not metadata property exists.
   *
   * @param string $property
   *   A metadata property key.
   *
   * @return bool
   *   Returns TRUE if metadata property is set.
   */
  public function hasMetadata($property);

  /**
   * Sets the metadata property.
   *
   * @param string $property
   *   A metadata property key.
   *
   * @param int|null $value
   *   A metadata property value.
   */
  public function setMetadata($property, $value);

  /**
   * Gets all metadata properties.
   *
   * @return array
   *   An array of metadata properties.
   */
  public function getAllMetadata();
}

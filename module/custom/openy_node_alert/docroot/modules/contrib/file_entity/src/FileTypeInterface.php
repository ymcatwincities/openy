<?php

namespace Drupal\file_entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * File type entity interface.
 */
interface FileTypeInterface extends ConfigEntityInterface {
  /**
   * Get the description of this file type.
   *
   * @var string
   *   A brief description.
   */
  public function getDescription();

  /**
   * Get a MIME types associated with this file type.
   *
   * @var string[]
   *   An indexed array of MIME types.
   */
  public function getMimeTypes();

  /**
   * Set the label of this file type.
   *
   * @param string $label
   *   A label for the file type.
   */
  public function setLabel($label);

  /**
   * Set the description of this file type.
   *
   * @param string $description
   *   A brief description of the file type.
   */
  public function setDescription($description);

  /**
   * Set the MIME types associated with this file type.
   *
   * @param string[] $mimetypes
   *   An indexed array of MIME types that should be associated with this file
   *   type.
   */
  public function setMimeTypes($mimetypes);

  /**
   * Loads and returns all enabled file types.
   *
   * @param bool $status
   *   (optional) If FALSE, this loads disabled rather than enabled types.
   *
   * @return FileTypeInterface[]
   *   An array of entity objects indexed by their IDs.
   */
  public static function loadEnabled($status = TRUE);
}

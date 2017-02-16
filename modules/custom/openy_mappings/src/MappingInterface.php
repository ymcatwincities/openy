<?php

namespace Drupal\openy_mappings;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Mapping entities.
 *
 * @ingroup openy_mappings
 */
interface MappingInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Mapping type.
   *
   * @return string
   *   The Mapping type.
   */
  public function getType();

  /**
   * Gets the Mapping name.
   *
   * @return string
   *   Name of the Mapping.
   */
  public function getName();

  /**
   * Sets the Mapping name.
   *
   * @param string $name
   *   The Mapping name.
   *
   * @return \Drupal\openy_mappings\MappingInterface
   *   The called Mapping entity.
   */
  public function setName($name);

  /**
   * Gets the Mapping creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Mapping.
   */
  public function getCreatedTime();

  /**
   * Sets the Mapping creation timestamp.
   *
   * @param int $timestamp
   *   The Mapping creation timestamp.
   *
   * @return \Drupal\openy_mappings\MappingInterface
   *   The called Mapping entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Mapping published status indicator.
   *
   * Unpublished Mapping are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Mapping is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Mapping.
   *
   * @param bool $published
   *   TRUE to set this Mapping to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\openy_mappings\MappingInterface
   *   The called Mapping entity.
   */
  public function setPublished($published);

}

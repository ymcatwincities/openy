<?php

namespace Drupal\logger_entity\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Logger Entity entities.
 *
 * @ingroup logger_entity
 */
interface LoggerEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Logger Entity type.
   *
   * @return string
   *   The Logger Entity type.
   */
  public function getType();

  /**
   * Gets the Logger Entity name.
   *
   * @return string
   *   Name of the Logger Entity.
   */
  public function getName();

  /**
   * Sets the Logger Entity name.
   *
   * @param string $name
   *   The Logger Entity name.
   *
   * @return \Drupal\logger_entity\Entity\LoggerEntityInterface
   *   The called Logger Entity entity.
   */
  public function setName($name);

  /**
   * Gets the Logger Entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Logger Entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Logger Entity creation timestamp.
   *
   * @param int $timestamp
   *   The Logger Entity creation timestamp.
   *
   * @return \Drupal\logger_entity\Entity\LoggerEntityInterface
   *   The called Logger Entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Logger Entity published status indicator.
   *
   * Unpublished Logger Entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Logger Entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Logger Entity.
   *
   * @param bool $published
   *   TRUE to set this entity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\logger_entity\Entity\LoggerEntityInterface
   *   The called Logger Entity entity.
   */
  public function setPublished($published);

  /**
   * Gets data.
   *
   * @return array
   *   Data.
   */
  public function getData();

  /**
   * Sets data.
   *
   * @param array $data
   *   Data.
   *
   * @return \Drupal\logger_entity\Entity\LoggerEntityInterface
   *   The called Logger Entity entity.
   */
  public function setData(array $data);

}

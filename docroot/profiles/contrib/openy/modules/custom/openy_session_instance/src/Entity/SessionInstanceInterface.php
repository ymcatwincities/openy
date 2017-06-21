<?php

namespace Drupal\openy_session_instance\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining Session Instance entities.
 *
 * @ingroup openy_session_instance
 */
interface SessionInstanceInterface extends ContentEntityInterface {

  /**
   * Gets the Session Instance name.
   *
   * @return string
   *   Name of the Session Instance.
   */
  public function getName();

  /**
   * Sets the Session Instance name.
   *
   * @param string $name
   *   The Session Instance name.
   *
   * @return \Drupal\openy_session_instance\Entity\SessionInstanceInterface
   *   The called Session Instance entity.
   */
  public function setName($name);

  /**
   * Gets the Session Instance creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Session Instance.
   */
  public function getCreatedTime();

  /**
   * Sets the Session Instance creation timestamp.
   *
   * @param int $timestamp
   *   The Session Instance creation timestamp.
   *
   * @return \Drupal\openy_session_instance\Entity\SessionInstanceInterface
   *   The called Session Instance entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Retrieves Session Instance 'from' timestamp.
   *
   * @return int
   *   The Session Instance 'from' timestamp.
   */
  public function getTimestamp();

  /**
   * Retrieves Session Instance 'to' timestamp.
   *
   * @return int
   *   The Session Instance 'to' timestamp.
   */
  public function getTimestampTo();

}

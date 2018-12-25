<?php

namespace Drupal\openy_digital_signage_room\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining Digital Signage Room entities.
 *
 * @ingroup openy_digital_signage_room
 */
interface OpenYRoomInterface extends ContentEntityInterface {

  /**
   * Gets Digital Signage Room title.
   *
   * @return string
   *   Name of the Digital Signage Room title.
   */
  public function getName();

  /**
   * Sets Digital Signage Room title.
   *
   * @param string $name
   *   Digital Signage Room title.
   *
   * @return \Drupal\openy_digital_signage_room\Entity\OpenYRoomInterface
   *   The called Digital Signage Room title entity.
   */
  public function setName($name);

  /**
   * Gets Digital Signage Room created timestamp.
   *
   * @return int
   *   Created timestamp of the Digital Signage Room.
   */
  public function getCreatedTime();

  /**
   * Sets Digital Signage Room created timestamp.
   *
   * @param int $timestamp
   *   Digital Signage Room creation timestamp.
   *
   * @return \Drupal\openy_digital_signage_room\Entity\OpenYRoomInterface
   *   The called Digital Signage Room entity.
   */
  public function setCreatedTime($timestamp);

}

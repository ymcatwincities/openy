<?php

namespace Drupal\openy_digital_signage_schedule\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining OpenY Digital Signage Schedule entities.
 *
 * @ingroup openy_digital_signage
 */
interface OpenYScheduleInterface extends ContentEntityInterface {

  /**
   * Gets the OpenY Digital Signage Schedule name.
   *
   * @return string
   *   Name of the OpenY Digital Signage Schedule.
   */
  public function getName();

  /**
   * Sets the OpenY Digital Signage Schedule name.
   *
   * @param string $name
   *   The OpenY Digital Signage Schedule name.
   *
   * @return \Drupal\openy_digital_signage_schedule\Entity\OpenYScheduleInterface
   *   The called OpenY Digital Signage Screen entity.
   */
  public function setName($name);

  /**
   * Gets the OpenY Digital Signage Schedule creation timestamp.
   *
   * @return int
   *   Creation timestamp of the OpenY Digital Signage Schedule.
   */
  public function getCreatedTime();

  /**
   * Sets the OpenY Digital Signage Schedule creation timestamp.
   *
   * @param int $timestamp
   *   The OpenY Digital Signage Schedule creation timestamp.
   *
   * @return \Drupal\openy_digital_signage_schedule\Entity\OpenYScheduleInterface
   *   The called OpenY Digital Signage Schedule entity.
   */
  public function setCreatedTime($timestamp);

}

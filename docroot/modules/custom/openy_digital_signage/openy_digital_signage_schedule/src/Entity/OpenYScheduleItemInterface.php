<?php

namespace Drupal\openy_digital_signage_schedule\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining OpenY Digital Signage Schedule Item entities.
 *
 * @ingroup openy_digital_signage
 */
interface OpenYScheduleItemInterface extends ContentEntityInterface {

  /**
   * Gets the OpenY Digital Signage Schedule Item name.
   *
   * @return string
   *   Name of the OpenY Digital Signage Schedule Item.
   */
  public function getName();

  /**
   * Sets the OpenY Digital Signage Schedule Item name.
   *
   * @param string $name
   *   The OpenY Digital Signage Schedule Item name.
   *
   * @return \Drupal\openy_digital_signage_schedule\Entity\OpenYScheduleInterface
   *   The called OpenY Digital Signage Screen Item entity.
   */
  public function setName($name);

  /**
   * Gets the OpenY Digital Signage Schedule Item creation timestamp.
   *
   * @return int
   *   Creation timestamp of the OpenY Digital Signage Schedule Item.
   */
  public function getCreatedTime();

  /**
   * Sets the OpenY Digital Signage Schedule Item creation timestamp.
   *
   * @param int $timestamp
   *   The OpenY Digital Signage Schedule Item creation timestamp.
   *
   * @return \Drupal\openy_digital_signage_schedule\Entity\OpenYScheduleItemInterface
   *   The called OpenY Digital Signage Schedule Item entity.
   */
  public function setCreatedTime($timestamp);

}

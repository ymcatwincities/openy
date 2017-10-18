<?php

namespace Drupal\openy_digital_signage_personify_schedule\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining Digital Signage Classes Personify Session entities.
 *
 * @ingroup openy_digital_signage_personify_schedule
 */
interface OpenYClassesPersonifySessionInterface extends ContentEntityInterface {

  /**
   * Gets Digital Signage Classes Session title.
   *
   * @return string
   *   Name of the Digital Signage Classes Session title.
   */
  public function getName();

  /**
   * Sets Digital Signage Classes Personify Session title.
   *
   * @param string $name
   *   Digital Signage Classes Personify Session title.
   *
   * @return \Drupal\openy_digital_signage_personify_schedule\Entity\OpenYClassesPersonifySessionInterface
   *   The called Digital Signage Classes Personify Session title entity.
   */
  public function setName($name);

  /**
   * Gets Digital Signage Classes Personify Session creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Digital Signage Classes Personify Session.
   */
  public function getCreatedTime();

  /**
   * Sets Digital Signage Classes Session creation timestamp.
   *
   * @param int $timestamp
   *   Digital Signage Classes Session creation timestamp.
   *
   * @return \Drupal\openy_digital_signage_personify_schedule\Entity\OpenYClassesPersonifySessionInterface
   *   The called Digital Signage Classes Personify Session entity.
   */
  public function setCreatedTime($timestamp);

}

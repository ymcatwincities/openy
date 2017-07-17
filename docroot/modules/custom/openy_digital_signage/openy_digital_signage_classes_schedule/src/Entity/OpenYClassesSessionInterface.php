<?php

namespace Drupal\openy_digital_signage_classes_schedule\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining Digital Signage Classes Session entities.
 *
 * @ingroup openy_digital_signage_classes_schedule
 */
interface OpenYClassesSessionInterface extends ContentEntityInterface {

  /**
   * Gets Digital Signage Classes Session title.
   *
   * @return string
   *   Name of the Digital Signage Classes Session title.
   */
  public function getName();

  /**
   * Sets Digital Signage Classes Session title.
   *
   * @param string $name
   *   Digital Signage Classes Session title.
   *
   * @return \Drupal\openy_digital_signage_classes_schedule\Entity\OpenYClassesSessionInterface
   *   The called Digital Signage Classes Session title entity.
   */
  public function setName($name);

  /**
   * Gets Digital Signage Classes Session creation timestamp.
   *
   * @return int
   *   Creation timestamp of the OpenY Digital Signage Classes Schedule.
   */
  public function getCreatedTime();

  /**
   * Sets Digital Signage Classes Session creation timestamp.
   *
   * @param int $timestamp
   *   Digital Signage Classes Session creation timestamp.
   *
   * @return \Drupal\openy_digital_signage_classes_schedule\Entity\OpenYClassesSessionInterface
   *   The called Digital Signage Classes Session entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets Digital Signage Classes Session source value.
   *
   * @return string
   *   Source value of the Digital Signage Classes Session.
   */
  public function getSource();

  /**
   * Set Digital Signage Classes Session source value.
   *
   * @param string $source
   *   Digital Signage Classes Session source value.
   *
   * @return \Drupal\openy_digital_signage_classes_schedule\Entity\OpenYClassesSessionInterface
   *   The called Digital Signage Classes Session title entity.
   */
  public function setSource($source);

  /**
   * Return status is session overridden or not.
   *
   * @return bool
   *   Overridden status.
   */
  public function isOverridden();

}

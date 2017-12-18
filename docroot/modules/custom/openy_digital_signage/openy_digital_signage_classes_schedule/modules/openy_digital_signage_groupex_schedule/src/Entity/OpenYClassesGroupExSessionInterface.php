<?php

namespace Drupal\openy_digital_signage_groupex_schedule\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining Digital Signage Classes GroupEx Pro Session entities.
 *
 * @ingroup openy_digital_signage_groupex_schedule
 */
interface OpenYClassesGroupExSessionInterface extends ContentEntityInterface {

  /**
   * Gets Digital Signage Classes Session title.
   *
   * @return string
   *   Name of the Digital Signage Classes Session title.
   */
  public function getName();

  /**
   * Sets Digital Signage Classes GroupEx Pro Session title.
   *
   * @param string $name
   *   Digital Signage Classes GroupEx Pro Session title.
   *
   * @return \Drupal\openy_digital_signage_groupex_schedule\Entity\OpenYClassesGroupExSessionInterface
   *   The called Digital Signage Classes GroupEx Pro Session title entity.
   */
  public function setName($name);

  /**
   * Gets Digital Signage Classes GroupEx Pro Session creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Digital Signage Classes GroupEx Pro Session.
   */
  public function getCreatedTime();

  /**
   * Sets Digital Signage Classes Session creation timestamp.
   *
   * @param int $timestamp
   *   Digital Signage Classes Session creation timestamp.
   *
   * @return \Drupal\openy_digital_signage_groupex_schedule\Entity\OpenYClassesGroupExSessionInterface
   *   The called Digital Signage Classes GroupEx Pro Session entity.
   */
  public function setCreatedTime($timestamp);

}

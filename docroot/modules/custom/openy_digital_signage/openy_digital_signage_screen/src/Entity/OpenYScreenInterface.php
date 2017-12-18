<?php

namespace Drupal\openy_digital_signage_screen\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining OpenY Digital Signage Screen entities.
 *
 * @ingroup openy_digital_signage
 */
interface OpenYScreenInterface extends ContentEntityInterface {

  /**
   * Gets the OpenY Digital Signage Screen name.
   *
   * @return string
   *   Name of the OpenY Digital Signage Screen.
   */
  public function getName();

  /**
   * Sets the OpenY Digital Signage Screen name.
   *
   * @param string $name
   *   The OpenY Digital Signage Screen name.
   *
   * @return \Drupal\openy_digital_signage_screen\Entity\OpenYScreenInterface
   *   The called OpenY Digital Signage Screen entity.
   */
  public function setName($name);

  /**
   * Gets the OpenY Digital Signage Screen creation timestamp.
   *
   * @return int
   *   Creation timestamp of the OpenY Digital Signage Screen.
   */
  public function getCreatedTime();

  /**
   * Sets the OpenY Digital Signage Screen creation timestamp.
   *
   * @param int $timestamp
   *   The OpenY Digital Signage Screen creation timestamp.
   *
   * @return \Drupal\openy_digital_signage_screen\Entity\OpenYScreenInterface
   *   The called OpenY Digital Signage Screen entity.
   */
  public function setCreatedTime($timestamp);

}

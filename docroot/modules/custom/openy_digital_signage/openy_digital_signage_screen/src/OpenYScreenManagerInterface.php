<?php

namespace Drupal\openy_digital_signage_screen;

/**
 * Interface OpenYScreenManagerInterface.
 *
 * @package Drupal\openy_digital_signage_screen
 */
interface OpenYScreenManagerInterface {

  /**
   * Dummy method.
   */
  public function dummy();

  /**
   * Return screen contenxt.
   *
   * @return \Drupal\Core\Entity\EntityInterface|mixed|null
   *   Screen context.
   */
  public function getScreenContext();

}

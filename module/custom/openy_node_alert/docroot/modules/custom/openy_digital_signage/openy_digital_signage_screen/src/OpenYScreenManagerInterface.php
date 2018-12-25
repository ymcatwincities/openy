<?php

namespace Drupal\openy_digital_signage_screen;

/**
 * Interface OpenYScreenManagerInterface.
 *
 * @ingroup openy_digital_signage_screen
 */
interface OpenYScreenManagerInterface {

  /**
   * Return screen contenxt.
   *
   * @return \Drupal\Core\Entity\EntityInterface|mixed|null
   *   Screen context.
   */
  public function getScreenContext();

}

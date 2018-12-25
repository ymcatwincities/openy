<?php

namespace Drupal\local_fonts\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Custom Font entities.
 */
interface LocalFontConfigEntityInterface extends ConfigEntityInterface {

  /**
   * Gets the Font Woff File Data.
   *
   * @return string
   *   Woff Font File Data.
   */
  public function getFontWoffData();

  /**
   * Sets the Font Woff File Data.
   *
   * @param string $data
   *   Woff Font File Data.
   *
   * @return \Drupal\local_fonts\Entity\LocalFontConfigEntityInterface
   *   The called Local Font Entity Interface.
   */
  public function setFontWoffData($data);

}

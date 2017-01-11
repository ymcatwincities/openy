<?php

namespace Drupal\purge\Plugin\Purge\Purger;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for purgers storing settings through config entities.
 */
interface PurgerSettingsInterface extends ConfigEntityInterface {

  /**
   * Either loads or creates the settings entity depending its existence.
   *
   * @param string $id
   *   Unique instance ID of the purger.
   *
   * @return \Drupal\purge\Plugin\Purge\Purger\PurgerSettingsInterface.
   */
  public static function load($id);

}

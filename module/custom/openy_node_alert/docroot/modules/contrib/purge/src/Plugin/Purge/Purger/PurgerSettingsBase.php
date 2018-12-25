<?php

namespace Drupal\purge\Plugin\Purge\Purger;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\purge\Plugin\Purge\Purger\PurgerSettingsInterface;

/**
 * Provides an interface for purgers storing settings through config entities.
 */
abstract class PurgerSettingsBase extends ConfigEntityBase implements PurgerSettingsInterface {

  /**
   * Unique purger instance ID.
   *
   * @var string
   */
  protected $id;

  /**
   * {@inheritdoc}
   */
  public static function load($id) {
    if (!($settings = parent::load($id))) {
      $settings = self::create(['id' => $id]);
    }
    return $settings;
  }

}

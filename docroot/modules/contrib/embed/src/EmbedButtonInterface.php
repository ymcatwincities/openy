<?php

namespace Drupal\embed;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a embed button entity.
 */
interface EmbedButtonInterface extends ConfigEntityInterface {

  /**
   * Returns the associated embed type.
   *
   * @return string
   *   Machine name of the embed type.
   */
  public function getTypeId();

  /**
   * Returns the label of the associated embed type.
   *
   * @return string
   *   Human readable label of the embed type.
   */
  public function getTypeLabel();

  /**
   * Returns the plugin of the associated embed type.
   *
   * @return \Drupal\embed\EmbedType\EmbedTypeInterface
   *   The plugin of the embed type.
   */
  public function getTypePlugin();

  /**
   * Gets the value of an embed type setting.
   *
   * @param string $key
   *   The setting name.
   * @param mixed $default
   *   The default value.
   *
   * @return mixed
   *   The value.
   */
  public function getTypeSetting($key, $default = NULL);

  /**
   * Gets all embed type settings.
   *
   * @return array
   *   An array of key-value pairs.
   */
  public function getTypeSettings();

  /**
   * Returns the button's icon file.
   *
   * @return \Drupal\file\FileInterface
   *   The file entity of the button icon.
   */
  public function getIconFile();

  /**
   * Returns the URL of the button's icon.
   *
   * If no icon file is associated with this Embed Button entity, the embed type
   * plugin's default icon is used.
   *
   * @return string
   *   The URL of the button icon.
   */
  public function getIconUrl();

}

<?php

namespace Drupal\fontyourface;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Font entities.
 *
 * @ingroup fontyourface
 */
interface FontInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the Font provider ID.
   *
   * @return string
   *   Font provider ID.
   */
  public function getProvider();

  /**
   * Sets the Font provider ID.
   *
   * @param string $provider
   *   The Font provider ID.
   *
   * @return \Drupal\fontyourface\FontInterface
   *   The called Font entity.
   */
  public function setProvider($provider);

  /**
   * Gets the Font metadata.
   *
   * @return mixed
   *   Mixed type of metadata.
   */
  public function getMetadata();

  /**
   * Sets the Font metadata.
   *
   * @param mixed $metadata
   *   The Font metadata.
   *
   * @return \Drupal\fontyourface\FontInterface
   *   The called Font entity.
   */
  public function setMetadata($metadata);

  /**
   * Gets the Font creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Font.
   */
  public function getCreatedTime();

  /**
   * Sets the Font creation timestamp.
   *
   * @param int $timestamp
   *   The Font creation timestamp.
   *
   * @return \Drupal\fontyourface\FontInterface
   *   The called Font entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Font changed timestamp.
   *
   * @return int
   *   Creation timestamp of the Font.
   */
  public function getChangedTime();

  /**
   * Sets the Font changed timestamp.
   *
   * @param int $timestamp
   *   The Font creation timestamp.
   *
   * @return \Drupal\fontyourface\FontInterface
   *   The called Font entity.
   */
  public function setChangedTime($timestamp);

  /**
   * Checks if the font is enabled.
   *
   * @return bool
   *   TRUE is font is enabled. FALSE otherwise.
   */
  public function isActivated();

  /**
   * Checks if the font is disabled.
   *
   * @return bool
   *   TRUE is font is disabled. FALSE otherwise.
   */
  public function isDeactivated();

  /**
   * Enable a font.
   *
   * @return bool
   *   TRUE is font is enabled. FALSE otherwise.
   */
  public function activate();

  /**
   * Disable a font.
   *
   * @return bool
   *   TRUE is font is disabled. FALSE otherwise.
   */
  public function deactivate();

  /**
   * Returns list of enabled fonts.
   *
   * @return array
   *   Array of fonts.
   */
  public static function loadActivatedFonts();

  /**
   * Returns font by url.
   *
   * @param string $font_url
   *   $The unique font url.
   *
   * @return array
   *   Array of fonts.
   */
  public static function loadByUrl($font_url);

}

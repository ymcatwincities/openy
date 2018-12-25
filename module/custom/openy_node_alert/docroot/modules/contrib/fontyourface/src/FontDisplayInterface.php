<?php

namespace Drupal\fontyourface;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Font display entities.
 */
interface FontDisplayInterface extends ConfigEntityInterface {

  /**
   * Gets the Font.
   *
   * @return Font
   *   Font from config.
   */
  public function getFont();

  /**
   * Gets the Font URL.
   *
   * @return string
   *   Font URL.
   */
  public function getFontUrl();

  /**
   * Sets the Font URL.
   *
   * @param string $font_url
   *   The Font URL.
   *
   * @return \Drupal\fontyourface\FontDisplayInterface
   *   The called Font Style entity.
   */
  public function setFontUrl($font_url);

  /**
   * Gets the Font fallback fonts.
   *
   * @return string
   *   Font URL.
   */
  public function getFallback();

  /**
   * Sets the Font fallback fonts.
   *
   * @param string $fallback
   *   The fallback fonts.
   *
   * @return \Drupal\fontyourface\FontDisplayInterface
   *   The called Font Style entity.
   */
  public function setFallback($fallback);

  /**
   * Gets the Font selectors.
   *
   * @return string
   *   Font selectors.
   */
  public function getSelectors();

  /**
   * Sets the Font selectors.
   *
   * @param string $selectors
   *   The Font selectors.
   *
   * @return \Drupal\fontyourface\FontDisplayInterface
   *   The called Font Style entity.
   */
  public function setSelectors($selectors);

  /**
   * Gets the site theme for display usage.
   *
   * @return string
   *   Site theme name.
   */
  public function getTheme();

  /**
   * Sets the Font theme for usage.
   *
   * @param string $theme
   *   Site theme name.
   *
   * @return \Drupal\fontyourface\FontDisplayInterface
   *   The called Font Style entity.
   */
  public function setTheme($theme);

  /**
   * Returns FontDisplays by theme name.
   *
   * @param string $theme
   *   Name of theme.
   *
   * @return array
   *   List of font style configs for theme.
   */
  public static function loadByTheme($theme);

}

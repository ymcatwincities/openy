<?php

namespace Drupal\slick\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Slick entity.
 */
interface SlickInterface extends ConfigEntityInterface {

  /**
   * Returns the number of breakpoints.
   *
   * @return int
   *   The number of the provided breakpoints.
   */
  public function getBreakpoints();

  /**
   * Returns the Slick skin.
   *
   * @return string
   *   The name of the Slick skin.
   */
  public function getSkin();

  /**
   * Returns the Slick options by group, or property.
   *
   * @param string $group
   *   The name of setting group: settings, responsives.
   * @param string $property
   *   The name of specific property: prevArrow, nexArrow.
   *
   * @return mixed|array|null
   *   Available options by $group, $property, all, or NULL.
   */
  public function getOptions($group = NULL, $property = NULL);

  /**
   * Returns the array of slick settings.
   *
   * @return array
   *   The array of settings.
   */
  public function getSettings();

  /**
   * Sets the array of slick settings.
   *
   * @param array $settings
   *   The new array of settings.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setSettings(array $settings = []);

  /**
   * Returns the value of a slick setting.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  public function getSetting($setting_name);

  /**
   * Sets the value of a slick setting.
   *
   * @param string $setting_name
   *   The setting name.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setSetting($setting_name, $value);

  /**
   * Returns available slick default options under group 'settings'.
   *
   * @param string $group
   *   The name of group: settings, responsives.
   *
   * @return array
   *   The default settings under options.
   */
  public static function defaultSettings($group = 'settings');

  /**
   * Returns the group this optioset instance belongs to for easy selections.
   *
   * @return string
   *   The name of the optionset group.
   */
  public function getGroup();

  /**
   * Returns whether to optimize the stored options, or not.
   *
   * @return bool
   *   If true, the stored options will be cleaned out from defaults.
   */
  public function optimized();

}

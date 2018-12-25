<?php

namespace Drupal\slick;

/**
 * Provides an interface defining Slick skins.
 *
 * The hook_hook_info() is deprecated, and no resolution by 1/16/16:
 *   #2233261: Deprecate hook_hook_info()
 *     Postponed till D9
 *
 * @see slick.api.php for more supported methods.
 */
interface SlickSkinInterface {

  /**
   * Returns the Slick skins.
   *
   * This can be used to register skins for the Slick. Skins will be
   * available when configuring the Optionset, Field formatter, or Views style,
   * or custom coded slicks.
   *
   * Slick skins get a unique CSS class to use for styling, e.g.:
   * If your skin name is "my_module_slick_carousel_rounded", the CSS class is:
   * slick--skin--my-module-slick-carousel-rounded
   *
   * A skin can specify CSS and JS files to include when Slick is displayed,
   * except for a thumbnail skin which accepts CSS only.
   *
   * Each skin supports 5 keys:
   * - name: The human readable name of the skin.
   * - description: The description about the skin, for help and manage pages.
   * - css: An array of CSS files to attach.
   * - js: An array of JS files to attach, e.g.: image zoomer, reflection, etc.
   * - group: A string grouping the current skin: main, thumbnail.
   * - provider: A module name registering the skins.
   *
   * @return array
   *   The array of the main and thumbnail skins.
   */
  public function skins();

}

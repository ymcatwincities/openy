<?php

/**
 * @file
 * Contains \Drupal\entity_browser\Annotation\EntityBrowserSelectionDisplay.
 */

namespace Drupal\entity_browser\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an entity browser selection display annotation object.
 *
 * @see hook_entity_browser_selection_display_info_alter()
 *
 * @Annotation
 */
class EntityBrowserSelectionDisplay extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the selection display.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A brief description of the selection display.
   *
   * This will be shown when adding or configuring this selection display.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

}

<?php

/**
 * @file
 * Contains \Drupal\panels_ipe\Annotation\IPEAccess.
 */

namespace Drupal\panels_ipe\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a IPE Access item annotation object.
 *
 * @see \Drupal\panels_ipe\Plugin\IPEAccessManager
 * @see plugin_api
 *
 * @Annotation
 */
class IPEAccess extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}

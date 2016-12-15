<?php

namespace Drupal\purge\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a PurgeQueuer annotation object.
 *
 * @Annotation
 */
class PurgeQueuer extends Plugin {

  /**
   * The plugin ID of the queuer plugin.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the queuer plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The description of the queuer plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * Whether the plugin needs to auto enable when first discovered.
   *
   * @var bool
   */
  public $enable_by_default = FALSE;

  /**
   * Full class name of the configuration form of your queuer, with leading
   * backslash. Class must extend \Drupal\purge_ui\Form\QueuerConfigFormBase.
   *
   * @var string
   */
  public $configform = '';

}

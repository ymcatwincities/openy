<?php

namespace Drupal\plugin\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Provides a plugin selector plugin annotation.
 *
 * @Annotation
 */
class PluginSelector extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The translated human-readable plugin name.
   *
   * @var string
   */
  public $label;
}

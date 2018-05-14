<?php

namespace Drupal\panels\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a display builder annotation object.
 *
 * @Annotation
 */
class DisplayBuilder extends Plugin {

  /**
   * The human-readable plugin label.
   *
   * @var string
   */
  public $label = '';

  /**
   * The plugin description.
   *
   * @var string
   */
  public $description = '';

}

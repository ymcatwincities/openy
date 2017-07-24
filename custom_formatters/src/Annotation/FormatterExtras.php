<?php

namespace Drupal\custom_formatters\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a formatter extras annotation object.
 *
 * @Annotation
 */
class FormatterExtras extends Plugin {

  /**
   * The formatter extra plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The formatter extra plugin label.
   *
   * @var string
   */
  public $label;

  /**
   * A description of the formatter extra.
   *
   * @var string
   */
  public $description;

  /**
   * A boolean flag determining if the extra is optional.
   *
   * @var bool
   */
  public $optional = TRUE;

  /**
   * A keyed array of dependencies.
   *
   * @var array
   */
  public $dependencies;

}

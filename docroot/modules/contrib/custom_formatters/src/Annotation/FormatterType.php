<?php

namespace Drupal\custom_formatters\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a formatter type annotation object.
 *
 * @Annotation
 */
class FormatterType extends Plugin {

  /**
   * The formatter type plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The formatter type plugin label.
   *
   * @var string
   */
  public $label;

  /**
   * A description of the formatter type.
   *
   * @var string
   */
  public $description;

  /**
   * Whether the formatter type plugin supports multiple fields.
   *
   * @var bool
   */
  public $multipleFields = FALSE;

}

<?php

namespace Drupal\openy_myy\Annotation;

use Drupal\Component\Annotation\Plugin;

class MyYDataSessions extends Plugin {

  /**
   * The plugin ID.
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the formatter type.
   * @ingroup plugin_translatable
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A short description of the formatter type.
   * @ingroup plugin_translatable
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * The name of the field formatter class.
   * This is not provided manually, it will be added by the discovery mechanism.
   * @var string
   */
  public $class;

}

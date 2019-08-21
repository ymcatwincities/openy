<?php

namespace Drupal\openy_myy\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a MyYDataVisits annotation object.
 *
 * @see \Drupal\openy_myy\PluginManager\MyYDataVisits
 * @see plugin_api
 *
 * @Annotation
 */
class MyYDataVisits extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the formatter type.
   *
   * @var string
   */
  public $label;

  /**
   * A short description of the formatter type.
   *
   * @var string
   */
  public $description;

}

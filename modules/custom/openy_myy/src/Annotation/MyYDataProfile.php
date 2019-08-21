<?php

namespace Drupal\openy_myy\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a MyYDataProfile annotation object.
 *
 * @see \Drupal\openy_myy\PluginManager\MyYDataProfile
 * @see plugin_api
 *
 * @Annotation
 */
class MyYDataProfile extends Plugin {

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

<?php

namespace Drupal\openy_myy\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a MyYDataChildcare annotation object.
 *
 * @see \Drupal\openy_myy\PluginManager\MyYDataVisits
 * @see plugin_api
 *
 * @Annotation
 */
class MyYDataChildcare extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of plugin.
   *
   * @var string
   */
  public $label;

  /**
   * A short description of plugin implementation.
   *
   * @var string
   */
  public $description;

}

<?php

namespace Drupal\openy_myy\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a MyYDataOrders annotation object.
 *
 * @see \Drupal\openy_myy\PluginManager\MyYDataOrders
 * @see plugin_api
 *
 * @Annotation
 */
class MyYDataOrders extends Plugin {

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

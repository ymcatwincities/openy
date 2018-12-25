<?php

namespace Drupal\rabbit_hole\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Rabbit hole behavior plugin item annotation object.
 *
 * @see \Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class RabbitHoleBehaviorPlugin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}

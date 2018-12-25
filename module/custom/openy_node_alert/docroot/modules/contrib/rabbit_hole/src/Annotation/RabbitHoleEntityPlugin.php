<?php

namespace Drupal\rabbit_hole\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Rabbit hole entity plugin item annotation object.
 *
 * @see \Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class RabbitHoleEntityPlugin extends Plugin {

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

  /**
   * The string id of the affected entity.
   *
   * @var string
   */
  public $entityType;

}

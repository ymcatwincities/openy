<?php

/**
 * @file
 * Contains \Drupal\webform\Annotation\Component.
 */

namespace Drupal\webform\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Component item annotation object.
 *
 * Plugin Namespace: Plugin\webform\component
 *
 * @see \Drupal\webform\Plugin\ComponentManager
 * @see plugin_api
 *
 * @Annotation
 */
class Component extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the component.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The description of the component.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}

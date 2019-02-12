<?php

namespace Drupal\openy_upgrade_tool\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an config event ignore annotation object.
 *
 * Plugin Namespace: Plugin\ConfigEventIgnore.
 *
 * For a working example, see
 * \Drupal\openy_upgrade_tool\Plugin\ConfigEventIgnore\Views
 *
 * @see \Drupal\openy_upgrade_tool\Annotation\ConfigEventIgnore
 * @see \Drupal\openy_upgrade_tool\ConfigEventIgnoreBase
 * @see \Drupal\openy_upgrade_tool\ConfigEventIgnorePluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class ConfigEventIgnore extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the config event ignore.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The config type.
   *
   * @var string
   */
  public $type;

  /**
   * The plugin weight.
   *
   * Used for overriding plugins for same type. If several plugins were defined
   * for one config type - the one with the most weight will be used.
   *
   * @var string
   */
  public $weight;

}

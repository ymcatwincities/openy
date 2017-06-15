<?php

namespace Drupal\migrate_plus\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an authentication annotation object.
 *
 * Plugin Namespace: Plugin\migrate_plus\authentication
 *
 * @see \Drupal\migrate_plus\AuthenticationPluginBase
 * @see \Drupal\migrate_plus\AuthenticationPluginInterface
 * @see \Drupal\migrate_plus\AuthenticationPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class Authentication extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

}

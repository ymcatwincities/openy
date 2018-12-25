<?php

namespace Drupal\purge\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a PurgePurger annotation object.
 *
 * @Annotation
 */
class PurgePurger extends Plugin {

  /**
   * The plugin ID of the purger plugin.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the purger plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * Full class name of the configuration form of your purger, with leading
   * backslash. Class must extend \Drupal\purge_ui\Form\PurgerConfigFormBase.
   *
   * @var string
   */
  public $configform = '';

  /**
   * Time in seconds to wait after invalidation.
   *
   * The value is expressed as float between 0.0 and 3.0. After ::invalidate()
   * finished, the system will automatically wait this time to allow the caching
   * platform in front of Drupal, to catch up (before other purgers kick in).
   *
   * This value adds up to the total time hint of this purger and therefore the
   * higher this value is, the less processing can happen per request. Platforms
   * that clear instantly (e.g.: via a socket) are best off leaving this at 0.0.
   *
   * @var float
   */
  public $cooldown_time = 0.0;

  /**
   * The description of the purger plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * Whether end users can create more then one instance of the purger plugin.
   *
   * When you set 'multi_instance = TRUE' in your plugin annotation, it
   * becomes possible for end-users to create multiple instances of your
   * purger. With \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::getId(), you can read
   * the unique identifier of your instance to keep multiple instances apart.
   *
   * @var bool
   */
  public $multi_instance = FALSE;

  /**
   * A list of invalidation types that are supported by the purger plugin, for
   * instance 'tag', 'path' or 'url'. The plugin will only receive invalidation
   * requests for the given types, others fail with state NOT_SUPPORTED. It
   * is possible to dynamically provide this list by overloading the base
   * implementation of \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::getTypes().
   *
   * @see \Drupal\purge\Plugin\Purge\Purger\PurgerInterface::getTypes()
   *
   * @var string[]
   */
  public $types = [];

}

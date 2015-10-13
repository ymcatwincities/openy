<?php

/**
 * @file
 * Contains \Drupal\embed\Annotation\EntityType.
 */

namespace Drupal\embed\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an embed type annotation object.
 *
 * @ingroup embed_api
 *
 * @Annotation
 */
class EmbedType extends Plugin {

  /**
   * The embed type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the embed type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  protected $label;

  /**
   * The name of the embed form class associated with this embed type.
   *
   * @var string
   */
  protected $embed_form_class;

}

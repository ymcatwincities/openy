<?php

/**
 * @file
 * Contains \Drupal\entity_embed\Annotation\EntityEmbedDisplay.
 */

namespace Drupal\entity_embed\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an entity embed display annotation object.
 *
 * @ingroup entity_embed_api
 *
 * @Annotation
 */
class EntityEmbedDisplay extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the display plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label = '';

  /**
   * The entity types the display can apply to.
   *
   * To make the display plugin valid for all entity types, set this value to
   * FALSE.
   *
   * @var bool|array
   */
  public $entity_types = FALSE;

}

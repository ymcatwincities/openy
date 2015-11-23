<?php

/**
 * @file
 * Contains \Drupal\crop\Annotation\CropEntityProvider.
 */

namespace Drupal\crop\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the annotation object for crop integration with storage entities.
 *
 * @see hook_crop_entity_provider_info_alter()
 *
 * @Annotation
 */
class CropEntityProvider extends Plugin {

  /**
   * Entity type plugin provides.
   *
   * @var string
   */
  public $entity_type;

  /**
   * The human-readable name of the crop entity provider (will usually match
   * entity type name).
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A brief description of the crop entity provider.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->definition['entity_type'];
  }

}

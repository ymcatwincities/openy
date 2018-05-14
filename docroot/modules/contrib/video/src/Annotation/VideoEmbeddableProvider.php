<?php

/**
 * @file
 * Contains \Drupal\video\Annotation\VideoEmbeddableProvider.
 */

namespace Drupal\video\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a VideoEmbeddableProvider item annotation object.
 *
 * @Annotation
 */
class VideoEmbeddableProvider extends Plugin {

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
   * A brief description of the plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';
  
  /**
   * List of regular expressions that match embed codes and URLs of videos.
   *
   * @var array
   */
  public $regular_expressions = [];
  
  /**
   * A mimetype of the plugin.
   *
   * @var string
   */
  public $mimetype = '';
  
  /**
   * A stream_wrapper to use in the plugin.
   *
   * @var string
   */
  public $stream_wrapper = '';  

}

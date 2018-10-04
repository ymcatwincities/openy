<?php

namespace Drupal\social_feed_fetcher\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Annotations for Node Processor plugin.
 *
 * @Annotation
 */
class SocialDataProvider extends Plugin {

  /**
   * ID.
   */
  public $id;

  /**
   * Label will be used in interface.
   */
  public $label;
}

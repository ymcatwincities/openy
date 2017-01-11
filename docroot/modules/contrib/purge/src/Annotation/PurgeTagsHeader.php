<?php

namespace Drupal\purge\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a PurgeTagsHeader annotation object.
 *
 * @Annotation
 */
class PurgeTagsHeader extends Plugin {

  /**
   * The plugin ID of the tagsheader.
   *
   * @var string
   */
  public $id;

  /**
   * The HTTP response header that the plugin sets.
   *
   * @warning
   *   In RFC #6648 the use of 'X-' as header prefixes has been deprecated
   *   for "application protocols", this naturally includes Drupal. Therefore
   *   if this is possible, consider header names without this prefix.
   *
   * @var string
   */
  public $header_name;

}

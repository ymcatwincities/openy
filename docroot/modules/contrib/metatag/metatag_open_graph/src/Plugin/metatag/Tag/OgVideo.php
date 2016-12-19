<?php

namespace Drupal\metatag_open_graph\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'og:video' meta tag.
 *
 * @MetatagTag(
 *   id = "og_video",
 *   label = @Translation("Video URL"),
 *   description = @Translation("The URL of an video which should represent the content. For best results use an image that is at least 1200 x 630 pixels in size, but at least 600 x 316 pixels is a recommended minimum. Supports PNG, JPEG and GIF formats. Should not be used if og:image:url is used."),
 *   name = "og:video",
 *   group = "open_graph",
 *   weight = 9,
 *   type = "video",
 *   secure = FALSE,
 *   multiple = TRUE
 * )
 */
class OgVideo extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}

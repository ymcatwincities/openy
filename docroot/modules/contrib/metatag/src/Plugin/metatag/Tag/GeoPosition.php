<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'geo.position' meta tag.
 *
 * @MetatagTag(
 *   id = "geo_position",
 *   label = @Translation("Geographical position"),
 *   description = @Translation("Geo-spatial information in 'latitude, longitude' format, e.g. '50.167958, -97.133185'; <a href='http://en.wikipedia.org/wiki/ICBM'>see Wikipedia for details</a>."),
 *   name = "geo.position",
 *   group = "advanced",
 *   weight = 0,
 *   type = "label",
 *   secure = 0,
 *   multiple = FALSE
 * )
 */
class GeoPosition extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}

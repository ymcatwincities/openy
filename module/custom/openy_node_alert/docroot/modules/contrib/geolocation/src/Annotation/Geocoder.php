<?php

namespace Drupal\geolocation\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a geocoder annotation object.
 *
 * @see \Drupal\geolocation\GeocoderManager
 * @see plugin_api
 *
 * @Annotation
 */
class Geocoder extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the geocoder.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * The description of the geocoder.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The name of the geocoder.
   *
   * @var bool
   */
  public $locationCapable;

  /**
   * The name of the geocoder.
   *
   * @var bool
   */
  public $boundaryCapable;

}

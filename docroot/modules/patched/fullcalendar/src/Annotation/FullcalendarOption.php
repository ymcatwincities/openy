<?php

namespace Drupal\fullcalendar\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Fullcalendar option annotation object.
 *
 * @Annotation
 */
class FullcalendarOption extends Plugin {

  public $id;
  public $js = FALSE;
  public $css = FALSE;
  public $weight = 0;

}

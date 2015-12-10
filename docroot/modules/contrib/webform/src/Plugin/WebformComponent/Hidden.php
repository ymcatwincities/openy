<?php

/**
 * @file
 * Contains \Drupal\webform\Plugin\WebformComponent\Hidden.
 */

namespace Drupal\webform\Plugin\WebformComponent;

use Drupal\webform\ComponentBase;

/**
 * Provides a 'hidden' component.
 *
 * @Component(
 *   id = "hidden",
 *   label = @Translation("Hidden"),
 *   description = @Translation("A form field that is not shown to end-users.")
 * )
 */
class Hidden extends ComponentBase {}

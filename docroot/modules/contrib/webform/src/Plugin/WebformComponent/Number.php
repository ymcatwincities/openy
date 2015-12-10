<?php

/**
 * @file
 * Contains \Drupal\webform\Plugin\WebformComponent\Number.
 */

namespace Drupal\webform\Plugin\WebformComponent;

use Drupal\webform\ComponentBase;

/**
 * Provides a 'number' component.
 *
 * @Component(
 *   id = "number",
 *   label = @Translation("Number"),
 *   description = @Translation("A field that accepts numerical values only.")
 * )
 */
class Number extends ComponentBase {}

<?php

/**
 * @file
 * Contains \Drupal\webform\Plugin\WebformComponent\Email.
 */

namespace Drupal\webform\Plugin\WebformComponent;

use Drupal\webform\ComponentBase;

/**
 * Provides a 'email' component.
 *
 * @Component(
 *   id = "email",
 *   label = @Translation("E-mail"),
 *   description = @Translation("A special textfield that accepts e-mail addresses.")
 * )
 */
class Email extends ComponentBase {}

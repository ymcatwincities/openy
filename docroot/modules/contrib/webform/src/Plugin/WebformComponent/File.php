<?php

/**
 * @file
 * Contains \Drupal\webform\Plugin\WebformComponent\File.
 */

namespace Drupal\webform\Plugin\WebformComponent;

use Drupal\webform\ComponentBase;

/**
 * Provides a 'file' component.
 *
 * @Component(
 *   id = "file",
 *   label = @Translation("File"),
 *   description = @Translation("A validated field that allows both public or private file uploads.")
 * )
 */
class File extends ComponentBase {}

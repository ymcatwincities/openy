<?php

namespace Drupal\openy_system\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Provides output terms.txt output.
 */
class TermsOfUseController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Provides the terms.txt file.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The terms.txt file as a response object with 'text/plain' content type.
   */
  public function content() {
    $config = \Drupal::config('openy.terms_and_conditions.schema');
    $timestamp = $config->get('accepted_version');
    $content = $this->t('Terms and Conditions were not accepted');

    // Is accepted.
    if ($timestamp) {
      $date = \Drupal::service('date.formatter')
        ->format($timestamp, 'custom', 'F d, Y', 'UTC');
      $time = \Drupal::service('date.formatter')
        ->format($timestamp, 'custom', 'H:i:s', 'UTC');
      $content = $this->t(
        'Terms and Conditions were accepted on @date at @time time UTC',
        [
          '@date' => $date,
          '@time' => $time,
        ]
      );
    }

    return new Response($content, 200, ['Content-Type' => 'text/plain']);
  }

}

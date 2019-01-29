<?php

namespace Drupal\openy_system\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Provides output terms.txt output.
 */
class TermsOfUseController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * TermsOfUseController constructor.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   */
  public function __construct(DateFormatterInterface $dateFormatter) {
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter')
    );
  }

  /**
   * Provides the terms.txt file.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The terms.txt file as a response object with 'text/plain' content type.
   */
  public function content() {
    $config = $this->config('openy.terms_and_conditions.schema');
    $timestamp = $config->get('accepted_version');
    $content = $this->t('Terms and Conditions were not accepted');

    // Is accepted.
    if ($timestamp) {
      $date = $this->dateFormatter
        ->format($timestamp, 'custom', 'F d, Y', 'UTC');
      $time = $this->dateFormatter
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

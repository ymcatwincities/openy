<?php

/**
 * @file
 * Contains Drupal\webprofiler\IdeLinkGenerator.
 */

namespace Drupal\webprofiler\Helper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Class IdeLinkGenerator.
 */
class IdeLinkGenerator implements IdeLinkGeneratorInterface {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config_factory;

  /**
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config_factory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function generateLink($file, $line) {
    $ide_link = $this->config_factory->get('webprofiler.config')
      ->get('ide_link');

    return new FormattableMarkup($ide_link, ['@file' => $file, '@line' => $line]);
  }
}

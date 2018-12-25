<?php

/**
 * @file
 * Contains \Drupal\video\StreamWrapper\VineStream.
 */

namespace Drupal\video\StreamWrapper;

use Drupal\Core\StreamWrapper\ReadOnlyStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;

/**
 * Defines a VineStream (vine://) stream wrapper class.
 */
class VineStream extends VideoRemoteStreamWrapper {
  
  protected static $base_url = 'https://vine.co/v';
  
  /**
   * {@inheritdoc}
   */
  public function getName() {
    return t('Vine');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Video served by the Vine services.');
  }
  
  /**
   * {@inheritdoc}
   */
  public static function baseUrl() {
    return self::$base_url;
  }
}

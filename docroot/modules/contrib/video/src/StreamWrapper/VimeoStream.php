<?php

/**
 * @file
 * Contains \Drupal\video\StreamWrapper\VimeoStream.
 */

namespace Drupal\video\StreamWrapper;

use Drupal\Core\StreamWrapper\ReadOnlyStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;

/**
 * Defines a YouTube (vimeo://) stream wrapper class.
 */
class VimeoStream extends VideoRemoteStreamWrapper {
  
  protected static $base_url = 'http://www.vimeo.com';
  
  /**
   * {@inheritdoc}
   */
  public function getName() {
    return t('Vimeo');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Video served by the Vimeo services.');
  }
  
  /**
   * {@inheritdoc}
   */
  public static function baseUrl() {
    return self::$base_url;
  }
}

<?php

/**
 * @file
 * Contains \Drupal\video\StreamWrapper\InstagramStream.
 */

namespace Drupal\video\StreamWrapper;

use Drupal\Core\StreamWrapper\ReadOnlyStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;

/**
 * Defines a InstagramStream (instagram://) stream wrapper class.
 */
class InstagramStream extends VideoRemoteStreamWrapper {
  
  protected static $base_url = 'https://www.instagram.com/p';
  
  /**
   * {@inheritdoc}
   */
  public function getName() {
    return t('Instagram');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Video served by the Instagram services.');
  }
  
  /**
   * {@inheritdoc}
   */
  public static function baseUrl() {
    return self::$base_url;
  }
}

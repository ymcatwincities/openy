<?php

/**
 * @file
 * Contains \Drupal\video\StreamWrapper\FacebookStream.
 */

namespace Drupal\video\StreamWrapper;

use Drupal\Core\StreamWrapper\ReadOnlyStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;

/**
 * Defines a Facebook (facebook://) stream wrapper class.
 */
class FacebookStream extends VideoRemoteStreamWrapper {
  
  protected static $base_url = 'https://www.facebook.com/video.php?v=';
  
  /**
   * {@inheritdoc}
   */
  public function getName() {
    return t('Facebook');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Video served by the Facebook services.');
  }
  
  /**
   * {@inheritdoc}
   */
  public static function baseUrl() {
    return self::$base_url;
  }
}

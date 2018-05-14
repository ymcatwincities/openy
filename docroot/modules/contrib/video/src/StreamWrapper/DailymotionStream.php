<?php

/**
 * @file
 * Contains \Drupal\video\StreamWrapper\DailymotionStream.
 */

namespace Drupal\video\StreamWrapper;

use Drupal\Core\StreamWrapper\ReadOnlyStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;

/**
 * Defines a Dailymotion (dailymotion://) stream wrapper class.
 */
class DailymotionStream extends VideoRemoteStreamWrapper {
  
  protected static $base_url = 'http://www.dailymotion.com/video';
  
  /**
   * {@inheritdoc}
   */
  public function getName() {
    return t('Dailymotion');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Video served by the Dailymotion services.');
  }
  
  /**
   * {@inheritdoc}
   */
  public static function baseUrl() {
    return self::$base_url;
  }
}

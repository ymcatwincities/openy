<?php

namespace Drupal\ymca_alters;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Class YmcaDateCacheReset.
 *
 * @package Drupal\ymca_alters
 */
class YmcaDateCacheReset implements YmcaDateCacheResetInterface {

  /**
   * CacheTagsInvalidator definition.
   *
   * @var CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * LoggerFactory definition.
   *
   * @var LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * YmcaDateCacheReset constructor.
   *
   * @param CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   CacheTagsInvalidator.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   LoggerFactory.
   */
  public function __construct(CacheTagsInvalidatorInterface $cache_tags_invalidator, LoggerChannelFactoryInterface $logger_factory) {
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->cacheTagsInvalidator->invalidateTags(['ymca_cron']);
    $this->loggerFactory->get('ymca_alters')->info('Date based content cache was cleared.');
  }

}

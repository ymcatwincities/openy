<?php

namespace Drupal\ygtc_pef_gxp_sync\syncer;

use Drupal\Core\Logger\LoggerChannel;

/**
 * Class Fetcher.
 *
 * @package Drupal\ygtc_pef_gxp_sync\syncer
 */
class Fetcher implements FetcherInterface {

  /**
   * Wrapper.
   *
   * @var \Drupal\ygtc_pef_gxp_sync\syncer\WrapperInterface
   */
  protected $wrapper;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * Fetcher constructor.
   *
   * @param \Drupal\ygtc_pef_gxp_sync\syncer\WrapperInterface $wrapper
   *   Wrapper.
   * @param \Drupal\Core\Logger\LoggerChannel $loggerChannel
   *   Logger.
   */
  public function __construct(WrapperInterface $wrapper, LoggerChannel $loggerChannel) {
    $this->wrapper = $wrapper;
    $this->logger = $loggerChannel;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch() {
    $a = 10;
  }

}

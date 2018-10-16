<?php

namespace Drupal\ygtc_pef_gxp_sync\syncer;

use Drupal\Core\Logger\LoggerChannel;

/**
 * Class Saver.
 *
 * @package Drupal\ygtc_pef_gxp_sync\syncer
 */
class Saver implements SaverInterface {

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
   * Saver constructor.
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
  public function save() {
    $data = $this->wrapper->getProcessedData();

    // @todo Loop over data and create the appropriate entities.
    // @todo Create field_session_class paragraph.
    // @todo Create field_session_time paragraph.
    // @todo Create field_session_exclusions paragraph.
    // @todo Create session instance itself.
  }

}

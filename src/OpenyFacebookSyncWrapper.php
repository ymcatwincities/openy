<?php

namespace Drupal\openy_facebook_sync;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class Wrapper.
 *
 * @package Drupal\openy_facebook_sync
 */
class OpenyFacebookSyncWrapper implements OpenyFacebookSyncWrapperInterface {

  use StringTranslationTrait;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;

  /**
   * Source data.
   *
   * @var array
   */
  private $sourceData = [];

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger factory.
   */
  public function __construct(LoggerChannelInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceData() {
    return $this->sourceData;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceData(array $data) {
    $this->sourceData = $data;
  }

}

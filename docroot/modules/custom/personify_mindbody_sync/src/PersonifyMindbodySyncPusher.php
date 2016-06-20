<?php

namespace Drupal\personify_mindbody_sync;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class PersonifyMindbodySyncPusher.
 *
 * @package Drupal\personify_mindbody_sync
 */
class PersonifyMindbodySyncPusher implements PersonifyMindbodySyncPusherInterface {

  /**
   * Drupal\personify_mindbody_sync\PersonifyMindbodySyncWrapper definition.
   *
   * @var PersonifyMindbodySyncWrapper
   */
  protected $wrapper;

  /**
   * Logger channel.
   *
   * @var LoggerChannel
   */
  protected $logger;

  /**
   * PersonifyMindbodySyncPusher constructor.
   *
   * @param PersonifyMindbodySyncWrapper $wrapper
   *   Data wrapper.
   */
  public function __construct(PersonifyMindbodySyncWrapper $wrapper, LoggerChannelFactory $logger_factory) {
    $this->wrapper = $wrapper;
    $this->logger = $logger_factory->get(PersonifyMindbodySyncWrapper::CHANNEL);
  }

  /**
   * {@inheritdoc}
   */
  public function push() {
    $a = 10;
  }

}

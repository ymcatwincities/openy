<?php

namespace Drupal\personify_mindbody_sync;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class PersonifyMindbodySyncProxy.
 *
 * @package Drupal\personify_mindbody_sync
 */
class PersonifyMindbodySyncProxy implements PersonifyMindbodySyncProxyInterface {

  /**
   * PersonifyMindbodySyncWrapper definition.
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
   * PersonifyMindbodySyncProxy constructor.
   *
   * @param PersonifyMindbodySyncWrapper $wrapper
   *   Wrapper.
   * @param LoggerChannelFactory $logger_factory
   *   Logger factory.
   */
  public function __construct(PersonifyMindbodySyncWrapper $wrapper, LoggerChannelFactory $logger_factory) {
    $this->wrapper = $wrapper;
    $this->logger = $logger_factory->get(PersonifyMindbodySyncWrapper::CHANNEL);
  }

  /**
   * {@inheritdoc}
   */
  public function saveEntities() {
    $a = 10;
  }

}

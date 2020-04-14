<?php

namespace Drupal\ymca_sync;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SyncerRunner.
 *
 * @package Drupal\ymca_sync.
 */
class SyncerRunner {

  /**
   * The used lock backend instance.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * LoggerChannelInterface.
   *
   * @var Drupal\Core\Logger\LoggerChannelInterface
   *   LoggerChannelInterface.
   */
  protected $logger;

  /**
   * YMCA sync config.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;


  /**
   * ContainerInterface.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   *   ContainerInterface.
   */
  protected $container;

  /**
   * Implement construct method.
   *
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   Lock.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   LoggerChannelInterface.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   ConfigFactory.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   ContainerInterface.
   */
  public function __construct(LockBackendInterface $lock, LoggerChannelInterface $logger, ConfigFactory $configFactory, ContainerInterface $container) {
    $this->lock = $lock;
    $this->logger = $logger;
    $this->config = $configFactory->get('ymca_sync.settings');
    $this->container = $container;
  }

  /**
   * Run sync.
   *
   * @param string $name
   *   Service name.
   * @param string $method
   *   Method name.
   * @param array $options
   *   Options.
   *
   * @throws \Exception
   */
  public function run($name, $method, array $options = []) {
    $active = $this->config->get('active_syncers');

    // Run only active syncers.
    if (!in_array($name, $active)) {
      $msg = 'Syncer "%name" is not activated or found. Exit. ';
      $msg .= 'Active syncers: %syncers';
      $this->logger->info(
        $msg,
        [
          '%name' => $name,
          '%syncers' => implode(', ', $active),
        ]);
      return;
    }

    $service = $this->container->get($name);
    if (!$service) {
      throw new \Exception('Failed to load specified service');
    }

    if ($this->lock->acquire($name, 250.0)) {
      $service->{$method}($options);
      $this->lock->release($name);
      return;
    }

    $msg = 'Lock syncer "%name" is still working. Exit.';
    $this->logger->info($msg, ['%name' => $name]);
  }

}
